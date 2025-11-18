import { useTranslation } from 'react-i18next';
import { Card, Avatar, Chip, Disclosure, Button } from '@heroui/react';
import { useApi } from '../hooks/useApi';
import type { CouncilMember } from '../types';

interface RotationSchedule {
  currentWeekNumber: number;
  schedule: Array<{
    weekNumber: number;
    startDate: string;
    rotatingMembers: string[];
  }>;
}

export function CouncilMembers() {
  const { t } = useTranslation('public');
  const { data: council } = useApi<{ members: CouncilMember[] }>('council.json');
  const { data: rotation } = useApi<RotationSchedule>('rotation-schedule.json');

  if (!council || !council.members || council.members.length === 0) return null;

  const permanentMembers = council.members.filter(m => m.seat === 'permanent');
  const rotatingMembers = council.members.filter(m => m.seat === 'rotating');

  return (
    <section className="mb-16" id="council">
      <h2 className="text-4xl font-bold text-center mb-8">{t('council.title')}</h2>

      {/* Council Info Card */}
      <Card variant="secondary" className="mb-8 p-6">
        <div className="flex flex-col md:flex-row justify-around items-center gap-6 text-center">
          <div>
            <div className="text-3xl font-bold text-accent">7</div>
            <div className="text-sm opacity-75">{t('council.totalMembers')}</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-green-500">5</div>
            <div className="text-sm opacity-75">{t('council.permanentSeats')}</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-blue-500">2</div>
            <div className="text-sm opacity-75">{t('council.rotatingSeats')}</div>
          </div>
          <div>
            <div className="text-3xl font-bold">{t('council.weekly')}</div>
            <div className="text-sm opacity-75">{t('council.rotation')}</div>
          </div>
        </div>
      </Card>

      {/* Permanent Members */}
      <div className="mb-8">
        <h3 className="text-2xl font-semibold mb-4 flex items-center gap-2">
          <Chip color="success" variant="primary">{t('council.permanent')}</Chip>
          <span>{t('council.top5Alliances')}</span>
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
          {permanentMembers.map((member, idx) => (
            <Card key={member.tag} variant="tertiary" className="p-4 text-center">
              <Card.Header className="flex-col items-center pb-2">
                <Avatar size="lg" className="mb-2">
                  <Avatar.Fallback className="text-2xl font-bold">
                    {member.tag}
                  </Avatar.Fallback>
                </Avatar>
                <Card.Title className="text-lg">#{idx + 1}</Card.Title>
              </Card.Header>
              <Card.Content>
                <div className="font-bold text-lg">[{member.tag}]</div>
                <div className="text-sm opacity-75 mt-1">{member.name}</div>
              </Card.Content>
            </Card>
          ))}
        </div>
      </div>

      {/* Rotating Members */}
      <div>
        <h3 className="text-2xl font-semibold mb-4 flex items-center gap-2">
          <Chip color="accent" variant="primary">{t('council.rotating')}</Chip>
          <span>{t('council.currentWeek')}</span>
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {rotatingMembers.map((member) => (
            <Card key={member.tag} variant="secondary" className="p-5">
              <div className="flex items-center gap-4">
                <Avatar size="lg">
                  <Avatar.Fallback className="text-2xl font-bold">
                    {member.tag}
                  </Avatar.Fallback>
                </Avatar>
                <div className="flex-1">
                  <div className="font-bold text-lg">[{member.tag}] {member.name}</div>
                  <div className="text-sm opacity-75">{t('council.rotatingMember')}</div>
                  {member.startDate && (
                    <div className="text-xs opacity-60 mt-1">{t('council.since')}: {member.startDate}</div>
                  )}
                </div>
              </div>
            </Card>
          ))}
        </div>
      </div>

      {/* Rotation Schedule */}
      {rotation && rotation.schedule && (
        <div className="mt-12">
          <h3 className="text-2xl font-semibold mb-4 flex items-center gap-2">
            <Chip color="warning" variant="primary">{t('councilRotation.schedule')}</Chip>
            <span>{t('councilRotation.rotationCalendar')}</span>
          </h3>
          <Card variant="tertiary" className="p-6">
            <div className="space-y-4">
              {/* Previous Week */}
              {rotation.currentWeekNumber > 1 && (() => {
                const prevWeek = rotation.schedule[rotation.currentWeekNumber - 2];
                const weekDate = new Date(prevWeek.startDate);
                return (
                  <Card variant="secondary" className="p-4 opacity-60">
                    <Card.Header className="pb-2">
                      <div className="flex items-center justify-between w-full">
                        <Card.Title className="text-base">{t('councilRotation.week')} {prevWeek.weekNumber}</Card.Title>
                        <Chip color="default" size="sm" variant="secondary">{t('councilRotation.previous')}</Chip>
                      </div>
                      <Card.Description className="text-xs">
                        {weekDate.toLocaleDateString()}
                      </Card.Description>
                    </Card.Header>
                    <Card.Content className="flex gap-4 flex-wrap">
                      {prevWeek.rotatingMembers.map((tag) => (
                        <div key={tag} className="flex items-center gap-2">
                          <div className="w-2 h-2 bg-gray-400 rounded-full"></div>
                          <span className="font-mono font-semibold">[{tag}]</span>
                        </div>
                      ))}
                    </Card.Content>
                  </Card>
                );
              })()}

              {/* Current Week */}
              {(() => {
                const currentWeek = rotation.schedule[rotation.currentWeekNumber - 1];
                const weekDate = new Date(currentWeek.startDate);
                return (
                  <Card variant="secondary" className="p-4 ring-2 ring-accent">
                    <Card.Header className="pb-2">
                      <div className="flex items-center justify-between w-full">
                        <Card.Title className="text-base">{t('councilRotation.week')} {currentWeek.weekNumber}</Card.Title>
                        <Chip color="accent" size="sm">{t('councilRotation.current')}</Chip>
                      </div>
                      <Card.Description className="text-xs">
                        {weekDate.toLocaleDateString()}
                      </Card.Description>
                    </Card.Header>
                    <Card.Content className="flex gap-4 flex-wrap">
                      {currentWeek.rotatingMembers.map((tag) => (
                        <div key={tag} className="flex items-center gap-2">
                          <div className="w-2 h-2 bg-accent rounded-full"></div>
                          <span className="font-mono font-semibold">[{tag}]</span>
                        </div>
                      ))}
                    </Card.Content>
                  </Card>
                );
              })()}

              {/* Next 4 Weeks - Collapsible */}
              <Disclosure>
                <Disclosure.Heading>
                  <Button slot="trigger" variant="secondary" className="w-full p-4 justify-between">
                    <div className="flex items-center gap-2">
                      <Chip color="default" size="sm" variant="secondary">{t('councilRotation.upcoming')}</Chip>
                      <span className="font-semibold">{t('councilRotation.next4Weeks')}</span>
                    </div>
                    <Disclosure.Indicator />
                  </Button>
                </Disclosure.Heading>
                <Disclosure.Content>
                  <div className="mt-4 space-y-3">
                    {rotation.schedule.slice(rotation.currentWeekNumber, rotation.currentWeekNumber + 4).map((week) => {
                      const weekDate = new Date(week.startDate);
                      return (
                        <Card key={week.weekNumber} variant="secondary" className="p-4">
                          <Card.Header className="pb-2">
                            <div className="flex items-center justify-between w-full">
                              <Card.Title className="text-base">{t('councilRotation.week')} {week.weekNumber}</Card.Title>
                            </div>
                            <Card.Description className="text-xs">
                              {weekDate.toLocaleDateString()}
                            </Card.Description>
                          </Card.Header>
                          <Card.Content className="flex gap-4 flex-wrap">
                            {week.rotatingMembers.map((tag) => (
                              <div key={tag} className="flex items-center gap-2">
                                <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span className="font-mono font-semibold">[{tag}]</span>
                              </div>
                            ))}
                          </Card.Content>
                        </Card>
                      );
                    })}
                  </div>
                </Disclosure.Content>
              </Disclosure>
            </div>
            <div className="mt-6 text-center text-sm opacity-60">
              {t('councilRotation.currentlyWeek', {
                current: rotation.currentWeekNumber,
                total: rotation.schedule.length
              })}
            </div>
          </Card>
        </div>
      )}
    </section>
  );
}
