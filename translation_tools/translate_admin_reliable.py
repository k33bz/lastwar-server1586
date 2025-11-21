#!/usr/bin/env python3
"""
Enterprise-Grade i18n Translation Script for Server 1586 Admin Interface
Internationalization (i18n) system with locale-aware translation management
Prioritizes correctness, reliability, and quality over speed

i18n FEATURES:
- Locale-aware translation management (ISO 639-1 language codes)
- Structured JSON translation keys with nested namespacing
- Preserve terms for technical/brand consistency across locales
- Quality validation with automatic retries
- Resume capability with checkpoints
- Comprehensive error handling and logging
- Configuration file support
- Preview and sampling modes
- 15 language locales with easy extensibility

USAGE:
    python translate_admin_reliable.py [options] [languages]

BASIC EXAMPLES:
    python translate_admin_reliable.py ko                    # Translate only Korean
    python translate_admin_reliable.py es pt de              # Translate specific languages
    python translate_admin_reliable.py                       # Translate all 15 languages
    python translate_admin_reliable.py --force es            # Force retranslation of Spanish

ADVANCED EXAMPLES:
    python translate_admin_reliable.py --preview es ko       # Preview without saving
    python translate_admin_reliable.py --sample 50 es        # Test with 50 strings only
    python translate_admin_reliable.py --namespace help es   # Translate only "help" section
    python translate_admin_reliable.py --namespace pages.dashboard es  # Specific subsection
    python translate_admin_reliable.py --max-retries 5 es    # Use 5 retry attempts
    python translate_admin_reliable.py --config my.json es   # Use custom configuration

SUPPORTED LOCALES (ISO 639-1):
    es (Spanish), pt (Portuguese), de (German), ko (Korean), fr (French), 
    it (Italian), ja (Japanese), zh (Chinese Simplified), ru (Russian), 
    ar (Arabic), nl (Dutch), pl (Polish), tr (Turkish), sv (Swedish), da (Danish)

i18n STRUCTURE:
    Source locale: en (English) - admin/i18n/en/translations.json
    Target locales: admin/i18n/{locale}/translations.json
    Namespace format: "section.subsection.key" (e.g., "help.president_approvals.title")
    Preserve terms: Technical terms maintained across all locales

REQUIREMENTS:
    - LM Studio running with translation model loaded
    - Python 3.7+ with requests library
    - Optional: tqdm for enhanced progress bars (pip install tqdm)

CONFIGURATION:
    Creates translate_config.json automatically with all i18n settings.
    
    Example translate_config.json:
    {
      "i18n": {
        "source_locale": "en",
        "default_namespace": "admin",
        "key_separator": ".",
        "fallback_locale": "en",
        "validate_placeholders": true,
        "validate_html_tags": true,
        "validate_preserve_terms": true
      },
      "model": "tencent.hunyuan-mt-7b",
      "temperature": 0.1,
      "max_tokens": 512,
      "max_retries": 3,
      "timeout": 30,
      "preserve_terms": ["R5", "R4", "APE", "Discord", "🚀"],
      "locales": {
        "es": "Spanish", "ko": "Korean", "ja": "Japanese"
      }
    }
    
    Modify this file to customize model, retries, preserve terms, locales, etc.

i18n OUTPUT STRUCTURE:
    - Source locale: admin/i18n/en/translations.json
    - Target locales: admin/i18n/{locale}/translations.json
    - Logs: logs/translation_YYYYMMDD_HHMMSS.log
    - Checkpoints: admin/i18n/.checkpoints/ (auto-cleanup on completion)
    - Configuration: translate_config.json (i18n settings)

For detailed documentation, see: TRANSLATION_GUIDE.md
"""

import json
import requests
import time
import sys
import argparse
import re
import logging
from pathlib import Path
from datetime import datetime

# Try to import tqdm for progress bars, fall back to simple progress if not available
try:
    from tqdm import tqdm
    HAS_TQDM = True
except ImportError:
    HAS_TQDM = False
    print("💡 TIP: Install tqdm for better progress bars: pip install tqdm")

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
    "R5", "R4", "APE", "NAP15",           # Game-specific roles/ranks
    "Discord", "SMTP", "JWT", "API",      # Technical systems
    "UvvU", "ORCE", "MTOP", "FNXS", "MZKU",  # Alliance tags/codes
    "admin",                              # System role (could be translated, but often kept as-is)
    "🚀", "📋", "🛡️", "⚠️", "✅", "🗳️", "📅", "📊", "ℹ️"  # UI emojis
]

# LM Studio configuration
LM_STUDIO_BASE_URL = "http://localhost:1234"
LM_STUDIO_CHAT_URL = f"{LM_STUDIO_BASE_URL}/v1/chat/completions"
LM_STUDIO_MODELS_URL = f"{LM_STUDIO_BASE_URL}/v1/models"
TRANSLATION_MODEL = "tencent.hunyuan-mt-7b"

# Target languages - easily extensible, just add new entries here
LANGUAGES = {
    "es": "Spanish",
    "pt": "Portuguese", 
    "de": "German",
    "ko": "Korean",
    "fr": "French",
    "it": "Italian", 
    "ja": "Japanese",
    "zh": "Chinese (Simplified)",
    "ru": "Russian",
    "ar": "Arabic",
    "nl": "Dutch",
    "pl": "Polish",
    "tr": "Turkish",
    "sv": "Swedish",
    "da": "Danish"
}

