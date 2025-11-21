import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Card } from '@heroui/react';

interface TimeInfo {
  serverTime: string;
  localTime: string;
  seasonEndCountdown: {
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
    totalSeconds: number;
  };
}

export function ServerClock() {
  const { t } = useTranslation(['common', 'public']);
  const [timeInfo, setTimeInfo] = useState<TimeInfo | null>(null);

  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      
      // Calculate server time offset
      // Server resets at 9pm EST (winter) and used to reset at 10pm EDT (summer)
      // This means server reset is at a FIXED time that doesn't change with DST
      // 9pm EST = 2am UTC next day, 10pm EDT = 2am UTC next day
      // So server reset is at midnight server time, making server UTC-2
      
      // Get current UTC time
      const utcTime = new Date(now.getTime() + (now.getTimezoneOffset() * 60000));
      
      // Server is UTC-2, so subtract 2 hours from UTC
      const serverTime = new Date(utcTime.getTime() - (2 * 60 * 60 * 1000));
      
      // Format times
      const serverTimeString = serverTime.toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      
      const localTimeString = now.toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      
      // Calculate season end countdown
      // Season 1 ends roughly 2d 19h 22min from 2025-11-21 00:37 EST
      // That's approximately 2025-11-23 19:59 EST
      const seasonEndDate = new Date('2025-11-23T19:59:00-05:00'); // EST timezone
      const timeDiff = seasonEndDate.getTime() - now.getTime();
      
      const totalSeconds = Math.max(0, Math.floor(timeDiff / 1000));
      const days = Math.floor(totalSeconds / (24 * 60 * 60));
      const hours = Math.floor((totalSeconds % (24 * 60 * 60)) / (60 * 60));
      const minutes = Math.floor((totalSeconds % (60 * 60)) / 60);
      const seconds = totalSeconds % 60;
      
      setTimeInfo({
        serverTime: serverTimeString,
        localTime: localTimeString,
        seasonEndCountdown: {
          days,
          hours,
          minutes,
          seconds,
          totalSeconds
        }
      });
    };

    // Update immediately
    updateTime();
    
    // Update every second
    const interval = setInterval(updateTime, 1000);
    
    return () => clearInterval(interval);
  }, []);

  if (!timeInfo) {
    return null;
  }

  const { seasonEndCountdown } = timeInfo;
  const isSeasonEnded = seasonEndCountdown.totalSeconds <= 0;

  return (
    <Card variant="secondary" className="p-4 mb-6">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
        {/* Server Time */}
        <div className="space-y-1">
          <div className="text-xs font-medium text-muted uppercase tracking-wide">
            {t('public:clock.serverTime')}
          </div>
          <div className="text-lg font-mono font-bold text-accent">
            {timeInfo.serverTime}
          </div>
          <div className="text-xs text-muted">
            UTC-2
          </div>
        </div>

        {/* Local Time */}
        <div className="space-y-1">
          <div className="text-xs font-medium text-muted uppercase tracking-wide">
            {t('public:clock.localTime')}
          </div>
          <div className="text-lg font-mono font-bold text-info">
            {timeInfo.localTime}
          </div>
          <div className="text-xs text-muted">
            {Intl.DateTimeFormat().resolvedOptions().timeZone}
          </div>
        </div>

        {/* Season Countdown */}
        <div className="space-y-1">
          <div className="text-xs font-medium text-muted uppercase tracking-wide">
            {isSeasonEnded ? t('public:clock.seasonEnded') : t('public:clock.seasonEnds')}
          </div>
          {isSeasonEnded ? (
            <div className="text-lg font-bold text-danger">
              {t('public:clock.seasonComplete')}
            </div>
          ) : (
            <div className="text-sm font-mono font-bold text-warning">
              <div className="flex justify-center items-center gap-1">
                <span className="bg-warning/20 text-warning px-2 py-1 rounded">
                  {seasonEndCountdown.days}d
                </span>
                <span className="bg-warning/20 text-warning px-2 py-1 rounded">
                  {seasonEndCountdown.hours.toString().padStart(2, '0')}h
                </span>
                <span className="bg-warning/20 text-warning px-2 py-1 rounded">
                  {seasonEndCountdown.minutes.toString().padStart(2, '0')}m
                </span>
                <span className="bg-warning/20 text-warning px-2 py-1 rounded">
                  {seasonEndCountdown.seconds.toString().padStart(2, '0')}s
                </span>
              </div>
            </div>
          )}
          <div className="text-xs text-muted">
            {isSeasonEnded ? '' : t('public:clock.season1')}
          </div>
        </div>
      </div>
    </Card>
  );
}