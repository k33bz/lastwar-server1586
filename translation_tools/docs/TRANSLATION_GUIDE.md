# Translation Script Guide

## Overview

The `translate_admin_reliable.py` script is an enterprise-grade translation tool designed for the Server 1586 admin interface. It prioritizes **correctness and reliability** over speed, featuring automatic quality validation, retry logic, and comprehensive error handling.

## 🚀 Quick Start

### Prerequisites

1. **LM Studio** running with a translation model loaded
2. **Python 3.7+** with the `requests` library
3. **Optional**: `tqdm` for enhanced progress bars (`pip install tqdm`)

### Basic Usage

```bash
# Translate all languages (15 total)
python translate_admin_reliable.py

# Translate specific languages
python translate_admin_reliable.py es ko ja

# Force retranslation (ignore existing files)
python translate_admin_reliable.py --force es pt
```

## 📋 Command Line Options

### Language Selection
```bash
# Single language
python translate_admin_reliable.py ko

# Multiple languages  
python translate_admin_reliable.py es pt de fr

# All languages (default)
python translate_admin_reliable.py
python translate_admin_reliable.py all
```

### Translation Modes
```bash
# Incremental mode (default) - only translate new/missing strings
python translate_admin_reliable.py es

# Force mode - retranslate everything
python translate_admin_reliable.py --force es

# Preview mode - show translations without saving
python translate_admin_reliable.py --preview es

# Sample mode - translate only N strings for testing
python translate_admin_reliable.py --sample 50 es

# Namespace mode - translate specific sections only
python translate_admin_reliable.py --namespace help es
python translate_admin_reliable.py --namespace pages.dashboard es
```

### Configuration Options
```bash
# Use custom configuration file
python translate_admin_reliable.py --config my_config.json es

# Override retry attempts
python translate_admin_reliable.py --max-retries 5 es

# Combine options
python translate_admin_reliable.py --preview --sample 25 --max-retries 2 ko ja

# Namespace filtering
python translate_admin_reliable.py --namespace help --preview es ko
python translate_admin_reliable.py --namespace pages.dashboard --force es
```

## ⚙️ Configuration

### Automatic Configuration

The script automatically creates `translate_config.json` with default i18n settings:

```json
{
  "i18n": {
    "source_locale": "en",
    "default_namespace": "admin",
    "key_separator": ".",
    "fallback_locale": "en",
    "validate_placeholders": true,
    "validate_html_tags": true,
    "validate_preserve_terms": true,
    "locale_conventions": {
      "quote_marks": true,
      "number_format": true,
      "date_format": true
    }
  },
  "model": "tencent.hunyuan-mt-7b",
  "temperature": 0.1,
  "max_tokens": 512,
  "max_retries": 3,
  "timeout": 30,
  "log_level": "INFO",
  "lm_studio_url": "http://localhost:1234",
  "preserve_terms": [
    "R5", "R4", "APE", "NAP15",
    "Discord", "SMTP", "JWT", "API",
    "UvvU", "ORCE", "MTOP", "FNXS", "MZKU",
    "admin",
    "🚀", "📋", "🛡️", "⚠️", "✅", "🗳️", "📅", "📊", "ℹ️"
  ],
  "locales": {
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
}
```

### Configuration Options Explained

#### i18n Section
- **`source_locale`**: Base locale for translations (default: "en")
- **`default_namespace`**: Default namespace for i18n keys (default: "admin")
- **`key_separator`**: Separator for nested keys (default: ".")
- **`fallback_locale`**: Fallback when translations are missing (default: "en")
- **`validate_placeholders`**: Check {variable} preservation (default: true)
- **`validate_html_tags`**: Check HTML tag preservation (default: true)
- **`validate_preserve_terms`**: Check preserve terms consistency (default: true)

#### Translation Engine
- **`model`**: LM Studio model name for translation
- **`temperature`**: AI creativity (0.0-1.0, lower = more consistent)
- **`max_tokens`**: Maximum response length per translation
- **`max_retries`**: Retry attempts for failed/poor translations
- **`timeout`**: Request timeout in seconds

