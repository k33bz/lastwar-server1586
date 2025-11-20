#!/usr/bin/env python3
"""
Smart Translation Script for Admin i18n
Translates entire JSON sections at once with glossary preservation

Usage:
    python translate_admin_smart.py [languages]

Examples:
    python translate_admin_smart.py ko           # Translate only Korean
    python translate_admin_smart.py es pt        # Translate Spanish and Portuguese
    python translate_admin_smart.py              # Translate all languages
"""

import json
import requests
import time
import sys
import argparse
from pathlib import Path

# Fix Windows console encoding for emoji support
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except AttributeError:
        # Python < 3.7
        import codecs
        sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
        sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

# Terms that should NOT be translated
PRESERVE_TERMS = [
    "R5", "R4", "APE", "NAP15",
    "Discord", "SMTP", "JWT", "API",
    "UvvU", "ORCE", "MTOP", "FNXS", "MZKU",
    "admin", "president", "council",
    "🚀", "📋", "🛡️", "⚠️", "✅", "🗳️", "📅", "📊", "ℹ️"  # Emojis
]

# LM Studio configuration
LM_STUDIO_BASE_URL = "http://localhost:1234"
LM_STUDIO_CHAT_URL = f"{LM_STUDIO_BASE_URL}/v1/chat/completions"
LM_STUDIO_MODELS_URL = f"{LM_STUDIO_BASE_URL}/v1/models"
TRANSLATION_MODEL = "tencent.hunyuan-mt-7b"

# Target languages
LANGUAGES = {
    "es": "Spanish",
    "pt": "Portuguese",
    "de": "German",
    "ko": "Korean"
}

def check_lm_studio():
    """Check if LM Studio is running and accessible"""
    try:
        response = requests.get(LM_STUDIO_MODELS_URL, timeout=5)
        response.raise_for_status()
        print("✅ LM Studio is running")
        return True
    except requests.exceptions.ConnectionError:
        print("❌ ERROR: LM Studio is not running!")
        print("   Please start LM Studio and try again.")
        return False
    except Exception as e:
        print(f"❌ ERROR: Cannot connect to LM Studio: {e}")
        return False

def get_loaded_models():
    """Get list of currently loaded models in LM Studio"""
    try:
        response = requests.get(LM_STUDIO_MODELS_URL, timeout=5)
        response.raise_for_status()
        data = response.json()
        models = [model['id'] for model in data.get('data', [])]
        return models
    except Exception as e:
        print(f"⚠️  WARNING: Could not get loaded models: {e}")
        return []

def is_model_loaded(model_id):
    """Check if a specific model is loaded"""
    loaded_models = get_loaded_models()
    return model_id in loaded_models

def load_model(model_id):
    """Load a model in LM Studio"""
    print(f"📦 Loading model: {model_id}")
    print("   NOTE: This requires manual model loading in LM Studio.")
    print("   Please load the model in LM Studio and press Enter to continue...")
    input()

    # Verify it's loaded
    if is_model_loaded(model_id):
        print(f"✅ Model {model_id} is loaded")
        return True
    else:
        print(f"⚠️  WARNING: Could not verify model {model_id} is loaded")
        print("   Continuing anyway...")
        return True