# Enhanced statistics tracking with quality metrics
class EnhancedStats:
    def __init__(self):
        self.total = 0
        self.completed = 0
        self.errors = 0
        self.warnings = 0
        self.skipped = 0
        self.api_calls = 0
        self.current_lang_completed = 0
        self.current_lang_total = 0
        self.total_languages = 0
        self.completed_languages = 0
        
        # Quality tracking
        self.translation_times = []
        self.quality_issues = []
        self.problematic_strings = []
        self.retry_attempts = 0
        self.html_tag_errors = 0
        self.contamination_errors = 0
    
    def progress_percent(self):
        if self.total == 0:
            return 0
        return (self.completed / self.total) * 100
    
    def lang_progress_percent(self):
        if self.current_lang_total == 0:
            return 0
        return (self.current_lang_completed / self.current_lang_total) * 100
    
    def overall_progress_percent(self):
        if self.total_languages == 0:
            return 0
        lang_progress = self.completed_languages / self.total_languages
        current_lang_progress = self.lang_progress_percent() / 100
        return ((lang_progress + current_lang_progress / self.total_languages) * 100)
    
    def reset_language(self):
        self.current_lang_completed = 0
        self.errors = 0
        self.warnings = 0
        self.skipped = 0
        self.api_calls = 0
        self.retry_attempts = 0
        self.html_tag_errors = 0
        self.contamination_errors = 0
    
    def add_translation_result(self, original, translation, duration, issues):
        """Record translation result with quality metrics"""
        self.translation_times.append(duration)
        if issues:
            self.quality_issues.extend(issues)
            self.problematic_strings.append({
                'original': original[:50] + "..." if len(original) > 50 else original,
                'translation': translation[:50] + "..." if len(translation) > 50 else translation,
                'issues': issues
            })
            
            # Count specific issue types
            for issue in issues:
                if "HTML" in issue:
                    self.html_tag_errors += 1
                elif "contamination" in issue.lower():
                    self.contamination_errors += 1
    
    def get_performance_report(self):
        """Generate performance summary"""
        if not self.translation_times:
            return {}
        
        return {
            'avg_time': sum(self.translation_times) / len(self.translation_times),
            'total_issues': len(self.quality_issues),
            'quality_rate': ((len(self.translation_times) - len(self.problematic_strings)) / len(self.translation_times)) * 100,
            'html_errors': self.html_tag_errors,
            'contamination_errors': self.contamination_errors,
            'retry_rate': (self.retry_attempts / self.api_calls) * 100 if self.api_calls > 0 else 0
        }

stats = EnhancedStats()

def setup_logging(log_level='INFO'):
    """Setup comprehensive logging"""
    log_dir = Path("logs")
    log_dir.mkdir(exist_ok=True)
    
    # Create timestamp for log file
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    log_file = log_dir / f"translation_{timestamp}.log"
    
    logging.basicConfig(
        level=getattr(logging, log_level),
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(log_file, encoding='utf-8'),
            logging.StreamHandler()
        ]
    )
    
    return log_file

def validate_translation(original, translation, target_language):
    """Comprehensive i18n quality validation for translations"""
    issues = []
    
    # Basic validation
    if not translation or not translation.strip():
        issues.append("Empty translation")
        return issues
    
    # Check if translation is identical to original (potential issue)
    if translation == original:
        if original not in PRESERVE_TERMS and len(original) > 10:
            # Allow some common words that might be the same across locales
            common_same_words = ["OK", "Email", "URL", "ID", "API", "JSON", "HTML", "CSS", "JavaScript"]
            if original not in common_same_words:
                issues.append("No translation provided (identical to original)")
    
    # HTML tag preservation check (critical for i18n)
    original_tags = re.findall(r'<[^>]+>', original)
    translation_tags = re.findall(r'<[^>]+>', translation)
    
    if original_tags:
        if set(original_tags) != set(translation_tags):
            issues.append(f"HTML tags not preserved: {original_tags} vs {translation_tags}")
    
    # i18n placeholder preservation (e.g., {variable}, {{placeholder}})
    original_placeholders = re.findall(r'\{[^}]+\}', original)
    translation_placeholders = re.findall(r'\{[^}]+\}', translation)
    
    if original_placeholders:
        if set(original_placeholders) != set(translation_placeholders):
            issues.append(f"i18n placeholders not preserved: {original_placeholders} vs {translation_placeholders}")
    
    # Check for instruction contamination
    contamination_patterns = [
        r'\btranslate\b', r'\btranslation\b', r'\bkeep unchanged\b', 
        r'\bpreserve\b', r'\bmaintain\b', r'\bdo not translate\b',
        target_language.lower(), r'\boutput only\b', r'\breturn\b'
    ]
    
    translation_lower = translation.lower()
    for pattern in contamination_patterns:
        if re.search(pattern, translation_lower):
            issues.append(f"Instruction contamination detected: '{pattern}'")
            break
    
    # Check for preserve terms being translated (i18n consistency)
    for term in PRESERVE_TERMS:
        if term in original and term not in translation:
            # Check if it was incorrectly translated
            issues.append(f"Preserve term '{term}' may have been translated (breaks i18n consistency)")
    
    # Check for excessive length (potential hallucination)
    if len(translation) > len(original) * 3:
        issues.append("Translation excessively long (potential hallucination)")
    
    # Check for repeated patterns (AI confusion)
    words = translation.split()
    if len(words) > 3:
        word_counts = {}
        for word in words:
            word_counts[word] = word_counts.get(word, 0) + 1
        
        # If any word appears more than 3 times in a short text, it's suspicious
        max_repeats = max(word_counts.values())
        if max_repeats > 3 and len(words) < 20:
            issues.append("Suspicious word repetition detected")
    
    # Locale-specific validation
    locale_specific_issues = validate_locale_specific(original, translation, target_language)
    issues.extend(locale_specific_issues)
    
    return issues

def validate_locale_specific(original, translation, target_language):
    """Validate locale-specific formatting and conventions"""
    issues = []
    
    # Check for proper quote marks by locale
    quote_conventions = {
        'French': ('«', '»'),
        'German': ('„', '"'),
        'Spanish': ('«', '»'),
        'Russian': ('«', '»')
    }
    
    if target_language in quote_conventions:
        expected_open, expected_close = quote_conventions[target_language]
        # Check if English quotes are used instead of locale-appropriate ones
        if '"' in translation and expected_open not in translation:
            issues.append(f"Consider using locale-appropriate quotes: {expected_open}...{expected_close}")
    
    # Check for proper number formatting hints
    if any(char.isdigit() for char in original):
        # Different locales use different decimal separators
        decimal_conventions = {
            'French': ',',
            'German': ',',
            'Spanish': ',',
            'Portuguese': ','
        }
        
        if target_language in decimal_conventions:
            # This is just a hint, not an error
            if '.' in translation and any(char.isdigit() for char in translation):
                issues.append(f"Note: {target_language} typically uses '{decimal_conventions[target_language]}' for decimals")
    
    return issues

