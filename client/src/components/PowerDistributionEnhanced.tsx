import { useState, useEffect, useRef } from 'react';
import { Card } from '@heroui/react';
import { useApi } from '../hooks/useApi';
import { useFullscreen } from '../hooks/useFullscreen';
import { FullscreenButton } from './FullscreenButton';
import type { Alliance } from '../types';

export function PowerDistributionEnhanced() {
  const { data: alliances, loading, error } = useApi<Alliance[]>('alliances.json');
  const [maxPower, setMaxPower] = useState(0);

  // Fullscreen support
  const cardRef = useRef<HTMLDivElement>(null);
  const { isFullscreen, toggleFullscreen } = useFullscreen(cardRef);

  // Find max power for scaling
  useEffect(() => {
    if (alliances && alliances.length > 0) {
      const max = Math.max(...alliances.slice(0, 15).map(a => a.power));
      setMaxPower(max);
    }
  }, [alliances]);

  if (loading) {
    return (
      <Card variant="secondary" className="p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-4 bg-gray-300 dark:bg-gray-700 rounded w-3/4"></div>
          <div className="h-8 bg-gray-300 dark:bg-gray-700 rounded"></div>
          <div className="h-8 bg-gray-300 dark:bg-gray-700 rounded w-11/12"></div>
          <div className="h-8 bg-gray-300 dark:bg-gray-700 rounded w-10/12"></div>
        </div>
      </Card>
    );
  }

  if (error || !alliances) {
    return (
      <Card variant="quaternary" className="p-6 border-2 border-red-500/20">
        <p className="text-red-500">Failed to load power distribution data</p>
      </Card>
    );
  }

  const top15 = alliances.slice(0, 15);

  return (
    <Card
      ref={cardRef}
      variant="secondary"
      className={`p-6 transition-all ${
        isFullscreen
          ? 'bg-background h-screen overflow-y-auto'
          : ''
      }`}
    >
      <Card.Header>
        <div className="flex items-start justify-between w-full">
          <div>
            <Card.Title className="text-2xl">⚡ Power Distribution</Card.Title>
            <Card.Description>
              Top 15 Alliances by Total Power
            </Card.Description>
          </div>
          <FullscreenButton
            isFullscreen={isFullscreen}
            onToggle={toggleFullscreen}
          />
        </div>
      </Card.Header>

      <Card.Content className="mt-6 space-y-3">
        {top15.map((alliance, index) => {
          const percentage = (alliance.power / maxPower) * 100;
          const isTop3 = index < 3;
          const isTop5 = index < 5;

          // Color scheme based on rank
          let barColor = 'bg-accent';
          if (isTop3) {
            barColor = index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : 'bg-orange-600';
          } else if (isTop5) {
            barColor = 'bg-purple-500';
          }

          return (
            <div key={alliance.tag} className="group">
              {/* Alliance Info Row */}
              <div className="flex items-center justify-between mb-1">
                <div className="flex items-center gap-2 min-w-0">
                  <span className="text-sm font-bold opacity-60 w-6 text-right flex-shrink-0">
                    #{index + 1}
                  </span>
                  <span className="font-semibold truncate">
                    {alliance.tag}
                  </span>
                  <span className="text-sm opacity-75 truncate hidden sm:block">
                    {alliance.name}
                  </span>
                </div>
                <span className="text-sm font-mono font-semibold ml-2 flex-shrink-0">
                  {alliance.power.toLocaleString()}M
                </span>
              </div>

              {/* Power Bar */}
              <div className="relative h-8 bg-surface-secondary rounded-lg overflow-hidden">
                {/* Animated gradient bar */}
                <div
                  className={`h-full ${barColor} transition-all duration-1000 ease-out relative overflow-hidden`}
                  style={{ width: `${percentage}%` }}
                >
                  {/* Shine effect */}
                  <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 group-hover:translate-x-full transition-transform duration-1000"></div>

                  {/* Power value inside bar */}
                  <div className="absolute inset-0 flex items-center px-3">
                    <span className="text-xs font-semibold text-white drop-shadow-md">
                      {percentage.toFixed(1)}%
                    </span>
                  </div>
                </div>

                {/* Rank badge for top 3 */}
                {isTop3 && (
                  <div className="absolute right-2 top-1/2 -translate-y-1/2">
                    <span className="text-xl">
                      {index === 0 ? '🥇' : index === 1 ? '🥈' : '🥉'}
                    </span>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </Card.Content>

      {/* Summary Stats */}
      <Card.Footer className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div className="text-center">
            <div className="text-2xl font-bold text-accent">
              {top15.reduce((sum, a) => sum + a.power, 0).toLocaleString()}M
            </div>
            <div className="text-xs opacity-75 mt-1">Total Combined Power</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-accent">
              {(top15.reduce((sum, a) => sum + a.power, 0) / top15.length).toFixed(0)}M
            </div>
            <div className="text-xs opacity-75 mt-1">Average Power</div>
          </div>
        </div>
      </Card.Footer>
    </Card>
  );
}