#### Locale Management
- **`preserve_terms`**: Terms that should never be translated
- **`locales`**: Supported locale codes and display names

### Custom Configuration

Create your own config file and use it:

```bash
python translate_admin_reliable.py --config production_config.json es ko
```

### Configuration Examples

#### Development Configuration (`dev_config.json`)
```json
{
  "i18n": {
    "source_locale": "en",
    "validate_placeholders": true,
    "validate_html_tags": true
  },
  "model": "tencent.hunyuan-mt-7b",
  "temperature": 0.2,
  "max_retries": 2,
  "timeout": 20,
  "log_level": "DEBUG",
  "locales": {
    "es": "Spanish",
    "ko": "Korean"
  }
}
```

#### Production Configuration (`prod_config.json`)
```json
{
  "i18n": {
    "source_locale": "en",
    "fallback_locale": "en",
    "validate_placeholders": true,
    "validate_html_tags": true,
    "validate_preserve_terms": true,
    "locale_conventions": {
      "quote_marks": true,
      "number_format": true
    }
  },
  "model": "tencent.hunyuan-mt-7b",
  "temperature": 0.1,
  "max_retries": 5,
  "timeout": 60,
  "log_level": "INFO",
  "preserve_terms": [
    "R5", "R4", "APE", "NAP15", "Discord", "SMTP", "JWT", "API",
    "UvvU", "ORCE", "MTOP", "FNXS", "MZKU", "admin",
    "🚀", "📋", "🛡️", "⚠️", "✅", "🗳️", "📅", "📊", "ℹ️"
  ],
  "locales": {
    "es": "Spanish", "pt": "Portuguese", "de": "German", "ko": "Korean",
    "fr": "French", "it": "Italian", "ja": "Japanese", "zh": "Chinese (Simplified)",
    "ru": "Russian", "ar": "Arabic", "nl": "Dutch", "pl": "Polish",
    "tr": "Turkish", "sv": "Swedish", "da": "Danish"
  }
}
```

#### High-Performance Configuration (`fast_config.json`)
```json
{
  "i18n": {
    "source_locale": "en",
    "validate_placeholders": false,
    "validate_html_tags": true,
    "validate_preserve_terms": true
  },
  "model": "tencent.hunyuan-mt-7b",
  "temperature": 0.15,
  "max_retries": 1,
  "timeout": 15,
  "max_tokens": 256,
  "log_level": "WARNING"
}
```

## 🎯 Key Features

### 1. Namespace Filtering

Target specific sections of your i18n structure for translation:

```bash
# Translate entire help system
python translate_admin_reliable.py --namespace help es ko

# Translate specific dashboard section
python translate_admin_reliable.py --namespace pages.dashboard es pt

# Translate button labels only
python translate_admin_reliable.py --namespace buttons es ko ja

# Nested namespace support
python translate_admin_reliable.py --namespace help.president_approvals es
```

**Benefits:**
- **Faster iteration** - translate only changed sections
- **Reduced API calls** - focus on specific areas
- **Preserve existing work** - keeps other translations intact
- **Development workflow** - perfect for feature-based translation

**Use Cases:**
- New feature development: `--namespace features.new_voting`
- Bug fixes: `--namespace errors.validation --force`
- UI updates: `--namespace pages.dashboard`
- Content updates: `--namespace help.user_guide`

### 2. Quality Validation

The script automatically validates each translation for:

- **HTML tag preservation** - Ensures `<strong>`, `<em>` tags are maintained
- **Instruction contamination** - Detects AI responses that include instructions
- **Preserve term handling** - Verifies technical terms remain unchanged
- **Length validation** - Catches potential AI hallucinations
- **Word repetition** - Identifies confused AI responses

### 2. Automatic Retry Logic

When quality issues are detected:

- **Attempt 1**: Temperature 0.1, retry after 0.5s if issues found
- **Attempt 2**: Temperature 0.15, retry after 1.0s if issues found  
- **Attempt 3**: Temperature 0.2, accept result (log warnings if issues remain)

### 3. Resume Capability

- **Automatic checkpoints** every 50 translations
- **Graceful resume** if script is interrupted
- **Path-based tracking** remembers exactly which strings were completed
- **Auto-cleanup** removes checkpoints after successful completion