def check_lm_studio():
    """Check if LM Studio is running and accessible"""
    try:
        response = requests.get(LM_STUDIO_MODELS_URL, timeout=5)
        response.raise_for_status()
        print("✅ LM Studio is running")
        return True
    except requests.exceptions.ConnectionError:
        print("❌ ERROR: LM Studio is not running!")
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

def load_model_via_api(model_id):
    """Load model using LM Studio API"""
    print(f"\n📦 Loading model: {model_id}")
    
    # LM Studio load model endpoint
    load_url = f"{LM_STUDIO_BASE_URL}/v1/models/load"
    
    payload = {
        "model": model_id
    }
    
    try:
        print("   🔄 Sending load request...")
        response = requests.post(load_url, json=payload, timeout=10)
        
        if response.status_code == 200:
            print("   ✅ Load request sent successfully")
            
            # Wait for model to actually load (can take time)
            print("   ⏳ Waiting for model to load...")
            max_wait = 60  # Wait up to 60 seconds
            
            for i in range(max_wait):
                time.sleep(1)
                if is_model_loaded(model_id):
                    print(f"   ✅ Model {model_id} loaded successfully!")
                    return True
                
                if i % 10 == 0 and i > 0:
                    print(f"   ⏳ Still loading... ({i}s)")
            
            print(f"   ⚠️  Model didn't load within {max_wait}s, but continuing...")
            return True
            
        else:
            print(f"   ❌ Load request failed: {response.status_code}")
            return False
            
    except Exception as e:
        print(f"   ❌ Failed to load model via API: {e}")
        return False

def unload_model_via_api():
    """Unload current model using LM Studio API"""
    print(f"\n📤 Unloading model to free GPU memory...")
    
    # LM Studio unload model endpoint
    unload_url = f"{LM_STUDIO_BASE_URL}/v1/models/unload"
    
    try:
        print("   🔄 Sending unload request...")
        response = requests.post(unload_url, timeout=10)
        
        if response.status_code == 200:
            print("   ✅ Model unloaded successfully!")
            print("   💾 GPU memory freed")
            return True
        else:
            print(f"   ⚠️  Unload request returned: {response.status_code}")
            print("   💡 You can manually unload in LM Studio if needed")
            return False
            
    except Exception as e:
        print(f"   ❌ Failed to unload via API: {e}")
        print("   💡 You can manually unload in LM Studio if needed")
        return False

def wait_for_model_load(model_id):
    """Load model automatically or wait for manual load"""
    print(f"\n📦 Model '{model_id}' is not loaded")
    
    # Try automatic loading first
    print("🤖 Attempting automatic model loading...")
    if load_model_via_api(model_id):
        return True
    
    # Fall back to manual loading
    print("\n🔧 Automatic loading failed, please load manually:")
    print("1. Open LM Studio")
    print("2. Go to 'My Models' tab")
    print(f"3. Load '{model_id}' or similar translation model")
    print("4. Wait for it to fully load")
    
    input("\nPress Enter when the model is loaded and ready...")
    
    # Verify it's loaded
    if is_model_loaded(model_id):
        print(f"✅ Model {model_id} is loaded and ready")
        return True
    else:
        # Check if any model is loaded
        loaded_models = get_loaded_models()
        if loaded_models:
            print(f"✅ Found loaded model: {loaded_models[0]}")
            print("   Continuing with available model...")
            return True
        else:
            print("❌ No model appears to be loaded")
            return False

def unload_model():
    """Unload model automatically or provide manual instructions"""
    print(f"\n💡 Translation complete!")
    
    # Try automatic unloading
    if not unload_model_via_api():
        # Fall back to manual instructions
        print("You can manually unload the model in LM Studio:")
        print("1. Go to LM Studio")
        print("2. Click 'Unload Model' to free up GPU memory")
        print("3. Or keep it loaded for future translations")

def translate_with_retry(text, target_language_name, max_retries=3):
    """Translate with exponential backoff retry and quality validation"""
    
    # Skip very short strings or preserved terms
    if len(text) <= 3 or text in PRESERVE_TERMS:
        stats.skipped += 1
        return text
    
    start_time = time.time()
    
    for attempt in range(max_retries):
        try:
            # Ultra-simple, clean prompt
            prompt = f'Translate to {target_language_name}: "{text}"'
            
            payload = {
                "model": TRANSLATION_MODEL,
                "messages": [
                    {"role": "system", "content": f"You are a translator. Translate the given text to {target_language_name}. Output ONLY the translation, nothing else. Keep technical terms like R5, R4, APE, Discord unchanged."},
                    {"role": "user", "content": prompt}
                ],
                "temperature": 0.1 + (attempt * 0.05),  # Slightly increase temperature on retries
                "max_tokens": 512
            }
            
            stats.api_calls += 1
            if attempt > 0:
                stats.retry_attempts += 1
                logging.warning(f"Retry attempt {attempt + 1} for: {text[:50]}...")
            
            response = requests.post(LM_STUDIO_CHAT_URL, json=payload, timeout=30)
            response.raise_for_status()
            result = response.json()
            translation = result['choices'][0]['message']['content'].strip()
            
            # Aggressive cleanup to remove any instruction leakage
            translation = clean_translation_response(translation, target_language_name)
            
            # Validate translation quality
            quality_issues = validate_translation(text, translation, target_language_name)
            
            # Record metrics
            duration = time.time() - start_time
            stats.add_translation_result(text, translation, duration, quality_issues)
            
            # If we have quality issues but this isn't our last attempt, retry
            if quality_issues and attempt < max_retries - 1:
                logging.warning(f"Quality issues detected, retrying: {quality_issues}")
                wait_time = (2 ** attempt) * 0.5  # Exponential backoff: 0.5s, 1s, 2s
                time.sleep(wait_time)
                continue
            
            # If we have issues on final attempt, log them but return result
            if quality_issues:
                stats.warnings += len(quality_issues)
                logging.warning(f"Final translation has issues: {quality_issues}")
            
            # Log successful translation
            if not quality_issues:
                logging.info(f"Successfully translated: '{text[:30]}...' -> '{translation[:30]}...'")
            
            return translation if translation else text
            
        except requests.exceptions.Timeout:
            logging.error(f"Timeout on attempt {attempt + 1} for: {text[:50]}...")
            if attempt < max_retries - 1:
                wait_time = (2 ** attempt) * 1.0  # Longer wait for timeouts
                time.sleep(wait_time)
                continue
        except requests.exceptions.RequestException as e:
            logging.error(f"Request error on attempt {attempt + 1}: {e}")
            if attempt < max_retries - 1:
                wait_time = (2 ** attempt) * 0.5
                time.sleep(wait_time)
                continue
        except Exception as e:
            logging.error(f"Unexpected error on attempt {attempt + 1}: {e}")
            if attempt < max_retries - 1:
                wait_time = (2 ** attempt) * 0.5
                time.sleep(wait_time)
                continue
    
    # All retries failed
    stats.errors += 1
    logging.error(f"All {max_retries} attempts failed for: {text[:50]}...")
    return text