def translate_text(text, target_language, preserve_terms):
    """Translate text using LM Studio with term preservation"""

    # Create glossary instruction
    glossary = ", ".join(preserve_terms[:15])  # Limit to first 15 to keep prompt short

    prompt = f"""Translate to {target_language}. Do not translate these terms: {glossary}

Text: {text}

Translation:"""

    payload = {
        "model": TRANSLATION_MODEL,
        "messages": [
            {"role": "system", "content": "You are a professional translator. Output ONLY the translated text. Never include instructions, rules, or lists of untranslated terms in your output."},
            {"role": "user", "content": prompt}
        ],
        "temperature": 0.1,
        "max_tokens": 2048
    }

    # Show what we're translating
    print(f"    [TRANSLATING] {text[:80]}..." if len(text) > 80 else f"    [TRANSLATING] {text}")

    try:
        response = requests.post(LM_STUDIO_CHAT_URL, json=payload, timeout=120)
        response.raise_for_status()
        result = response.json()
        translation = result['choices'][0]['message']['content'].strip()

        # Show the translation result
        print(f"    [RESULT] {translation[:80]}..." if len(translation) > 80 else f"    [RESULT] {translation}")

        # Clean up: Remove glossary instructions that were appended
        import re

        # More aggressive cleanup patterns for any glossary instructions
        # Match any line containing preservation instructions + tech terms
        glossary_patterns = [
            # Match full lines with preservation keywords + tech terms
            r'\n+.*?(?:sin cambios?|unchanged|mantienen|términos|elementos|변경|않|없음|마세요|항목|Mantenha|Mantém|inalterado|Unverändert|bleiben).*?(?:R5|R4|APE|Discord|SMTP|JWT|API).*$',
            # Match "Keep unchanged:" style instructions
            r'\n+\s*(?:Keep|Mantén|Mantenha|변경하지|保持).*?(?:unchanged|sin cambios|inalterado|않|不变).*?[:\-].*$',
            # Match lists of tech terms at end (multiple R5, Discord, etc.)
            r'\n+\s*.*?(?:R5|Discord).*?,.*?(?:R4|SMTP).*?,.*?(?:APE|JWT).*$',
            # Match Korean specific patterns (more aggressive)
            r'\n+.*?(?:변경|유지|마세요|않).*?(?:R5|Discord|SMTP).*$',
            # Match any line that's mostly technical terms and punctuation
            r'\n+\s*R5,\s*R4,\s*APE.*$',
        ]

        original_translation = translation
        for pattern in glossary_patterns:
            translation = re.sub(pattern, '', translation, flags=re.IGNORECASE | re.DOTALL | re.MULTILINE)

        # Additional cleanup: remove trailing/leading whitespace and empty lines
        translation = '\n'.join(line for line in translation.split('\n') if line.strip())
        translation = translation.strip()

        # Remove instruction text markers
        cleanup_markers = [
            "**Reglas importantes:**",
            "**Important Rules:**",
            "**Texto a traducir:**",
            "**Text to translate:**",
            "**Devuelve",
            "**Return",
            "IMPORTANT RULES:",
            "REGLAS IMPORTANTES:"
        ]

        for marker in cleanup_markers:
            if marker in translation:
                # This response contains instructions - extract just the translation
                lines = translation.split('\n')
                # Find lines that don't contain markers or asterisks
                clean_lines = []
                for line in lines:
                    line_strip = line.strip()
                    # Skip instruction lines
                    if any(m in line for m in cleanup_markers):
                        continue
                    if line_strip.startswith('*') and line_strip.endswith('*'):
                        continue
                    if line_strip and not line_strip.startswith('**') and not any(c.isdigit() and '.' in line_strip[:3] for c in line_strip[:3]):
                        clean_lines.append(line_strip)

                if clean_lines:
                    translation = ' '.join(clean_lines)
                    break

        # Final cleanup: remove asterisks and quotes
        translation = translation.replace('**', '').strip('*').strip('"').strip("'").strip()

        return translation

    except Exception as e:
        print(f"  [ERROR] Translation failed: {e}")
        return text  # Return original on error

def translate_value(value, target_language, preserve_terms):
    """Recursively translate JSON values"""
    if isinstance(value, str):
        # Skip if it's just a preserved term
        if value in preserve_terms:
            return value
        # Skip very short strings (likely codes/keys)
        if len(value) <= 3:
            return value
        # Translate
        return translate_text(value, target_language, preserve_terms)

    elif isinstance(value, dict):
        return {k: translate_value(v, target_language, preserve_terms) for k, v in value.items()}

    elif isinstance(value, list):
        return [translate_value(item, target_language, preserve_terms) for item in value]

    else:
        return value

def translate_section(section_name, section_data, target_language, preserve_terms):
    """Translate an entire JSON section"""
    print(f"  Translating section: {section_name}")
    return translate_value(section_data, target_language, preserve_terms)

