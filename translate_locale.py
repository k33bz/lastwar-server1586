#!/usr/bin/env python3
"""
Translate i18next locale JSON files using LM Studio API.
Preserves JSON structure and interpolation variables like {{var}}.
"""
import json
import requests
import sys
import time
import re
import subprocess
from pathlib import Path

def translate_text(text, target_lang):
    """Translate text using LM Studio API while preserving interpolation variables."""
    if not text or text.strip() == "":
        return text

    lang_names = {
        'es': 'Spanish',
        'pt': 'Portuguese',
        'ko': 'Korean',
        'de': 'German'
    }

    # Don't translate if it's just a variable or number
    if re.match(r'^[\d\s{{}}]+$', text):
        return text

    url = 'http://localhost:1234/v1/chat/completions'
    headers = {'Content-Type': 'application/json'}

    # Hunyuan-MT models work best with simple, direct instructions
    system_prompt = f'You are a professional translator. Translate the following text from English to {lang_names[target_lang]}. Preserve variables like {{{{var}}}}, HTML tags like <strong>, and proper nouns like "Server 1586", "NAP15", "Discord", "GitHub", "Claude Code", "Kiro". Only output the translation.'

    payload = {
        'messages': [
            {'role': 'system', 'content': system_prompt},
            {'role': 'user', 'content': text}
        ],
        'temperature': 0.3,
        'max_tokens': 500
    }

    try:
        response = requests.post(url, headers=headers, json=payload, timeout=30)
        response.raise_for_status()
        result = response.json()
        translated = result['choices'][0]['message']['content'].strip()

        # Remove any quotes that the model might add
        if translated.startswith('"') and translated.endswith('"'):
            translated = translated[1:-1]

        return translated
    except Exception as e:
        print(f"Error translating '{text[:50]}...': {e}", file=sys.stderr)
        return text

def translate_dict(data, target_lang, path=""):
    """Recursively translate all string values in a dictionary."""
    if isinstance(data, dict):
        result = {}
        for key, value in data.items():
            current_path = f"{path}.{key}" if path else key
            print(f"  Translating: {current_path}", file=sys.stderr)
            result[key] = translate_dict(value, target_lang, current_path)
            time.sleep(0.3)  # Rate limiting
        return result
    elif isinstance(data, str):
        return translate_text(data, target_lang)
    else:
        return data

def translate_locale_file(input_file, output_file, target_lang):
    """Translate entire locale JSON file."""
    print(f"\n{'='*60}", file=sys.stderr)
    print(f"Translating {input_file} to {target_lang.upper()}", file=sys.stderr)
    print(f"Output: {output_file}", file=sys.stderr)
    print(f"{'='*60}\n", file=sys.stderr)

    with open(input_file, 'r', encoding='utf-8') as f:
        source_data = json.load(f)

    translated_data = translate_dict(source_data, target_lang)

    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(translated_data, f, ensure_ascii=False, indent=2)

    print(f"\n✓ Translation completed: {output_file}", file=sys.stderr)

def get_available_models():
    """Get list of available models from LM Studio."""
    try:
        result = subprocess.run(['lms', 'ls'], capture_output=True, text=True, timeout=10)
        if result.returncode == 0:
            models = []
            for line in result.stdout.split('\n'):
                line = line.strip()
                if line and not line.startswith('Downloaded'):
                    parts = line.split()
                    if parts:
                        models.append(parts[0])
            return models
    except Exception as e:
        print(f"⚠️  Could not get model list from CLI: {e}", file=sys.stderr)
    return []

def find_translation_model(models):
    """Find the best translation model from available models."""
    preferred_keywords = ['hunyuan-mt', 'hunyuan', 'translate', 'mt', 'translation']
    for keyword in preferred_keywords:
        for model in models:
            if keyword.lower() in model.lower():
                return model
    if models:
        print("⚠️  No translation-specific model found, using first available model", file=sys.stderr)
        return models[0]
    return None

def test_model_ready():
    """Test if the loaded model is actually ready to translate."""
    try:
        url = 'http://localhost:1234/v1/chat/completions'
        headers = {'Content-Type': 'application/json'}
        payload = {
            'messages': [
                {'role': 'system', 'content': 'You are a translator.'},
                {'role': 'user', 'content': 'Hello'}
            ],
            'temperature': 0.3,
            'max_tokens': 10
        }
        response = requests.post(url, headers=headers, json=payload, timeout=10)
        return response.status_code == 200
    except Exception:
        return False

