import { useTranslation } from 'react-i18next';
import { Card, Avatar, Chip } from '@heroui/react';
import { useApi } from '../hooks/useApi';
import type { Alliance } from '../types';

export function Signatories() {
  const { t } = useTranslation('public');
  const { data: alliances } = useApi<Alliance[]>('alliances.json');

  if (!alliances) return null;

  // Only show Top 15 alliances (NAP15 members)
  const top15 = [...alliances].sort((a, b) => b.power - a.power).slice(0, 15);

  const signedAlliances = top15.filter(a => a.signed).sort((a, b) => b.power - a.power);
  const unsignedAlliances = top15.filter(a => !a.signed).sort((a, b) => b.power - a.power);

  return (
    <section className="mb-16" id="signatories">
      <h2 className="text-4xl font-bold text-center mb-8">{t('signatories.title')}</h2>

      {/* Summary Card */}
      <Card variant="tertiary" className="mb-8 p-6">
        <div className="flex flex-col md:flex-row justify-around items-center gap-6 text-center">
          <div>
            <div className="text-4xl font-bold text-green-500">{signedAlliances.length}</div>
            <div className="text-sm opacity-75">{t('signatories.signed')}</div>
          </div>
          <div className="text-6xl opacity-20">/</div>
          <div>
            <div className="text-4xl font-bold text-red-500">{unsignedAlliances.length}</div>
            <div className="text-sm opacity-75">{t('signatories.notSigned')}</div>
          </div>
          <div className="text-6xl opacity-20">=</div>
          <div>
            <div className="text-4xl font-bold">{top15.length}</div>
            <div className="text-sm opacity-75">{t('signatories.totalNap15')}</div>
          </div>
        </div>
      </Card>

      {/* Signed Alliances */}
      {signedAlliances.length > 0 && (
        <div className="mb-8">
          <h3 className="text-2xl font-semibold mb-4 flex items-center gap-2">
            <Chip color="success" variant="primary">{t('signatories.signedLabel')}</Chip>
            <span>{t('signatories.alliancesCount', { count: signedAlliances.length })}</span>
          </h3>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            {signedAlliances.map((alliance) => (
              <Card key={alliance.tag} variant="secondary" className="p-4 text-center">
                <Avatar size="md" className="mx-auto mb-2">
                  {alliance.discord?.logoUrl && (
                    <Avatar.Image src={alliance.discord.logoUrl} alt={alliance.name} />
                  )}
                  <Avatar.Fallback className="font-bold">
                    {alliance.tag}
                  </Avatar.Fallback>
                </Avatar>
                <div className="font-bold">[{alliance.tag}]</div>
                <div className="text-xs opacity-75 truncate">{alliance.name}</div>
              </Card>
            ))}
          </div>
        </div>
      )}

      {/* Unsigned Alliances */}
      {unsignedAlliances.length > 0 && (
        <div>
          <h3 className="text-2xl font-semibold mb-4 flex items-center gap-2">
            <Chip color="danger" variant="primary">{t('signatories.notSignedLabel')}</Chip>
            <span>{t('signatories.alliancesCount', { count: unsignedAlliances.length })}</span>
          </h3>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            {unsignedAlliances.map((alliance) => (
              <Card key={alliance.tag} variant="secondary" className="p-4 text-center opacity-60">
                <Avatar size="md" className="mx-auto mb-2">
                  {alliance.discord?.logoUrl && (
                    <Avatar.Image src={alliance.discord.logoUrl} alt={alliance.name} />
                  )}
                  <Avatar.Fallback className="font-bold">
                    {alliance.tag}
                  </Avatar.Fallback>
                </Avatar>
                <div className="font-bold">[{alliance.tag}]</div>
                <div className="text-xs opacity-75 truncate">{alliance.name}</div>
              </Card>
            ))}
          </div>
        </div>
      )}
    </section>
  );
}