def clean_translation_response(translation, target_language_name):
    """Clean up translation response to remove instruction leakage"""
    
    translation = translation.strip().strip('"').strip("'")
    
    # Remove common instruction patterns
    cleanup_patterns = [
        "translation:", "translate to", "in spanish:", "in korean:", "in german:", "in portuguese:",
        "spanish:", "korean:", "german:", "portuguese:", "french:", "italian:", "japanese:",
        "chinese:", "russian:", "arabic:", "dutch:", "polish:", "turkish:", "swedish:", "danish:",
        "translation is:", "the translation is:", "output:", "result:"
    ]
    
    for pattern in cleanup_patterns:
        if translation.lower().startswith(pattern):
            translation = translation[len(pattern):].strip().strip(':').strip()
    
    # If translation contains a colon and looks like "Language: translation"
    if ':' in translation and len(translation.split(':')) == 2:
        parts = translation.split(':', 1)
        language_indicators = [
            'spanish', 'korean', 'german', 'portuguese', 'french', 'italian', 
            'japanese', 'chinese', 'russian', 'arabic', 'dutch', 'polish', 
            'turkish', 'swedish', 'danish'
        ]
        if any(lang in parts[0].lower() for lang in language_indicators):
            translation = parts[1].strip().strip('"').strip("'")
    
    # Remove markdown formatting that sometimes appears
    translation = re.sub(r'\*\*(.*?)\*\*', r'\1', translation)  # **text** -> text
    translation = re.sub(r'\*(.*?)\*', r'\1', translation)      # *text* -> text
    
    # Remove quotes that wrap the entire translation
    if translation.startswith('"') and translation.endswith('"'):
        translation = translation[1:-1]
    if translation.startswith("'") and translation.endswith("'"):
        translation = translation[1:-1]
    
    return translation.strip()

def translate_single_string(text, target_language_name):
    """Wrapper for backward compatibility"""
    return translate_with_retry(text, target_language_name)

def get_value_at_path(data, path):
    """Get value from nested dict/list using dot notation path"""
    if not path:
        return data
    
    parts = path.split('.')
    current = data
    
    try:
        for part in parts:
            if '[' in part and ']' in part:
                # Handle array indices like "items[0]"
                key, index_part = part.split('[', 1)
                index = int(index_part.rstrip(']'))
                if key:
                    current = current[key][index]
                else:
                    current = current[index]
            else:
                current = current[part]
        return current
    except (KeyError, IndexError, TypeError, ValueError):
        return None

def set_value_at_path(data, path, value):
    """Set value in nested dict/list using dot notation path"""
    if not path:
        return value
    
    parts = path.split('.')
    current = data
    
    # Navigate to parent
    for part in parts[:-1]:
        if '[' in part and ']' in part:
            key, index_part = part.split('[', 1)
            index = int(index_part.rstrip(']'))
            if key:
                current = current[key][index]
            else:
                current = current[index]
        else:
            current = current[part]
    
    # Set the final value
    final_part = parts[-1]
    if '[' in final_part and ']' in final_part:
        key, index_part = final_part.split('[', 1)
        index = int(index_part.rstrip(']'))
        if key:
            current[key][index] = value
        else:
            current[index] = value
    else:
        current[final_part] = value

def translate_json_structure(en_data, existing_data, target_language_name, force_retranslate=False, path="", pbar_lang=None, pbar_overall=None, checkpoint_data=None, lang_code=None, sample_limit=None, namespace_filter=None):
    """Recursively translate JSON structure with checkpoints, sampling, and namespace filtering"""
    
    # Track completed paths for checkpointing
    completed_paths = checkpoint_data.get('completed_paths', []) if checkpoint_data else []
    
    if isinstance(en_data, str):
        stats.completed += 1
        stats.current_lang_completed += 1
        
        # Check sample limit
        if sample_limit and stats.current_lang_completed > sample_limit:
            return en_data  # Return original if we've hit sample limit
        
        # Update both progress bars if available
        if pbar_lang:
            pbar_lang.update(1)
        if pbar_overall:
            pbar_overall.update(1)
        
        # Check if this path was already completed (resume capability)
        if path in completed_paths:
            stats.skipped += 1
            existing_value = get_value_at_path(existing_data, path) if existing_data else en_data
            if pbar_lang:
                short_path = path.split('.')[-1] if '.' in path else path
                pbar_lang.set_postfix_str(f"↻ {short_path}: resumed")
            return existing_value
        
        # Check if translation already exists and is valid
        existing_value = get_value_at_path(existing_data, path) if existing_data else None
        
        if not force_retranslate and existing_value and existing_value != en_data:
            # Translation exists and is different from English - keep it
            stats.skipped += 1
            completed_paths.append(path)
            
            # Show in progress bar postfix
            if pbar_lang:
                short_path = path.split('.')[-1] if '.' in path else path
                pbar_lang.set_postfix_str(f"↻ {short_path}: {existing_value[:30]}...")
            
            return existing_value
        
        # Need to translate
        original_text = en_data[:40] + "..." if len(en_data) > 40 else en_data
        
        # Show what we're translating in progress bar
        if pbar_lang:
            short_path = path.split('.')[-1] if '.' in path else path
            pbar_lang.set_postfix_str(f"🔄 {short_path}: {original_text}")
        
        result = translate_single_string(en_data, target_language_name)
        
        # Mark path as completed
        completed_paths.append(path)
        
        # Save checkpoint every 50 translations
        if lang_code and len(completed_paths) % 50 == 0:
            checkpoint_stats = {
                'completed': stats.current_lang_completed,
                'errors': stats.errors,
                'warnings': stats.warnings,
                'api_calls': stats.api_calls
            }
            save_checkpoint(lang_code, completed_paths, checkpoint_stats)
        
        # Show result in progress bar
        if pbar_lang:
            result_display = result[:30] + "..." if len(result) > 30 else result
            if result != en_data:
                pbar_lang.set_postfix_str(f"✓ {short_path}: {result_display}")
            else:
                pbar_lang.set_postfix_str(f"= {short_path}: {result_display}")
        
        # Small delay to see the translation
        time.sleep(0.1)
        
        return result
        
    elif isinstance(en_data, dict):
        # For dicts, merge with existing structure
        result = {}
        for key, value in en_data.items():
            new_path = f"{path}.{key}" if path else key
            result[key] = translate_json_structure(
                value, existing_data, target_language_name, force_retranslate, new_path, 
                pbar_lang, pbar_overall, checkpoint_data, lang_code, sample_limit, namespace_filter
            )
        return result
    
    elif isinstance(en_data, list):
        # For lists, translate each item
        result = []
        for i, item in enumerate(en_data):
            new_path = f"{path}[{i}]"
            result.append(translate_json_structure(
                item, existing_data, target_language_name, force_retranslate, new_path, 
                pbar_lang, pbar_overall, checkpoint_data, lang_code, sample_limit, namespace_filter
            ))
        return result
    
    else:
        return en_data

