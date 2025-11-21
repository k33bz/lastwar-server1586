import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, Avatar, Chip } from '@heroui/react';
import { AllianceDetailModal } from './AllianceDetailModal';
import type { Alliance } from '../types';

interface AlliancePodiumProps {
  alliances: Alliance[];
}

export function AlliancePodium({ alliances }: AlliancePodiumProps) {
  const { t } = useTranslation('public');
  const [selectedAlliance, setSelectedAlliance] = useState<{ alliance: Alliance; rank: number } | null>(null);
  // Sort by power and take top 3
  const topThree = [...alliances]
    .sort((a, b) => b.power - a.power)
    .slice(0, 3);

  // Reorder for podium display: 2nd, 1st, 3rd
  const podiumOrder = topThree.length >= 3
    ? [topThree[1], topThree[0], topThree[2]]
    : topThree;

  const getMedalColor = (rank: number) => {
    switch (rank) {
      case 1: return 'text-yellow-400'; // Gold
      case 2: return 'text-gray-300'; // Silver
      case 3: return 'text-orange-400'; // Bronze
      default: return 'text-gray-400';
    }
  };

  const getMedalEmoji = (rank: number) => {
    switch (rank) {
      case 1: return '🥇';
      case 2: return '🥈';
      case 3: return '🥉';
      default: return '';
    }
  };

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
        <h2 className="text-3xl font-bold text-center mb-12">{t('podium.title')}</h2>

        {/* Mobile Layout - Proper Ranking Order */}
        <div className="grid grid-cols-1 gap-6 max-w-md mx-auto md:hidden">
          {topThree.map((alliance, index) => {
            const actualRank = index + 1;
            const isFirst = actualRank === 1;

            return (
              <Card
                key={alliance.tag}
                className={`p-6 text-center transition-all cursor-pointer hover:shadow-xl ${
                  isFirst ? 'scale-105 shadow-lg' : 'shadow-md'
                }`}
                onClick={() => setSelectedAlliance({ alliance, rank: actualRank })}
              >
              <Card.Header className="flex-col items-center">
                <div className={`text-6xl mb-2 ${getMedalColor(actualRank)}`}>
                  {getMedalEmoji(actualRank)}
                </div>
                <Avatar size="lg" className="mb-3">
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
                <Card.Title className="text-xl">{alliance.name}</Card.Title>
                <Card.Description className="text-sm">
                  [{alliance.tag}]
                </Card.Description>
              </Card.Header>

              <Card.Content className="space-y-3">
                <div>
                  <div className="text-sm opacity-60 mb-1">{t('alliances.power')}</div>
                  <div className="text-2xl font-bold">{formatPower(alliance.power)}</div>
                </div>

                <div>
                  <div className="text-sm opacity-60 mb-1">{t('alliances.r5')}</div>
                  <div className="font-semibold">{alliance.r5.name}</div>
                </div>

                {alliance.signed && (
                  <Chip color="success" size="sm">
                    {t('podium.signed')}
                  </Chip>
                )}

                {alliance.crossServer?.hasPartner && (
                  <Chip color="accent" size="sm">
                    Cross-Server Alliance
                  </Chip>
                )}
              </Card.Content>
            </Card>
            );
          })}
        </div>

        {/* Desktop Layout - Podium Visual Effect */}
        <div className="hidden md:grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
          {podiumOrder.map((alliance) => {
            // Get actual rank (1st, 2nd, or 3rd)
            const actualRank = topThree.indexOf(alliance) + 1;
            const isFirst = actualRank === 1;
            const isSecond = actualRank === 2;

            return (
              <Card
                key={alliance.tag}
                className={`p-6 text-center transition-all cursor-pointer hover:shadow-xl ${
                  isFirst ? 'md:scale-110 shadow-lg' :
                  isSecond ? 'md:scale-105 shadow-md' : 'md:scale-100'
                }`}
                onClick={() => setSelectedAlliance({ alliance, rank: actualRank })}
              >
              <Card.Header className="flex-col items-center">
                <div className={`text-6xl mb-2 ${getMedalColor(actualRank)}`}>
                  {getMedalEmoji(actualRank)}
                </div>
                <Avatar size="lg" className="mb-3">
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
                <Card.Title className="text-xl">{alliance.name}</Card.Title>
                <Card.Description className="text-sm">
                  [{alliance.tag}]
                </Card.Description>
              </Card.Header>

              <Card.Content className="space-y-3">
                <div>
                  <div className="text-sm opacity-60 mb-1">{t('alliances.power')}</div>
                  <div className="text-2xl font-bold">{formatPower(alliance.power)}</div>
                </div>

                <div>
                  <div className="text-sm opacity-60 mb-1">{t('alliances.r5')}</div>
                  <div className="font-semibold">{alliance.r5.name}</div>
                </div>

                {alliance.signed && (
                  <Chip color="success" size="sm">
                    {t('podium.signed')}
                  </Chip>
                )}

                {alliance.crossServer?.hasPartner && (
                  <Chip color="accent" size="sm">
                    Cross-Server Alliance
                  </Chip>
                )}
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