def load_lm_studio_model():
    """Automatically find and load a translation model in LM Studio."""
    print("\n" + "="*60, file=sys.stderr)
    print("🔍 Searching for translation models...", file=sys.stderr)
    print("="*60 + "\n", file=sys.stderr)

    try:
        response = requests.get('http://localhost:1234/v1/models', timeout=5)
        if response.status_code != 200:
            print("❌ LM Studio is not responding", file=sys.stderr)
            return False
    except requests.exceptions.RequestException as e:
        print(f"❌ Cannot connect to LM Studio: {e}", file=sys.stderr)
        return False

    models_response = requests.get('http://localhost:1234/v1/models', timeout=5).json()
    if models_response.get('data'):
        print("📋 Model detected, verifying it's ready...", file=sys.stderr)
        if test_model_ready():
            print("✓ Model is loaded and ready!\n", file=sys.stderr)
            return True
        else:
            print("⚠️  Model detected but not responding, will try to load...\n", file=sys.stderr)

    print("📋 Getting available models...", file=sys.stderr)
    available_models = get_available_models()

    if not available_models:
        print("❌ No models found!", file=sys.stderr)
        return False

    translation_model = find_translation_model(available_models)
    if not translation_model:
        print("❌ No suitable translation model found!", file=sys.stderr)
        return False

    print(f"✓ Found translation model: {translation_model}", file=sys.stderr)
    print(f"📥 Loading model (this may take a minute)...", file=sys.stderr)

    try:
        result = subprocess.run(['lms', 'load', translation_model],
                              capture_output=True, text=True, timeout=60)
        if result.returncode == 0:
            print(f"✓ Model load command completed", file=sys.stderr)
            print("⏳ Waiting for model to initialize...", file=sys.stderr)
            time.sleep(3)

            print("🔍 Verifying model is ready...", file=sys.stderr)
            for attempt in range(5):
                if test_model_ready():
                    print(f"✓ Model is ready!\n", file=sys.stderr)
                    return True
                print(f"   Waiting... (attempt {attempt + 1}/5)", file=sys.stderr)
                time.sleep(2)

            print("❌ Model loaded but not responding to requests", file=sys.stderr)
            return False
        else:
            print(f"❌ Failed to load model: {result.stderr}", file=sys.stderr)
            return False
    except Exception as e:
        print(f"❌ Error loading model: {e}", file=sys.stderr)
        return False

def unload_lm_studio_model():
    """Automatically unload LM Studio model."""
    print("\n" + "="*60, file=sys.stderr)
    print("✓ Translation complete!", file=sys.stderr)
    print("📤 Unloading model...", file=sys.stderr)

    try:
        result = subprocess.run(['lms', 'unload'], capture_output=True, text=True, timeout=10)
        if result.returncode == 0:
            print("✓ Model unloaded successfully!", file=sys.stderr)
        else:
            print(f"⚠️  Could not unload model: {result.stderr}", file=sys.stderr)
    except Exception as e:
        print(f"⚠️  Error unloading model: {e}", file=sys.stderr)

    print("="*60 + "\n", file=sys.stderr)

def main():
    if len(sys.argv) < 2:
        print("Usage:")
        print("  python translate_locale.py <target_lang>        # Translate public.json")
        print("  python translate_locale.py <target_lang> <file> # Translate specific file")
        print("\nExamples:")
        print("  python translate_locale.py es              # Translate public.json to Spanish")
        print("  python translate_locale.py ko common.json  # Translate common.json to Korean")
        print("\nSupported languages: es, pt, ko, de")
        sys.exit(1)

    target_lang = sys.argv[1]
    if target_lang not in ['es', 'pt', 'ko', 'de']:
        print(f"Error: Unsupported language '{target_lang}'", file=sys.stderr)
        print("Supported: es, pt, ko, de", file=sys.stderr)
        sys.exit(1)

    # Determine which file to translate
    if len(sys.argv) >= 3:
        filename = sys.argv[2]
    else:
        filename = 'public.json'

    # Setup paths
    base_dir = Path(__file__).parent
    input_file = base_dir / 'client' / 'locales' / 'en-US' / filename
    output_dir = base_dir / 'client' / 'locales' / target_lang
    output_file = output_dir / filename

    # Create output directory if needed
    output_dir.mkdir(parents=True, exist_ok=True)

    # Automatically load LM Studio model
    if not load_lm_studio_model():
        print("\n❌ Cannot proceed - failed to load translation model", file=sys.stderr)
        print("   Please ensure LM Studio is running with a translation model", file=sys.stderr)
        sys.exit(1)

    try:
        # Translate
        translate_locale_file(str(input_file), str(output_file), target_lang)
    finally:
        # Always unload model at the end
        unload_lm_studio_model()

if __name__ == '__main__':
    main()