### 4. Comprehensive Logging

All activities are logged to `logs/translation_YYYYMMDD_HHMMSS.log`:

- Translation attempts and results
- Quality issues and retry decisions
- Performance metrics and timing
- Error details and recovery actions

## 📊 Progress Tracking

### With tqdm (Recommended)

Install for enhanced progress bars:
```bash
pip install tqdm
```

You'll see dual progress bars:
```
🌐 Overall: 45%|████▌     | 2730/6075 [1:32:15<2:05:30] Lang 3/4: German
🌍 German:  78%|███████▊  | 316/405 [12:45<03:35] ✓ title: Hilfe zur Präsidenten...
```

### Without tqdm

Simple text-based progress updates:
```
📊 50/405 (12.3%) - help.president_approvals.sections.overview.intro
        ✓ Como Presidente del servidor, es su responsabilidad...
```

## 🗂️ File Structure

### Input
```
admin/i18n/en/translations.json    # Source English file
```

### Output
```
admin/i18n/
├── es/translations.json           # Spanish
├── pt/translations.json           # Portuguese  
├── de/translations.json           # German
├── ko/translations.json           # Korean
├── fr/translations.json           # French
├── it/translations.json           # Italian
├── ja/translations.json           # Japanese
├── zh/translations.json           # Chinese (Simplified)
├── ru/translations.json           # Russian
├── ar/translations.json           # Arabic
├── nl/translations.json           # Dutch
├── pl/translations.json           # Polish
├── tr/translations.json           # Turkish
├── sv/translations.json           # Swedish
└── da/translations.json           # Danish
```

### Generated Files
```
translate_config.json              # Configuration (auto-created)
logs/translation_YYYYMMDD_HHMMSS.log  # Detailed logs
admin/i18n/.checkpoints/           # Resume checkpoints (auto-cleanup)
```

## 🔧 Troubleshooting

### Common Issues

#### LM Studio Not Running
```
❌ ERROR: LM Studio is not running!
```
**Solution**: Start LM Studio and ensure it's accessible at `http://localhost:1234`

#### Model Not Loaded
```
📦 Model 'tencent.hunyuan-mt-7b' is not loaded
```
**Solution**: The script will attempt to load automatically, or follow manual instructions

#### High Warning Rate
```
⚠️  Total warnings: 150
🎯 Overall quality rate: 75.2%
```
**Solutions**:
- Increase `max_retries` in config or via `--max-retries 5`
- Check if model is appropriate for translation tasks
- Review `preserve_terms` list for missing technical terms

#### Network Timeouts
```
[ERROR] Timeout on attempt 1 for: Long text string...
```
**Solutions**:
- Increase `timeout` in configuration
- Check LM Studio performance and GPU memory
- Reduce `max_tokens` if responses are too long

### Performance Optimization

#### For Better Speed
- Use `--sample N` for testing with fewer strings
- Reduce `max_retries` to 2 for faster processing
- Increase `timeout` to reduce retry overhead

#### For Better Quality  
- Increase `max_retries` to 5
- Lower `temperature` to 0.05 for more consistent results
- Add more terms to `preserve_terms` list

## 📈 Understanding Output

### Progress Information
```
🌍 Translating to Spanish (es)...
    📂 Loaded existing translations from admin/i18n/es/translations.json
    ⚡ Incremental mode: Only translating new/missing strings
```

### Completion Summary
```
✅ Spanish Complete!
   📄 File: admin/i18n/es/translations.json (45,231 bytes)
   ⏱️  Time: 180.5 seconds
   📊 Translated: 220
   ⏭️  Skipped: 25 (short/preserved)
   ⚠️  Warnings: 12
   ❌ Errors: 2
   🔄 API calls: 222
   🔁 Retries: 8
   🎯 Quality rate: 94.5%
   ⚡ Avg per call: 0.81s
```

### Final Statistics
```
📈 Overall Statistics:
   ✅ Total strings processed: 6,075
   ⚠️  Total warnings: 45
   ❌ Total errors: 0
   🔁 Total retries: 12
   🎯 Overall quality rate: 98.5%
   📊 Overall success rate: 99.3%
```