def save_checkpoint(lang_code, completed_paths, stats_data):
    """Save progress checkpoint"""
    checkpoint_dir = Path("../.checkpoints")
    checkpoint_dir.mkdir(exist_ok=True)
    
    checkpoint_file = checkpoint_dir / f"checkpoint_{lang_code}.json"
    checkpoint_data = {
        'completed_paths': completed_paths,
        'timestamp': datetime.now().isoformat(),
        'stats': {
            'completed': stats_data['completed'],
            'errors': stats_data['errors'],
            'warnings': stats_data['warnings'],
            'api_calls': stats_data['api_calls']
        }
    }
    
    try:
        with open(checkpoint_file, 'w', encoding='utf-8') as f:
            json.dump(checkpoint_data, f, indent=2, ensure_ascii=False)
        logging.info(f"Checkpoint saved for {lang_code}: {len(completed_paths)} paths completed")
    except Exception as e:
        logging.error(f"Failed to save checkpoint: {e}")

def load_checkpoint(lang_code):
    """Load progress checkpoint"""
    checkpoint_file = Path(f"../.checkpoints/checkpoint_{lang_code}.json")
    
    if checkpoint_file.exists():
        try:
            with open(checkpoint_file, 'r', encoding='utf-8') as f:
                checkpoint_data = json.load(f)
                logging.info(f"Loaded checkpoint for {lang_code}: {len(checkpoint_data['completed_paths'])} paths completed")
                return checkpoint_data
        except Exception as e:
            logging.error(f"Failed to load checkpoint: {e}")
    
    return None

def cleanup_checkpoints(lang_code):
    """Remove checkpoint file after successful completion"""
    checkpoint_file = Path(f"../.checkpoints/checkpoint_{lang_code}.json")
    if checkpoint_file.exists():
        try:
            checkpoint_file.unlink()
            logging.info(f"Cleaned up checkpoint for {lang_code}")
        except Exception as e:
            logging.error(f"Failed to cleanup checkpoint: {e}")

def extract_namespace(data, namespace_path):
    """Extract a specific namespace from the JSON structure"""
    if not namespace_path:
        return data
    
    parts = namespace_path.split('.')
    current = data
    
    try:
        for part in parts:
            if isinstance(current, dict) and part in current:
                current = current[part]
            else:
                return None
        return current
    except (KeyError, TypeError):
        return None

def merge_namespace_translation(existing_data, namespace_path, translated_namespace):
    """Merge translated namespace back into the full data structure"""
    import copy
    
    # Start with existing data or empty dict
    result = copy.deepcopy(existing_data) if existing_data else {}
    
    # Navigate to the namespace location and set the translated data
    parts = namespace_path.split('.')
    current = result
    
    # Create nested structure if it doesn't exist
    for part in parts[:-1]:
        if part not in current:
            current[part] = {}
        current = current[part]
    
    # Set the translated namespace
    current[parts[-1]] = translated_namespace
    
    return result

def count_strings_in_structure(data, sample_limit=None, namespace_path=None):
    """Count total translatable strings in the JSON structure"""
    
    # Extract namespace if specified
    if namespace_path:
        data = extract_namespace(data, namespace_path)
        if data is None:
            return 0
    
    count = 0
    
    def count_recursive(value):
        nonlocal count
        if sample_limit and count >= sample_limit:
            return
        
        if isinstance(value, str):
            count += 1
        elif isinstance(value, dict):
            for v in value.values():
                count_recursive(v)
        elif isinstance(value, list):
            for item in value:
                count_recursive(item)
    
    count_recursive(data)
    return min(count, sample_limit) if sample_limit else count

def load_config():
    """Load i18n configuration from file with defaults"""
    config_file = Path("translate_config.json")
    
    default_config = {
        # i18n System Configuration
        "i18n": {
            "source_locale": "en",
            "default_namespace": "admin",
            "key_separator": ".",
            "fallback_locale": "en",
            "validate_placeholders": True,
            "validate_html_tags": True,
            "validate_preserve_terms": True
        },
        
        # Translation Engine Configuration  
        "model": "tencent.hunyuan-mt-7b",
        "temperature": 0.1,
        "max_tokens": 512,
        "max_retries": 3,
        "timeout": 30,
        "log_level": "INFO",
        "lm_studio_url": "http://localhost:1234",
        
        # i18n Preserve Terms (maintain across all locales)
        "preserve_terms": PRESERVE_TERMS,
        
        # Supported Locales (ISO 639-1 codes)
        "locales": LANGUAGES
    }
    
    if config_file.exists():
        try:
            with open(config_file, 'r', encoding='utf-8') as f:
                user_config = json.load(f)
                default_config.update(user_config)
                print(f"📄 Loaded configuration from {config_file}")
        except Exception as e:
            print(f"⚠️  Could not load config file: {e}")
            print("   Using default configuration")
    else:
        # Create default config file for user reference
        try:
            with open(config_file, 'w', encoding='utf-8') as f:
                json.dump(default_config, f, indent=2, ensure_ascii=False)
            print(f"📄 Created default config file: {config_file}")
        except Exception as e:
            print(f"⚠️  Could not create config file: {e}")
    
    return default_config

