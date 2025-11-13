import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, Avatar, Chip } from '@heroui/react';
import { AllianceDetailModal } from './AllianceDetailModal';
import type { Alliance } from '../types';

interface AllianceGridProps {
  alliances: Alliance[];
}

export function AllianceGrid({ alliances }: AllianceGridProps) {
  const { t } = useTranslation('public');
  const [selectedAlliance, setSelectedAlliance] = useState<{ alliance: Alliance; rank: number } | null>(null);
  // Sort by power and skip top 3
  const rankedAlliances = [...alliances]
    .sort((a, b) => b.power - a.power)
    .slice(3, 15);

  const formatPower = (power: number) => {
    if (power >= 1_000_000_000) {
      return `${(power / 1_000_000_000).toFixed(2)}B`;
    }
    if (power >= 1_000_000) {
      return `${(power / 1_000_000).toFixed(2)}M`;
    }
    return power.toLocaleString();
  };

  return (
    <>
      <section className="mb-12">
        <h2 className="text-3xl font-bold text-center mb-8">{t('alliances.title')}</h2>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {rankedAlliances.map((alliance, index) => {
            const rank = index + 4; // Ranks 4-15

            return (
              <Card
                key={alliance.tag}
                className="p-5 cursor-pointer hover:shadow-lg hover:scale-105 transition-all"
                onClick={() => setSelectedAlliance({ alliance, rank })}
              >
              <Card.Header>
                <div className="flex items-center gap-3 w-full">
                  <div className="text-2xl font-bold opacity-40 min-w-[2rem]">
                    #{rank}
                  </div>
                  <Avatar size="md">
                    {alliance.discord?.logoUrl && (
                      <Avatar.Image
                        src={alliance.discord.logoUrl}
                        alt={alliance.name}
                      />
                    )}
                    <Avatar.Fallback>
                      {alliance.tag}
                    </Avatar.Fallback>
                  </Avatar>
                  <div className="flex-1 min-w-0">
                    <Card.Title className="text-base truncate">
                      {alliance.name}
                    </Card.Title>
                    <Card.Description className="text-xs">
                      [{alliance.tag}]
                    </Card.Description>
                  </div>
                </div>
              </Card.Header>

              <Card.Content className="space-y-2">
                <div className="flex justify-between items-center">
                  <span className="text-sm opacity-60">{t('alliances.power')}:</span>
                  <span className="font-bold">{formatPower(alliance.power)}</span>
                </div>

                <div className="flex justify-between items-center">
                  <span className="text-sm opacity-60">{t('alliances.r5')}:</span>
                  <span className="text-sm font-semibold truncate max-w-[60%]">
                    {alliance.r5.name}
                  </span>
                </div>

                <div className="flex flex-wrap gap-2 mt-3">
                  {alliance.signed && (
                    <Chip color="success" size="sm">
                      {t('alliances.signed')}
                    </Chip>
                  )}
                  {alliance.crossServer?.hasPartner && (
                    <Chip color="accent" size="sm">
                      Cross-Server
                    </Chip>
                  )}
                </div>
              </Card.Content>
            </Card>
            );
          })}
        </div>
      </section>

      <AllianceDetailModal
        alliance={selectedAlliance?.alliance || null}
        rank={selectedAlliance?.rank || 0}
        onClose={() => setSelectedAlliance(null)}
      />
    </>
  );
}
