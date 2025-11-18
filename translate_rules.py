#!/usr/bin/env python3
import json
import requests
import sys
import time

def translate_text(text, target_lang):
    """Translate text using LM Studio API"""
    lang_names = {
        'es': 'Spanish',
        'pt': 'Portuguese',
        'ko': 'Korean',
        'de': 'German'
    }

    url = 'http://localhost:1234/v1/chat/completions'
    headers = {'Content-Type': 'application/json'}

    # Hunyuan-MT models work best with simple, direct instructions
    system_prompt = f'You are a professional translator. Translate the following text from English to {lang_names[target_lang]}. Preserve HTML tags like <strong> exactly as they are. Only output the translation, nothing else.'
    max_tokens = 500
    temperature = 0.3

    payload = {
        'messages': [
            {
                'role': 'system',
                'content': system_prompt
            },
            {
                'role': 'user',
                'content': text
            }
        ],
        'temperature': temperature,
        'max_tokens': max_tokens
    }

    try:
        response = requests.post(url, headers=headers, json=payload, timeout=30)
        response.raise_for_status()
        result = response.json()
        return result['choices'][0]['message']['content'].strip()
    except Exception as e:
        print(f"Error translating: {e}", file=sys.stderr)
        return text

def translate_rules(input_file, output_file, target_lang):
    """Translate entire rules.json file"""
    with open(input_file, 'r', encoding='utf-8') as f:
        rules = json.load(f)

    translated_rules = []
    total = len(rules)

    for idx, section in enumerate(rules):
        print(f"Translating section {idx+1}/{total}: {section['title']}", file=sys.stderr)

        translated_section = {}

        # Translate title
        translated_section['title'] = translate_text(section['title'], target_lang)
        time.sleep(0.5)  # Rate limiting

        # Translate content if exists
        if 'content' in section:
            translated_section['content'] = []
            for content_item in section['content']:
                translated = translate_text(content_item, target_lang)
                translated_section['content'].append(translated)
                time.sleep(0.5)

        # Translate items if exists
        if 'items' in section:
            translated_section['items'] = []
            for item in section['items']:
                translated = translate_text(item, target_lang)
                translated_section['items'].append(translated)
                time.sleep(0.5)

        # Copy type if exists
        if 'type' in section:
            translated_section['type'] = section['type']

        translated_rules.append(translated_section)

    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(translated_rules, f, ensure_ascii=False, indent=4)

    print(f"Translated rules saved to {output_file}", file=sys.stderr)

if __name__ == '__main__':
    if len(sys.argv) != 4:
        print("Usage: python translate_rules.py <input_file> <output_file> <target_lang>")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]
    target_lang = sys.argv[3]

    translate_rules(input_file, output_file, target_lang)
