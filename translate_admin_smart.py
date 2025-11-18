#!/usr/bin/env python3
"""
Smart Translation Script for Admin i18n
Translates entire JSON sections at once with glossary preservation
"""

import json
import requests
import time
from pathlib import Path

# Terms that should NOT be translated
PRESERVE_TERMS = [
    "R5", "R4", "APE", "NAP15",
    "Discord", "SMTP", "JWT", "API",
    "UvvU", "ORCE", "MTOP", "FNXS", "MZKU",
    "admin", "president", "council",
    "🚀", "📋", "🛡️", "⚠️", "✅", "🗳️", "📅", "📊", "ℹ️"  # Emojis
]

# LM Studio API endpoint
LM_STUDIO_URL = "http://localhost:1234/v1/chat/completions"

# Target languages
LANGUAGES = {
    "es": "Spanish",
    "pt": "Portuguese",
    "de": "German",
    "ko": "Korean"
}

def translate_text(text, target_language, preserve_terms):
    """Translate text using LM Studio with term preservation"""

    # Create glossary instruction
    glossary = ", ".join(preserve_terms)

    prompt = f"""Translate ONLY this text to {target_language}: {text}

Keep unchanged: {glossary}"""

    payload = {
        "model": "tencent/hunyuan-mt-7b",
        "messages": [
            {"role": "system", "content": "You are a professional translator. Return ONLY the translated text with no explanations, no rules, no formatting - just the pure translation."},
            {"role": "user", "content": prompt}
        ],
        "temperature": 0.1,
        "max_tokens": 2048
    }

    try:
        response = requests.post(LM_STUDIO_URL, json=payload, timeout=120)
        response.raise_for_status()
        result = response.json()
        translation = result['choices'][0]['message']['content'].strip()

        # Clean up: Remove glossary instructions that were appended
        # Pattern: "Text\n\nMantén sin cambios: R5, R4..." or similar variations
        import re

        # Comprehensive pattern to match any glossary instruction
        # Matches: \n\n followed by any text mentioning "sin cambios", "unchanged", etc. until end of string
        glossary_pattern = r'\n\n.*?(?:sin cambios?|unchanged|que se mantienen|términos|elementos)[^.]*[:.][^\n]*(?:R5|Discord|SMTP|API).*$'

        translation = re.sub(glossary_pattern, '', translation, flags=re.IGNORECASE | re.DOTALL)

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

def main():
    """Main translation process"""

    # Load English translations
    en_file = Path("admin/i18n/en/translations.json")
    with open(en_file, 'r', encoding='utf-8') as f:
        en_data = json.load(f)

    print("=" * 60)
    print("Smart Translation Script")
    print("=" * 60)
    print(f"Loaded: {en_file}")
    print(f"Terms to preserve: {', '.join(PRESERVE_TERMS[:10])}...")
    print(f"Target languages: {', '.join(LANGUAGES.keys())}")
    print("=" * 60)

    # Translate to each language
    for lang_code, lang_name in LANGUAGES.items():
        print(f"\n[{lang_code.upper()}] Translating to {lang_name}...")

        output_file = Path(f"admin/i18n/{lang_code}/translations.json")
        output_file.parent.mkdir(parents=True, exist_ok=True)

        # Translate each top-level section
        translated_data = {}

        for section_name, section_data in en_data.items():
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
                print(f"  [ERROR] Failed to translate {section_name}: {e}")
                translated_data[section_name] = section_data  # Keep original on error

        # Save translated file
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(translated_data, f, indent=2, ensure_ascii=False)

        print(f"  [SUCCESS] Saved to {output_file}")

    print("\n" + "=" * 60)
    print("[COMPLETE] All translations finished!")
    print("=" * 60)

if __name__ == '__main__':
    main()