## 🌍 Supported Languages

| Code | Language | Code | Language |
|------|----------|------|----------|
| `es` | Spanish | `nl` | Dutch |
| `pt` | Portuguese | `pl` | Polish |
| `de` | German | `tr` | Turkish |
| `ko` | Korean | `sv` | Swedish |
| `fr` | French | `da` | Danish |
| `it` | Italian | | |
| `ja` | Japanese | | |
| `zh` | Chinese (Simplified) | | |
| `ru` | Russian | | |
| `ar` | Arabic | | |

### Adding New Languages

Edit `translate_config.json`:
```json
{
  "languages": {
    "existing_languages": "...",
    "no": "Norwegian",
    "fi": "Finnish",
    "hu": "Hungarian"
  }
}
```

## 🔒 Preserve Terms

These terms are never translated:

### Game-Specific
- `R5`, `R4`, `APE`, `NAP15` - Game roles and ranks
- `UvvU`, `ORCE`, `MTOP`, `FNXS`, `MZKU` - Alliance tags

### Technical
- `Discord`, `SMTP`, `JWT`, `API` - System names
- `admin` - System role

### UI Elements  
- `🚀`, `📋`, `🛡️`, `⚠️`, `✅`, `🗳️`, `📅`, `📊`, `ℹ️` - Interface icons

## 🚀 Best Practices

### Development Workflow

1. **Test First**: Use `--preview --sample 25` to test changes
2. **Namespace Development**: Use `--namespace section_name` for feature work
3. **Incremental Updates**: Run without `--force` for ongoing development
4. **Quality Check**: Monitor warning rates and quality scores
5. **Full Rebuild**: Use `--force` when improving translation quality

#### Feature Development Example
```bash
# 1. Develop new help section
python translate_admin_reliable.py --namespace help.new_feature --preview es ko

# 2. Test and refine
python translate_admin_reliable.py --namespace help.new_feature --sample 10 es

# 3. Deploy to target locales
python translate_admin_reliable.py --namespace help.new_feature es pt de ko

# 4. Full deployment when ready
python translate_admin_reliable.py es pt de ko
```

### Production Deployment

1. **Backup Existing**: Copy current translation files before major updates
2. **Staged Rollout**: Translate 2-3 languages first, then expand
3. **Quality Validation**: Review high-warning translations manually
4. **Monitor Logs**: Check logs for systematic issues

### Performance Tuning

1. **Hardware**: Ensure adequate GPU memory for your model
2. **Network**: Stable connection to LM Studio
3. **Configuration**: Tune `max_retries` and `timeout` based on your setup
4. **Monitoring**: Use quality rates to optimize settings

## 📁 Configuration Files

The repository includes several example configuration files:

| File | Purpose | Usage |
|------|---------|-------|
| `translate_config.example.json` | Complete example with all options | Reference for all available settings |
| `translate_config.dev.json` | Development configuration | Fast testing with debug logging |
| `translate_config.prod.json` | Production configuration | High quality with full validation |

### Using Example Configurations

```bash
# Development workflow
python translate_admin_reliable.py --config translate_config.dev.json --preview es ko

# Production deployment
python translate_admin_reliable.py --config translate_config.prod.json

# Custom configuration
cp translate_config.example.json my_config.json
# Edit my_config.json as needed
python translate_admin_reliable.py --config my_config.json es
```

## 📞 Support

### Log Analysis
Check `logs/translation_*.log` for detailed information about:
- Translation attempts and results
- i18n validation decisions and locale-specific issues
- Quality validation decisions  
- Retry logic and timing
- Error details and recovery

### Common Solutions
- **High retry rate**: Increase timeout or reduce max_tokens
- **Low quality rate**: Add preserve terms or adjust temperature
- **Slow performance**: Check GPU memory and model efficiency
- **Network issues**: Verify LM Studio accessibility and stability
- **i18n validation errors**: Check placeholder preservation and HTML tags
- **Locale-specific issues**: Review locale conventions in configuration

---

*This script is designed for the Server 1586 admin interface translation workflow. For technical support or feature requests, refer to the project documentation.*