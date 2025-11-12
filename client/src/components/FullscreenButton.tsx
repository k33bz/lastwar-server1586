import { Button } from '@heroui/react';

interface FullscreenButtonProps {
  isFullscreen: boolean;
  onToggle: () => void;
  className?: string;
}

/**
 * Fullscreen toggle button with responsive icons
 *
 * @example
 * const { isFullscreen, toggleFullscreen } = useFullscreen(ref);
 * <FullscreenButton isFullscreen={isFullscreen} onToggle={toggleFullscreen} />
 */
export function FullscreenButton({ isFullscreen, onToggle, className = '' }: FullscreenButtonProps) {
  return (
    <Button
      variant="ghost"
      size="sm"
      onPress={onToggle}
      className={`min-w-0 ${className}`}
      aria-label={isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
    >
      {isFullscreen ? (
        // Exit fullscreen icon
        <svg
          className="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"
          />
        </svg>
      ) : (
        // Enter fullscreen icon
        <svg
          className="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M4 8V4m0 0h4M4 4l5.5 5.5M20 8V4m0 0h-4m4 0l-5.5 5.5M4 16v4m0 0h4m-4 0l5.5-5.5M20 16v4m0 0h-4m4 0l-5.5-5.5"
          />
        </svg>
      )}
    </Button>
  );
}
