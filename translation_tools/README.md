# Server 1586 Translation Tools

Enterprise-grade i18n translation system for the Server 1586 admin interface.

## 📁 Structure

```
translation_tools/
├── README.md                           # This file
├── translate_admin_reliable.py         # Main translation script
├── translate_admin_simple.py           # Legacy simple script
├── docs/
│   ├── TRANSLATION_GUIDE.md           # Comprehensive documentation
│   └── TRANSLATION_README.md          # Quick reference
└── configs/
    ├── translate_config.example.json  # Complete configuration example
    ├── translate_config.dev.json      # Development configuration
    └── translate_config.prod.json     # Production configuration
```

## 🚀 Quick Start

```bash
# Navigate to translation tools
cd translation_tools

# Install optional dependency for better progress bars
pip install tqdm

# Translate all locales (first run)
python translate_admin_reliable.py

# Translate specific locales
python translate_admin_reliable.py es ko ja

# Use development configuration
python translate_admin_reliable.py --config configs/translate_config.dev.json --preview es
```

## 🎯 Key Features

- **Namespace Filtering** - Translate specific sections (`--namespace help`)
- **Quality Validation** - Automatic retry on translation issues
- **Resume Capability** - Continues from interruption points
- **15 Locales** - Comprehensive language support
- **i18n Best Practices** - Professional internationalization workflow

## 📊 i18n Structure

### Source and Target
```
../admin/i18n/
├── en/translations.json              # Source locale (English)
├── es/translations.json              # Spanish
├── ko/translations.json              # Korean
├── ja/translations.json              # Japanese
└── [12 more locales...]              # Complete coverage
```

### Generated Files
```
translation_tools/
├── translate_config.json             # Auto-generated configuration
└── logs/
    └── translation_YYYYMMDD_HHMMSS.log  # Detailed execution logs
```

## 🔧 Configuration

The script automatically creates `translate_config.json` with i18n settings. Use example configurations:

- **Development**: `configs/translate_config.dev.json` - Fast testing
- **Production**: `configs/translate_config.prod.json` - High quality
- **Custom**: Copy and modify `configs/translate_config.example.json`

## 📚 Documentation

- **[TRANSLATION_GUIDE.md](docs/TRANSLATION_GUIDE.md)** - Complete documentation with examples
- **[TRANSLATION_README.md](docs/TRANSLATION_README.md)** - Quick reference guide

## 🌍 Supported Locales

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

## 🛠️ Requirements

1. **LM Studio** running with translation model (e.g., `tencent.hunyuan-mt-7b`)
2. **Python 3.7+** with `requests` library
3. **Source file**: `../admin/i18n/en/translations.json`

## 💡 Common Workflows

### Feature Development
```bash
# Test new help section
python translate_admin_reliable.py --namespace help.new_feature --preview es ko

# Deploy help updates
python translate_admin_reliable.py --namespace help es pt de ko
```

### Production Deployment
```bash
# High-quality translation with full validation
python translate_admin_reliable.py --config configs/translate_config.prod.json

# Core locales first
python translate_admin_reliable.py es pt de ko fr

# All locales
python translate_admin_reliable.py
```

### Troubleshooting
```bash
# Test with sample data
python translate_admin_reliable.py --sample 25 --preview es

# Force retranslation with more retries
python translate_admin_reliable.py --force --max-retries 5 es

# Check specific namespace
python translate_admin_reliable.py --namespace buttons --preview es
```

---

*Part of the Server 1586 admin interface i18n system. For technical support, check the logs and documentation.*