def parse_args():
    """Parse command-line arguments"""
    parser = argparse.ArgumentParser(
        description="Translate admin UI to multiple languages using LM Studio",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python translate_admin_smart.py ko           # Translate only Korean
  python translate_admin_smart.py es pt        # Translate Spanish and Portuguese
  python translate_admin_smart.py              # Translate all languages

Available languages: es (Spanish), pt (Portuguese), de (German), ko (Korean)
        """
    )
    parser.add_argument(
        'languages',
        nargs='*',
        choices=list(LANGUAGES.keys()) + ['all'],
        help='Language codes to translate (default: all)'
    )
    return parser.parse_args()

def main():
    """Main translation process"""

    print("=" * 70)
    print("Smart Translation Script for Admin i18n")
    print("=" * 70)

    # Parse command-line arguments
    args = parse_args()

    # Determine which languages to translate
    if not args.languages or 'all' in args.languages:
        target_languages = LANGUAGES
        print("\n📋 Translating all languages")
    else:
        target_languages = {code: LANGUAGES[code] for code in args.languages}
        print(f"\n📋 Translating selected languages: {', '.join(args.languages)}")

    # Check if LM Studio is running
    print("\n🔍 Checking LM Studio...")
    if not check_lm_studio():
        print("\n💡 TIP: Start LM Studio and load the Hunyuan-MT-7B model first")
        sys.exit(1)

    # Check if translation model is loaded
    print(f"\n🔍 Checking for model: {TRANSLATION_MODEL}")
    loaded_models = get_loaded_models()

    if loaded_models:
        print(f"📦 Currently loaded models:")
        for model in loaded_models:
            print(f"   - {model}")

    if not is_model_loaded(TRANSLATION_MODEL):
        print(f"\n⚠️  Model {TRANSLATION_MODEL} is not loaded")
        load_model(TRANSLATION_MODEL)
    else:
        print(f"✅ Model {TRANSLATION_MODEL} is ready")

    # Load English translations
    en_file = Path("admin/i18n/en/translations.json")
    print(f"\n📖 Loading source file: {en_file}")

    try:
        with open(en_file, 'r', encoding='utf-8') as f:
            en_data = json.load(f)
    except FileNotFoundError:
        print(f"❌ ERROR: Source file not found: {en_file}")
        sys.exit(1)

    print(f"✅ Loaded {len(en_data)} top-level sections")
    print(f"🔒 Preserving {len(PRESERVE_TERMS)} special terms")
    print("=" * 70)

    # Translate to each language
    for lang_code, lang_name in target_languages.items():
        print(f"\n🌍 [{lang_code.upper()}] Translating to {lang_name}...")
        print("-" * 70)

        output_file = Path(f"admin/i18n/{lang_code}/translations.json")
        output_file.parent.mkdir(parents=True, exist_ok=True)

        # Translate each top-level section
        translated_data = {}
        total_sections = len(en_data)

        for idx, (section_name, section_data) in enumerate(en_data.items(), 1):
            print(f"\n  [{idx}/{total_sections}] Section: {section_name}")
            try:
                translated_data[section_name] = translate_section(
                    section_name,
                    section_data,
                    lang_name,
                    PRESERVE_TERMS
                )
                # Small delay to avoid overwhelming the API
                time.sleep(0.5)
            except Exception as e:
                print(f"  ❌ ERROR: Failed to translate {section_name}: {e}")
                translated_data[section_name] = section_data  # Keep original on error

        # Save translated file
        print(f"\n  💾 Saving to {output_file}...")
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(translated_data, f, indent=2, ensure_ascii=False)

        print(f"  ✅ Successfully saved {lang_code} translations")

    print("\n" + "=" * 70)
    print("✨ Translation complete!")
    print("=" * 70)
    print(f"\n📊 Translated {len(target_languages)} language(s)")
    print(f"💾 Output location: admin/i18n/[lang]/translations.json")
    print(f"\n💡 TIP: Model left loaded in LM Studio for future use")

if __name__ == '__main__':
    main()
