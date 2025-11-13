import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

// Import translation files
import enCommon from '../locales/en-US/common.json';
import enPublic from '../locales/en-US/public.json';
import esCommon from '../locales/es/common.json';
import esPublic from '../locales/es/public.json';
import ptCommon from '../locales/pt/common.json';
import ptPublic from '../locales/pt/public.json';
import koCommon from '../locales/ko/common.json';
import koPublic from '../locales/ko/public.json';
import deCommon from '../locales/de/common.json';
import dePublic from '../locales/de/public.json';

const resources = {
  'en-US': {
    common: enCommon,
    public: enPublic
  },
  es: {
    common: esCommon,
    public: esPublic
  },
  pt: {
    common: ptCommon,
    public: ptPublic
  },
  ko: {
    common: koCommon,
    public: koPublic
  },
  de: {
    common: deCommon,
    public: dePublic
  }
};

i18n
  .use(LanguageDetector) // Auto-detect browser language
  .use(initReactI18next) // Pass i18n instance to react-i18next
  .init({
    resources,
    fallbackLng: 'en-US',
    defaultNS: 'common',
    ns: ['common', 'public'],

    detection: {
      // Order of language detection
      order: ['localStorage', 'navigator', 'htmlTag'],
      // Cache user language preference
      caches: ['localStorage'],
      lookupLocalStorage: 'preferredLanguage'
    },

    interpolation: {
      escapeValue: false // React already escapes values
    },

    react: {
      useSuspense: true
    }
  });

export default i18n;

export const supportedLanguages = [
  { code: 'en-US', name: 'English', nativeName: 'English', flag: '🇺🇸' },
  { code: 'es', name: 'Spanish', nativeName: 'Español', flag: '🇪🇸' },
  { code: 'pt', name: 'Portuguese', nativeName: 'Português', flag: '🇵🇹' },
  { code: 'ko', name: 'Korean', nativeName: '한국어', flag: '🇰🇷' },
  { code: 'de', name: 'German', nativeName: 'Deutsch', flag: '🇩🇪' }
];
