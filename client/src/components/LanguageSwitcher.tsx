import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Popover, PopoverTrigger, PopoverContent } from '@heroui/react';
import { supportedLanguages } from '../i18n';

export function LanguageSwitcher() {
  const { i18n, t } = useTranslation('common');
  const [isOpen, setIsOpen] = useState(false);

  const currentLanguage = supportedLanguages.find(lang => lang.code === i18n.language) || supportedLanguages[0];

  const handleLanguageChange = (languageCode: string) => {
    i18n.changeLanguage(languageCode);
    setIsOpen(false);
  };

  return (
    <Popover isOpen={isOpen} onOpenChange={setIsOpen} placement="bottom">
      <PopoverTrigger>
        <Button
          variant="ghost"
          size="sm"
          className="gap-2"
          aria-label={t('language.select')}
        >
          <span className="text-lg">{currentLanguage.flag}</span>
          <span className="hidden sm:inline">{currentLanguage.nativeName}</span>
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-64">
        <div className="p-2">
          <div className="text-sm font-semibold mb-2 px-2">{t('language.select')}</div>
          <div className="space-y-1">
            {supportedLanguages.map((language) => (
              <button
                key={language.code}
                onClick={() => handleLanguageChange(language.code)}
                className={`
                  w-full flex items-center gap-3 px-3 py-2 rounded-md
                  hover:bg-accent/10 transition-colors text-left
                  ${i18n.language === language.code ? 'bg-accent/20' : ''}
                `}
              >
                <span className="text-xl">{language.flag}</span>
                <div className="flex-1">
                  <div className="font-medium">{language.nativeName}</div>
                  <div className="text-xs opacity-60">{language.name}</div>
                </div>
                {i18n.language === language.code && (
                  <svg width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="20 6 9 17 4 12" transform="scale(0.8)" />
                  </svg>
                )}
              </button>
            ))}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
}
