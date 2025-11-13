import { Button, Link } from '@heroui/react';
import { useTranslation } from 'react-i18next';
import { ThemeToggle } from './ThemeToggle';
import { LanguageSwitcher } from './LanguageSwitcher';

interface NavigationProps {
  isOpen: boolean;
  onClose: () => void;
}

export function Navigation({ isOpen, onClose }: NavigationProps) {
  const { t } = useTranslation('common');

  const navLinks = [
    { href: '#top', icon: '🏠', label: t('navigation.home') },
    { href: '#podium', icon: '🏆', label: t('navigation.topAlliances') },
    { href: '#alliances', icon: '⚔️', label: t('navigation.alliances') },
    { href: '#council', icon: '🗳️', label: t('navigation.council') },
    { href: '/votes', icon: '📝', label: t('navigation.votes') },
    { href: '#rules', icon: '📜', label: t('navigation.rules') },
    { href: '#amendments', icon: '📝', label: t('navigation.amendments') },
    { href: '#power-trends', icon: '📊', label: t('navigation.powerTrends') },
    { href: '#signatories', icon: '✍️', label: t('navigation.signatories') },
  ];

  return (
    <>
      {/* Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 md:hidden"
          onClick={onClose}
        />
      )}

      {/* Side Navigation */}
      <nav
        className={`
          fixed top-0 left-0 h-full w-64 bg-surface shadow-lg z-50
          transform transition-transform duration-300 ease-in-out
          ${isOpen ? 'translate-x-0' : '-translate-x-full'}
        `}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-bold">{t('navigation.menu')}</h3>
          <Button
            variant="ghost"
            size="sm"
            isIconOnly
            onPress={onClose}
            aria-label={t('navigation.closeMenu')}
          >
            <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </Button>
        </div>

        {/* Links */}
        <ul className="py-4 space-y-1">
          {navLinks.map((link) => (
            <li key={link.href}>
              <Link
                href={link.href}
                className="flex items-center gap-2 px-4 py-3 hover:bg-accent/10 transition-colors"
                onPress={onClose}
              >
                <span>{link.icon}</span>
                <span>{link.label}</span>
              </Link>
            </li>
          ))}
        </ul>

        {/* Footer */}
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
          <div className="flex flex-col gap-3">
            <LanguageSwitcher />
            <ThemeToggle />
            <div className="text-center">
              <p className="text-sm font-semibold">{t('server.name')}</p>
              <p className="text-xs opacity-60">{t('server.version', { version: '3.8.0' })}</p>
            </div>
          </div>
        </div>
      </nav>
    </>
  );
}
