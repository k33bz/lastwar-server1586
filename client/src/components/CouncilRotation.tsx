import { Card } from '@heroui/react';
import { useApi } from '../hooks/useApi';

interface RotationSchedule {
  generatedAt: string;
  epoch: string;
  currentWeekNumber: number;
  metadata: {
    top5Permanent?: string[];
    rotatingPool?: string[];
    cycleDuration?: number;
    top3Snapshot?: string[];
    top15Snapshot?: string[];
    lastGeneratedDate: string;
    changesDetected?: boolean;
    changeNotes?: string | null;
    notes?: string;
  };
  schedule: Array<{
    weekNumber: number;
    startDate: string;
    rotatingMembers: string[];
  }>;
}

export function CouncilRotation() {
  const { data: rotation, loading, error } = useApi<RotationSchedule>('rotation-schedule.json');

  if (loading) {
    return (
      <Card variant="secondary" className="p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-4 bg-gray-300 dark:bg-gray-700 rounded w-3/4"></div>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
            {[...Array(10)].map((_, i) => (
              <div key={i} className="h-20 bg-gray-300 dark:bg-gray-700 rounded"></div>
            ))}
          </div>
        </div>
      </Card>
    );
  }

  if (error || !rotation) {
    return (
      <Card variant="quaternary" className="p-6 border-2 border-red-500/20">
        <p className="text-red-500">Failed to load rotation schedule</p>
      </Card>
    );
  }

  // Get next few weeks (current + 4 more)
  const currentWeek = rotation.currentWeekNumber;
  const upcomingWeeks = rotation.schedule
    .filter(week => week.weekNumber >= currentWeek && week.weekNumber < currentWeek + 5)
    .slice(0, 5);

  // Get top 5 permanent members (support both old and new format)
  const top5Permanent = rotation.metadata.top5Permanent ||
    (rotation.metadata.top15Snapshot ? rotation.metadata.top15Snapshot.slice(0, 5) : []);

  // Get cycle duration (support both old and new format)
  const cycleDuration = rotation.metadata.cycleDuration ||
    new Set(rotation.schedule.slice(0, 10).map(w => w.rotatingMembers.join(','))).size;

  return (
    <Card variant="secondary" className="p-6">
      <Card.Header>
        <Card.Title className="text-2xl">🔄 Council Rotation Schedule</Card.Title>
        <Card.Description>
          Rotating members change weekly on Mondays at 2:00 AM UTC
        </Card.Description>
      </Card.Header>

      <Card.Content className="mt-6">
        {/* Permanent Members Banner */}
        <div className="mb-6 p-4 bg-accent/10 border-2 border-accent/30 rounded-lg">
          <div className="flex items-center gap-2 mb-3">
            <span className="text-sm font-semibold text-accent">👑 Permanent Council Members (Top 5)</span>
          </div>
          <div className="flex flex-wrap gap-2">
            {top5Permanent.map(tag => (
              <span key={tag} className="px-3 py-1 bg-accent text-white rounded-md font-semibold text-sm">
                {tag}
              </span>
            ))}
          </div>
        </div>

        {/* Rotation Timeline */}
        <div className="space-y-4">
          <h3 className="font-semibold text-sm opacity-75">Rotating Members (Ranks 6-15)</h3>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {upcomingWeeks.map((week) => {
              const isCurrent = week.weekNumber === currentWeek;
              const startDate = new Date(week.startDate);

              return (
                <div
                  key={week.weekNumber}
                  className={`relative p-4 rounded-lg border-2 transition-all ${
                    isCurrent
                      ? 'bg-accent/10 border-accent shadow-lg scale-105'
                      : 'bg-surface border-gray-300 dark:border-gray-700'
                  }`}
                >
                  {/* Current indicator */}
                  {isCurrent && (
                    <div className="absolute -top-2 -right-2 bg-accent text-white text-xs font-bold px-2 py-1 rounded-full shadow-md">
                      CURRENT
                    </div>
                  )}

                  {/* Week Number */}
                  <div className="text-center mb-3">
                    <div className={`text-2xl font-bold ${isCurrent ? 'text-accent' : ''}`}>
                      Week {week.weekNumber}
                    </div>
                    <div className="text-xs opacity-75">
                      {startDate.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric'
                      })}
                    </div>
                  </div>

                  {/* Rotating Members */}
                  <div className="space-y-2">
                    {week.rotatingMembers.map((tag, idx) => (
                      <div
                        key={idx}
                        className={`text-center py-2 px-3 rounded-md font-semibold text-sm ${
                          isCurrent
                            ? 'bg-accent text-white'
                            : 'bg-surface-secondary'
                        }`}
                      >
                        {tag}
                      </div>
                    ))}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Info Box */}
        <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
          <p className="text-sm">
            <strong>ℹ️ How it works:</strong> The top 5 alliances are permanent council members.
            Ranks 6-15 rotate weekly in pairs, cycling through every {cycleDuration} weeks.
            This ensures all top alliances get council representation while maintaining stability.
          </p>
        </div>
      </Card.Content>
    </Card>
  );
}
