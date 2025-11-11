import { useState, useEffect } from 'react';
import { SwitchRoot, SwitchControl, SwitchThumb, Label } from '@heroui/react';

export function FloatingThemeToggle() {
  const [darkMode, setDarkMode] = useState(() => {
    // Check system preference or localStorage
    if (typeof window !== 'undefined') {
      const stored = localStorage.getItem('theme');
      if (stored) return stored === 'dark';
      return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    return false;
  });

  useEffect(() => {
    if (darkMode) {
      document.documentElement.classList.add('dark');
      document.documentElement.setAttribute('data-theme', 'server1586-dark');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      document.documentElement.setAttribute('data-theme', 'server1586');
      localStorage.setItem('theme', 'light');
    }
  }, [darkMode]);

  return (
    <div className="fixed top-8 right-8 z-50 bg-surface shadow-lg rounded-full p-3 border border-gray-200 dark:border-gray-700">
      <SwitchRoot
        isSelected={darkMode}
        onChange={setDarkMode}
        aria-label="Toggle dark mode"
      >
        <SwitchControl>
          <SwitchThumb />
        </SwitchControl>
        <Label className="text-sm">{darkMode ? '🌙' : '☀️'}</Label>
      </SwitchRoot>
    </div>
  );
}
