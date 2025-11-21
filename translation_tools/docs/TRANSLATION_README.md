# Server 1586 Translation System

## Quick Start

```bash
# Install optional dependency for better progress bars
pip install tqdm

# Translate all languages (recommended for first run)
python translate_admin_reliable.py

# Translate specific languages
python translate_admin_reliable.py es ko ja

# Test with preview mode
python translate_admin_reliable.py --preview --sample 25 es
```

## Files Overview

| File | Purpose |
|------|---------|
| `translate_admin_reliable.py` | Main i18n translation script |
| `TRANSLATION_GUIDE.md` | Comprehensive documentation |
| `translate_config.json` | Configuration (auto-created) |
| `translate_config.example.json` | Complete configuration example |
| `translate_config.dev.json` | Development configuration |
| `translate_config.prod.json` | Production configuration |

## Key Features

- ✅ **Namespace Filtering** - Translate specific sections only
- ✅ **Quality Validation** - Automatic retry on translation issues
- ✅ **Resume Capability** - Continues from interruption points  
- ✅ **15 Languages** - Comprehensive language support
- ✅ **Incremental Updates** - Only translates new/changed content
- ✅ **Enterprise Logging** - Detailed logs for troubleshooting
- ✅ **Preview Mode** - Test without saving files

## Common Commands

```bash
# Development workflow
python translate_admin_reliable.py --namespace help --preview es
python translate_admin_reliable.py --namespace pages.dashboard es
python translate_admin_reliable.py --force es            # Rebuild Spanish

# Production deployment  
python translate_admin_reliable.py --config translate_config.prod.json
python translate_admin_reliable.py es pt de ko           # Core languages

# Namespace-specific updates
python translate_admin_reliable.py --namespace buttons es ko ja
python translate_admin_reliable.py --namespace help.user_guide es pt

# Custom configuration
cp translate_config.example.json my_config.json
python translate_admin_reliable.py --config my_config.json es

# Troubleshooting
python translate_admin_reliable.py --sample 10 --max-retries 5 es
```

## Output Structure

```
admin/i18n/
├── en/translations.json          # Source (English)
├── es/translations.json          # Spanish
├── ko/translations.json          # Korean
├── ja/translations.json          # Japanese
└── [13 more languages...]        # Complete coverage
```

## Namespace Filtering

Translate specific sections for faster development:

```bash
# Translate help system only
python translate_admin_reliable.py --namespace help es ko

# Translate dashboard pages
python translate_admin_reliable.py --namespace pages.dashboard es pt

# Translate button labels
python translate_admin_reliable.py --namespace buttons es ko ja

# Nested namespaces
python translate_admin_reliable.py --namespace help.president_approvals es
```

**Benefits**: Faster iteration, reduced API calls, preserve existing translations

## Requirements

1. **LM Studio** running with translation model
2. **Python 3.7+** with `requests` library
3. **Source file**: `admin/i18n/en/translations.json`

## Support

- **Documentation**: See `TRANSLATION_GUIDE.md`
- **Logs**: Check `logs/translation_*.log` for details
- **Configuration**: Modify `translate_config.json`
- **Issues**: Review quality rates and warning messages

---

*Part of the Server 1586 admin interface localization system*