def main():
    """Main translation process"""
    
    start_time = datetime.now()
    
    # Load configuration first
    config = load_config()
    
    # Setup logging
    log_file = setup_logging(config['log_level'])
    
    print("=" * 80)
    print("🎯 ENTERPRISE TRANSLATION SCRIPT - Server 1586 Admin Interface")
    print("=" * 80)
    print(f"⏰ Started at: {start_time.strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"📝 Logging to: {log_file}")
    print(f"📄 Documentation: TRANSLATION_GUIDE.md")
    
    # Show current configuration summary
    print(f"\n🔧 Configuration Summary:")
    print(f"   Model: {config['model']}")
    print(f"   LM Studio: {config['lm_studio_url']}")
    print(f"   Max retries: {config['max_retries']}")
    print(f"   Temperature: {config['temperature']}")
    print(f"   Timeout: {config['timeout']}s")
    
    # Parse arguments with comprehensive help
    parser = argparse.ArgumentParser(
        description="Enterprise-grade translation script for Server 1586 admin interface",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
EXAMPLES:
  %(prog)s ko                           # Translate Korean only
  %(prog)s es pt de                     # Translate Spanish, Portuguese, German
  %(prog)s --force es                   # Force retranslation of Spanish
  %(prog)s --preview --sample 50 ko     # Preview 50 Korean translations
  %(prog)s --config prod.json es ko     # Use custom configuration
  %(prog)s --max-retries 5 es           # Use 5 retry attempts

SUPPORTED LANGUAGES:
  es (Spanish), pt (Portuguese), de (German), ko (Korean), fr (French),
  it (Italian), ja (Japanese), zh (Chinese), ru (Russian), ar (Arabic),
  nl (Dutch), pl (Polish), tr (Turkish), sv (Swedish), da (Danish)

FEATURES:
  • Quality validation with automatic retries
  • Resume capability with checkpoints  
  • Comprehensive error handling and logging
  • Configuration file support (translate_config.json)
  • Preview and sampling modes for testing

OUTPUT:
  • Files: admin/i18n/[lang]/translations.json
  • Logs: logs/translation_YYYYMMDD_HHMMSS.log
  • Config: translate_config.json (auto-created)

For detailed documentation: see TRANSLATION_GUIDE.md
        """
    )
    
    parser.add_argument(
        'languages', 
        nargs='*', 
        choices=list(config['languages'].keys()) + ['all'],
        help='Language codes to translate (default: all languages)'
    )
    
    parser.add_argument(
        '--force', 
        action='store_true', 
        help='Force retranslation of all strings (ignores existing translations)'
    )
    
    parser.add_argument(
        '--incremental', 
        action='store_true', 
        default=True, 
        help='Only translate missing/new strings (default behavior)'
    )
    
    parser.add_argument(
        '--config', 
        metavar='FILE',
        help='Path to custom configuration file (default: translate_config.json)'
    )
    
    parser.add_argument(
        '--max-retries', 
        type=int, 
        default=config['max_retries'], 
        metavar='N',
        help=f'Maximum retry attempts per string (default: {config["max_retries"]})'
    )
    
    parser.add_argument(
        '--preview', 
        action='store_true', 
        help='Preview mode - show translations without saving files'
    )
    
    parser.add_argument(
        '--sample', 
        type=int, 
        metavar='N',
        help='Translate only N sample strings for testing'
    )
    
    parser.add_argument(
        '--namespace', 
        type=str, 
        metavar='PATH',
        help='Translate only specific namespace (e.g., "help", "pages.dashboard", "buttons")'
    )
    
    args = parser.parse_args()
    
    # Load custom config if specified
    if args.config:
        custom_config_file = Path(args.config)
        if custom_config_file.exists():
            with open(custom_config_file, 'r', encoding='utf-8') as f:
                custom_config = json.load(f)
                config.update(custom_config)
                print(f"📄 Loaded custom config from {custom_config_file}")
    
    # Update global variables from config
    global TRANSLATION_MODEL, LM_STUDIO_BASE_URL, LM_STUDIO_CHAT_URL, LM_STUDIO_MODELS_URL, PRESERVE_TERMS
    TRANSLATION_MODEL = config['model']
    LM_STUDIO_BASE_URL = config['lm_studio_url']
    LM_STUDIO_CHAT_URL = f"{LM_STUDIO_BASE_URL}/v1/chat/completions"
    LM_STUDIO_MODELS_URL = f"{LM_STUDIO_BASE_URL}/v1/models"
    PRESERVE_TERMS = config['preserve_terms']
    
    logging.info(f"Translation session started with model: {TRANSLATION_MODEL}")
    
    # Determine target locales for i18n
    available_locales = config.get('locales', config.get('languages', LANGUAGES))
    if not args.languages or 'all' in args.languages:
        target_languages = available_locales
    else:
        target_languages = {code: available_locales[code] for code in args.languages}
        
    # Validate locale codes
    invalid_locales = [code for code in args.languages if code not in available_locales and code != 'all']
    if invalid_locales:
        print(f"❌ ERROR: Invalid locale codes: {', '.join(invalid_locales)}")
        print(f"   Available locales: {', '.join(available_locales.keys())}")
        sys.exit(1)
    
    if args.preview:
        print("🔍 PREVIEW MODE - No files will be saved")
        logging.info("Running in preview mode")
    
    print(f"\n🌍 i18n Configuration:")
    print(f"   Source locale: {config.get('i18n', {}).get('source_locale', 'en')}")
    print(f"   Target locales: {', '.join(target_languages.keys())}")
    print(f"   Locale directory: admin/i18n/")
    print(f"   Preserve terms: {len(config.get('preserve_terms', PRESERVE_TERMS))} terms")
    
    # Check LM Studio
    if not check_lm_studio():
        sys.exit(1)
    
    # Check if translation model is loaded
    print(f"\n🔍 Checking for model: {TRANSLATION_MODEL}")
    loaded_models = get_loaded_models()
    
    if loaded_models:
        print(f"📦 Currently loaded models:")
        for model in loaded_models:
            print(f"   - {model}")
    
    model_was_loaded = is_model_loaded(TRANSLATION_MODEL)
    
    if not model_was_loaded:
        if not wait_for_model_load(TRANSLATION_MODEL):
            print("❌ Cannot proceed without a loaded model")
            sys.exit(1)
    else:
        print(f"✅ Model {TRANSLATION_MODEL} is ready")
    
    # Load and validate English source
    en_file = Path("../admin/i18n/en/translations.json")
    try:
        with open(en_file, 'r', encoding='utf-8') as f:
            en_data = json.load(f)
        
        # Validate source file structure
        if not isinstance(en_data, dict):
            print(f"❌ ERROR: Source file must contain a JSON object")
            sys.exit(1)
        
        if not en_data:
            print(f"❌ ERROR: Source file is empty")
            sys.exit(1)
            
        print(f"✅ Loaded source file: {en_file}")
        logging.info(f"Loaded English source with {len(en_data)} top-level sections")
        
    except FileNotFoundError:
        print(f"❌ ERROR: Source file not found: {en_file}")
        print(f"   Expected location: {en_file.absolute()}")
        print(f"   Please ensure the English translations file exists")
        sys.exit(1)
    except json.JSONDecodeError as e:
        print(f"❌ ERROR: Invalid JSON in source file: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"❌ ERROR: Could not load source file: {e}")
        sys.exit(1)
    
    # Handle namespace filtering
    namespace_data = en_data
    if args.namespace:
        namespace_data = extract_namespace(en_data, args.namespace)
        if namespace_data is None:
            print(f"❌ ERROR: Namespace '{args.namespace}' not found in source file")
            print(f"   Available top-level namespaces: {', '.join(en_data.keys())}")
            sys.exit(1)
        print(f"🎯 Filtering to namespace: {args.namespace}")
        logging.info(f"Filtering translation to namespace: {args.namespace}")
    
    # Count total strings and setup overall progress
    total_strings = count_strings_in_structure(namespace_data, args.sample)
    stats.total = total_strings
    stats.total_languages = len(target_languages)
    stats.current_lang_total = total_strings
    
    namespace_info = f" in namespace '{args.namespace}'" if args.namespace else ""
    sample_info = f" (sample of {args.sample})" if args.sample else ""
    print(f"📊 Found {total_strings} translatable i18n keys{namespace_info}{sample_info}")
    print(f"🌍 Localizing to {len(target_languages)} locales")
    print(f"⚡ Method: Individual API calls with i18n quality validation (max {args.max_retries} attempts)")
    print(f"🔧 Translation engine: {config['model']} @ {config['lm_studio_url']}")
    
    if not HAS_TQDM:
        print("💡 For better progress bars, install: pip install tqdm")
        print("   (Will use simple progress indicators for now)")
    
    logging.info(f"Starting translation of {total_strings} strings to {len(target_languages)} languages")
    
    # Create overall progress bar if tqdm available
    overall_pbar = None
    if HAS_TQDM:
        total_operations = stats.total * len(target_languages)
        overall_pbar = tqdm(
            total=total_operations,
            desc="🌐 Overall",
            unit="strings",
            position=0,
            bar_format="{l_bar}{bar}| {n_fmt}/{total_fmt} [{elapsed}<{remaining}] {postfix}",
            colour="blue"
        )
    
    # Translate each language
    for lang_index, (lang_code, lang_name) in enumerate(target_languages.items()):
        print(f"\n🌍 Translating to {lang_name} ({lang_code})...")
        print("-" * 60)
        
        # Reset progress for this language
        stats.reset_language()
        
        # Load existing translations if they exist
        output_file = Path(f"../admin/i18n/{lang_code}/translations.json")
        existing_data = None
        
        if output_file.exists() and not args.force:
            try:
                with open(output_file, 'r', encoding='utf-8') as f:
                    existing_data = json.load(f)
                if not HAS_TQDM:
                    print(f"    📂 Loaded existing translations from {output_file}")
                logging.info(f"Loaded existing translations for {lang_code}")
            except Exception as e:
                if not HAS_TQDM:
                    print(f"    ⚠️  Could not load existing file: {e}")
                logging.error(f"Failed to load existing translations for {lang_code}: {e}")
                existing_data = None
        
        # Load checkpoint if available
        checkpoint_data = load_checkpoint(lang_code)
        if checkpoint_data and not args.force:
            if not HAS_TQDM:
                print(f"    🔄 Resuming from checkpoint: {len(checkpoint_data['completed_paths'])} paths completed")
            logging.info(f"Resuming {lang_code} from checkpoint")
        
        if not HAS_TQDM:
            if args.force:
                print(f"    🔄 Force mode: Retranslating all strings")
            elif existing_data:
                print(f"    ⚡ Incremental mode: Only translating new/missing strings")
            else:
                print(f"    🆕 New translation: No existing file found")
        
        lang_start_time = datetime.now()
        
        # Update overall progress bar
        if overall_pbar:
            overall_pbar.set_postfix_str(f"Lang {lang_index + 1}/{len(target_languages)}: {lang_name}")
        
        # Prepare data for translation (handle namespace filtering)
        source_data = namespace_data if args.namespace else en_data
        
        # Translate with dual progress tracking
        if HAS_TQDM:
            # Create language-specific progress bar
            with tqdm(
                total=stats.total,
                desc=f"🌍 {lang_name}",
                unit="strings",
                position=1,
                bar_format="{l_bar}{bar}| {n_fmt}/{total_fmt} [{elapsed}<{remaining}] {postfix}",
                colour="green",
                leave=False
            ) as lang_pbar:
                if args.namespace:
                    # Translate only the namespace portion
                    translated_namespace = translate_json_structure(
                        source_data, existing_data, lang_name, args.force, 
                        path=args.namespace, pbar_lang=lang_pbar, pbar_overall=overall_pbar,
                        checkpoint_data=checkpoint_data, lang_code=lang_code, sample_limit=args.sample
                    )
                    # Merge with existing data structure
                    translated_data = merge_namespace_translation(existing_data or {}, args.namespace, translated_namespace)
                else:
                    # Translate entire structure
                    translated_data = translate_json_structure(
                        source_data, existing_data, lang_name, args.force, 
                        pbar_lang=lang_pbar, pbar_overall=overall_pbar,
                        checkpoint_data=checkpoint_data, lang_code=lang_code, sample_limit=args.sample
                    )
        else:
            # Fallback to simple progress
            if args.namespace:
                translated_namespace = translate_json_structure(
                    source_data, existing_data, lang_name, args.force,
                    path=args.namespace, checkpoint_data=checkpoint_data, lang_code=lang_code, sample_limit=args.sample
                )
                translated_data = merge_namespace_translation(existing_data or {}, args.namespace, translated_namespace)
            else:
                translated_data = translate_json_structure(
                    source_data, existing_data, lang_name, args.force,
                    checkpoint_data=checkpoint_data, lang_code=lang_code, sample_limit=args.sample
                )
        
        lang_end_time = datetime.now()
        lang_duration = (lang_end_time - lang_start_time).total_seconds()
        
        # Save results (unless in preview mode)
        if not args.preview:
            # Create output directory and save
            output_file.parent.mkdir(parents=True, exist_ok=True)
            
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(translated_data, f, indent=2, ensure_ascii=False)
            
            file_size = output_file.stat().st_size
            
            # Clean up checkpoint after successful completion
            cleanup_checkpoints(lang_code)
            
            logging.info(f"Successfully completed {lang_name} translation: {file_size:,} bytes")
        else:
            file_size = 0
            logging.info(f"Preview mode: {lang_name} translation completed but not saved")
        
        # Update completed languages counter
        stats.completed_languages += 1
        
        # Get performance report
        performance = stats.get_performance_report()
        
        # Language summary
        if not HAS_TQDM:
            print(f"\n✅ {lang_name} Complete!" + (" (Preview - Not Saved)" if args.preview else ""))
            if not args.preview:
                print(f"   📄 File: {output_file} ({file_size:,} bytes)")
            print(f"   ⏱️  Time: {lang_duration:.1f} seconds")
            print(f"   📊 Translated: {stats.current_lang_completed - stats.skipped - stats.errors}")
            print(f"   ⏭️  Skipped: {stats.skipped} (short/preserved)")
            print(f"   ⚠️  Warnings: {stats.warnings}")
            print(f"   ❌ Errors: {stats.errors}")
            print(f"   🔄 API calls: {stats.api_calls}")
            if stats.retry_attempts > 0:
                print(f"   🔁 Retries: {stats.retry_attempts}")
            
            if performance and 'quality_rate' in performance:
                print(f"   🎯 Quality rate: {performance['quality_rate']:.1f}%")
            
            if stats.api_calls > 0:
                avg_time = lang_duration / stats.api_calls
                print(f"   ⚡ Avg per call: {avg_time:.2f}s")
        else:
            # Update overall progress bar with completion info
            if overall_pbar:
                quality_info = f"Q:{performance.get('quality_rate', 0):.0f}%" if performance else ""
                overall_pbar.set_postfix_str(f"✅ {lang_name} done! ({stats.warnings}W, {stats.errors}E, {quality_info})")
    
    # Close overall progress bar
    if overall_pbar:
        overall_pbar.close()
    
    # Final summary
    end_time = datetime.now()
    total_duration = (end_time - start_time).total_seconds()
    
    print(f"\n" + "=" * 70)
    print("✨ All Translations Complete!" + (" (Preview Mode)" if args.preview else ""))
    print("=" * 70)
    print(f"⏰ Total time: {total_duration:.1f} seconds")
    print(f"📊 Languages: {len(target_languages)}")
    if not args.preview:
        print(f"💾 Files saved to: admin/i18n/[lang]/translations.json")
    print(f"🎯 Method: Individual calls with retry logic")
    
    # Comprehensive statistics
    final_performance = stats.get_performance_report()
    
    print(f"\n📈 Overall Statistics:")
    print(f"   ✅ Total strings processed: {stats.completed:,}")
    print(f"   ⚠️  Total warnings: {stats.warnings}")
    print(f"   ❌ Total errors: {stats.errors}")
    print(f"   🔁 Total retries: {stats.retry_attempts}")
    print(f"   🔄 Total API calls: {stats.api_calls}")
    
    if final_performance:
        print(f"   🎯 Overall quality rate: {final_performance.get('quality_rate', 0):.1f}%")
        if final_performance.get('avg_time'):
            print(f"   ⚡ Average time per call: {final_performance['avg_time']:.2f}s")
        if final_performance.get('html_errors', 0) > 0:
            print(f"   🏷️  HTML tag errors: {final_performance['html_errors']}")
        if final_performance.get('contamination_errors', 0) > 0:
            print(f"   🧹 Contamination errors: {final_performance['contamination_errors']}")
    
    # Log problematic strings if any
    if stats.problematic_strings:
        print(f"\n⚠️  Found {len(stats.problematic_strings)} problematic translations")
        print("   Check logs for details")
        
        # Log detailed issues
        logging.warning(f"Problematic translations summary:")
        for issue in stats.problematic_strings[:10]:  # Log first 10
            logging.warning(f"  Original: {issue['original']}")
            logging.warning(f"  Translation: {issue['translation']}")
            logging.warning(f"  Issues: {', '.join(issue['issues'])}")
    
    # Success rate calculation
    if stats.completed > 0:
        success_rate = ((stats.completed - stats.warnings - stats.errors) / stats.completed) * 100
        print(f"   📊 Overall success rate: {success_rate:.1f}%")
    
    logging.info(f"Translation session completed. Total time: {total_duration:.1f}s")
    
    # Offer to unload model if we loaded it
    if not model_was_loaded:
        unload_model()

if __name__ == '__main__':
    main()