import { useState, useCallback, useEffect } from 'react';
import type { RefObject } from 'react';

interface UseFullscreenResult {
  isFullscreen: boolean;
  toggleFullscreen: () => void;
  enterFullscreen: () => void;
  exitFullscreen: () => void;
}

/**
 * Hook for fullscreen functionality on any element
 *
 * @param elementRef - Ref to the element to make fullscreen
 * @returns Fullscreen state and control functions
 *
 * @example
 * const chartRef = useRef<HTMLDivElement>(null);
 * const { isFullscreen, toggleFullscreen } = useFullscreen(chartRef);
 */
export function useFullscreen(elementRef: RefObject<HTMLElement | null>): UseFullscreenResult {
  const [isFullscreen, setIsFullscreen] = useState(false);

  // Check if Fullscreen API is supported
  const isSupported = typeof document !== 'undefined' &&
    (document.fullscreenEnabled ||
     (document as any).webkitFullscreenEnabled ||
     (document as any).mozFullScreenEnabled ||
     (document as any).msFullscreenEnabled);

  const enterFullscreen = useCallback(async () => {
    if (!elementRef.current || !isSupported) return;

    try {
      const element = elementRef.current;

      // Try standard fullscreen API
      if (element.requestFullscreen) {
        await element.requestFullscreen();
      }
      // Webkit (Safari)
      else if ((element as any).webkitRequestFullscreen) {
        await (element as any).webkitRequestFullscreen();
      }
      // Mozilla
      else if ((element as any).mozRequestFullScreen) {
        await (element as any).mozRequestFullScreen();
      }
      // IE/Edge
      else if ((element as any).msRequestFullscreen) {
        await (element as any).msRequestFullscreen();
      }
    } catch (error) {
      console.error('Failed to enter fullscreen:', error);
    }
  }, [elementRef, isSupported]);

  const exitFullscreen = useCallback(async () => {
    if (!isSupported) return;

    try {
      // Try standard fullscreen API
      if (document.exitFullscreen) {
        await document.exitFullscreen();
      }
      // Webkit (Safari)
      else if ((document as any).webkitExitFullscreen) {
        await (document as any).webkitExitFullscreen();
      }
      // Mozilla
      else if ((document as any).mozCancelFullScreen) {
        await (document as any).mozCancelFullScreen();
      }
      // IE/Edge
      else if ((document as any).msExitFullscreen) {
        await (document as any).msExitFullscreen();
      }
    } catch (error) {
      console.error('Failed to exit fullscreen:', error);
    }
  }, [isSupported]);

  const toggleFullscreen = useCallback(() => {
    if (isFullscreen) {
      exitFullscreen();
    } else {
      enterFullscreen();
    }
  }, [isFullscreen, enterFullscreen, exitFullscreen]);

  // Listen for fullscreen changes
  useEffect(() => {
    if (!isSupported) return;

    const handleFullscreenChange = () => {
      const isCurrentlyFullscreen =
        document.fullscreenElement === elementRef.current ||
        (document as any).webkitFullscreenElement === elementRef.current ||
        (document as any).mozFullScreenElement === elementRef.current ||
        (document as any).msFullscreenElement === elementRef.current;

      setIsFullscreen(isCurrentlyFullscreen);
    };

    // Listen to all fullscreen change events
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);

    return () => {
      document.removeEventListener('fullscreenchange', handleFullscreenChange);
      document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
      document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
      document.removeEventListener('MSFullscreenChange', handleFullscreenChange);
    };
  }, [elementRef, isSupported]);

  return {
    isFullscreen,
    toggleFullscreen,
    enterFullscreen,
    exitFullscreen,
  };
}
