import { useTranslation } from 'react-i18next';
import { DiscordBanner } from './components/DiscordBanner';
import { AlliancePodium } from './components/AlliancePodium';
import { AllianceGrid } from './components/AllianceGrid';
import { CouncilMembers } from './components/CouncilMembers';
import { ServerRules } from './components/ServerRules';
import { Signatories } from './components/Signatories';
import { PowerTrendsEnhanced } from './components/PowerTrendsEnhanced';
import { PowerDistributionEnhanced } from './components/PowerDistributionEnhanced';
import { CouncilRotation } from './components/CouncilRotation';
import { SignatureStatus } from './components/SignatureStatus';
import { BackToTop } from './components/BackToTop';
import { FloatingThemeToggle } from './components/FloatingThemeToggle';
import { LanguageSwitcher } from './components/LanguageSwitcher';
import { useApi } from './hooks/useApi';
import type { Alliance, ServerInfo } from './types';
import { Spinner, Card, Separator, Tabs, Link } from '@heroui/react';

interface VersionInfo {
  version: string;
  releaseDate: string;
  lastUpdated: string;
}

export function HomePage() {
  const { t } = useTranslation(['common', 'public']);

  // Fetch data
  const { data: alliances, loading: alliancesLoading, error: alliancesError } = useApi<Alliance[]>('alliances.json');
  const { data: serverInfo, loading: serverInfoLoading, error: serverInfoError } = useApi<ServerInfo>('server-info.json');
  const { data: versionInfo } = useApi<VersionInfo>('version.json');

  const loading = alliancesLoading || serverInfoLoading;
  const error = alliancesError || serverInfoError;

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-surface to-surface-secondary">
        <Card variant="tertiary" className="p-8 text-center">
          <Spinner size="lg" className="mb-4" />
          <p className="text-lg font-semibold">{t('common:common.loading')} {t('common:server.name')}...</p>
          <p className="text-sm opacity-75 mt-2">{t('common:errors.loadingData')}</p>
        </Card>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-background via-surface to-surface-secondary">
        <Card variant="quaternary" className="max-w-lg p-6 border-2 border-red-500/20">
          <Card.Header>
            <Card.Title className="text-red-500">{t('common:errors.loadingData')}</Card.Title>
            <Card.Description className="text-red-400">
              {error.message}
            </Card.Description>
          </Card.Header>
          <Card.Content className="mt-4">
            <p className="text-sm opacity-75">
              {t('common:errors.networkError')}
            </p>
          </Card.Content>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-background via-surface to-surface-secondary">
      {/* Main Container with max width */}
      <div className="container mx-auto px-4 pt-8 pb-8 max-w-7xl">
        {/* Discord Banner */}
        {serverInfo && (
          <div className="mb-16">
            <DiscordBanner serverInfo={serverInfo} />
          </div>
        )}

        <Separator className="my-12" />

        {/* Tabbed Content */}
        <Tabs defaultSelectedKey="rankings" className="w-full">
          <Tabs.ListContainer className="mb-8">
            <Tabs.List aria-label="Server sections" className="flex justify-center">
              <Tabs.Tab id="rankings">
                🏆 {t('public:tabs.rankings')}
                <Tabs.Indicator />
              </Tabs.Tab>
              <Tabs.Tab id="rules">
                📜 {t('public:tabs.rulesNap')}
                <Tabs.Indicator />
              </Tabs.Tab>
              <Tabs.Tab id="power-trends">
                📊 {t('public:tabs.powerAnalytics')}
                <Tabs.Indicator />
              </Tabs.Tab>
            </Tabs.List>
          </Tabs.ListContainer>

          {/* Rankings Tab */}
          <Tabs.Panel id="rankings">
            {alliances && alliances.length > 0 && (
              <>
                <div className="mb-16">
                  <AlliancePodium alliances={alliances} />
                </div>

                <Separator className="my-12" />

                <AllianceGrid alliances={alliances} />
              </>
            )}
          </Tabs.Panel>

          {/* Rules & NAP15 Tab */}
          <Tabs.Panel id="rules">
            <div className="mb-16">
              <ServerRules />
            </div>

            <Separator className="my-12" />

            <SignatureStatus />

            <Separator className="my-12" />

            <CouncilMembers />

            <Separator className="my-12" />

            <Signatories />

            <Separator className="my-12" />

            <CouncilRotation />
          </Tabs.Panel>

          {/* Power Analytics Tab */}
          <Tabs.Panel id="power-trends">
            <PowerDistributionEnhanced />

            <Separator className="my-12" />

            <PowerTrendsEnhanced />
          </Tabs.Panel>
        </Tabs>

        {/* Footer */}
        <footer className="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
          <Card variant="secondary" className="p-6">
            <Card.Header className="items-center text-center">
              <Card.Title className="text-2xl">{t('public:footer.title')}</Card.Title>
              <Card.Description>
                {t('public:footer.subtitle')}
              </Card.Description>
            </Card.Header>
            <Card.Content className="mt-4">
              {/* Quick Links */}
              <div className="flex flex-wrap justify-center gap-4 mb-4">
                <Link
                  href="https://github.com/k33bz/Server1586-clean"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm hover:underline"
                >
                  📚 {t('public:footer.github')}
                </Link>
                <Link
                  href="https://github.com/k33bz/Server1586-clean/issues"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm hover:underline"
                >
                  🐛 {t('public:footer.issues')}
                </Link>
                <Link
                  href="https://github.com/k33bz/Server1586-clean/blob/main/README.md"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm hover:underline"
                >
                  📖 {t('public:footer.documentation')}
                </Link>
                <Link
                  href="/admin/dashboard.php"
                  className="text-sm hover:underline"
                >
                  🔐 {t('public:footer.adminDashboard')}
                </Link>
              </div>

              {/* Build Info */}
              <div className="text-center">
                <p className="text-xs opacity-60">
                  {t('public:footer.builtWith')} &middot; {t('public:footer.poweredBy')}
                </p>
                <p className="text-xs opacity-60 mt-1">
                  🤖 {t('public:footer.generatedWith')} <Link href="https://claude.com/claude-code" target="_blank" rel="noopener noreferrer" className="text-xs hover:underline">Claude Code</Link> &middot; {t('public:footer.enhancedBy')} <Link href="https://kiro.dev/" target="_blank" rel="noopener noreferrer" className="text-xs hover:underline">Kiro</Link>
                </p>
                <p className="text-xs opacity-40 mt-1">
                  v{versionInfo?.version || '3.7.0'} &middot; {new Date().getFullYear()}
                </p>
              </div>
            </Card.Content>
          </Card>
        </footer>
      </div>

      {/* Back to Top Button */}
      <BackToTop />

      {/* Floating Theme Toggle */}
      <FloatingThemeToggle />

      {/* Floating Language Switcher */}
      <div className="fixed top-24 right-8 z-50">
        <LanguageSwitcher />
      </div>
    </div>
  );
}
