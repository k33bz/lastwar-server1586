import { Button, Link } from '@heroui/react';
import { ThemeToggle } from './ThemeToggle';

interface NavigationProps {
  isOpen: boolean;
  onClose: () => void;
}

export function Navigation({ isOpen, onClose }: NavigationProps) {
  const navLinks = [
    { href: '#top', label: '🏠 Home' },
    { href: '#podium', label: '🏆 Top 3 Alliances' },
    { href: '#alliances', label: '⚔️ NAP15 Alliances' },
    { href: '#council', label: '🗳️ Council Voting' },
    { href: '/votes', label: '📝 Council Votes' },
    { href: '#rules', label: '📜 Server Rules' },
    { href: '#amendments', label: '📝 Amendments' },
    { href: '#power-trends', label: '📊 Power Trends' },
    { href: '#signatories', label: '✍️ Signatories' },
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
          <h3 className="text-lg font-bold">Navigation</h3>
          <Button
            variant="ghost"
            size="sm"
            isIconOnly
            onPress={onClose}
            aria-label="Close menu"
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
                className="flex items-center px-4 py-3 hover:bg-accent/10 transition-colors"
                onPress={onClose}
              >
                {link.label}
              </Link>
            </li>
          ))}
        </ul>

        {/* Footer */}
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
          <div className="flex flex-col gap-3">
            <ThemeToggle />
            <div className="text-center">
              <p className="text-sm font-semibold">Server 1586</p>
              <p className="text-xs opacity-60">v3.3.1</p>
            </div>
          </div>
        </div>
      </nav>
    </>
  );
}
