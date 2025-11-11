import { ThemeToggle } from './ThemeToggle';

export function Header() {
  return (
    <header className="py-4">
      <div className="flex justify-end">
        <ThemeToggle />
      </div>
    </header>
  );
}
