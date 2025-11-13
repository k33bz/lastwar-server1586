import { Card } from '@heroui/react';
import { useApi } from '../hooks/useApi';

interface SignatureHistoryData {
  currentRulesVersion: string;
  lastUpdated: string;
  alliances: Array<{
    tag: string;
    name: string;
    rank: number;
    r5History: Array<{
      r5Name: string;
      current: boolean;
      signatures: Array<{
        version: string;
        signedAt: string;
        signedBy: string;
        notes?: string;
      }>;
    }>;
  }>;
}

export function SignatureStatus() {
  const { data: signatureData, loading, error } = useApi<SignatureHistoryData>('signature-history.json');

  if (loading) {
    return (
      <Card variant="secondary" className="p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-4 bg-gray-300 dark:bg-gray-700 rounded w-3/4"></div>
          <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
            {[...Array(15)].map((_, i) => (
              <div key={i} className="h-16 bg-gray-300 dark:bg-gray-700 rounded"></div>
            ))}
          </div>
        </div>
      </Card>
    );
  }

  if (error || !signatureData) {
    return (
      <Card variant="quaternary" className="p-6 border-2 border-red-500/20">
        <p className="text-red-500">Failed to load signature data</p>
      </Card>
    );
  }

  const currentVersion = signatureData.currentRulesVersion;

  // Helper function to get version difference (how many versions behind)
  const getVersionDistance = (signedVersion: string): number => {
    const current = parseFloat(currentVersion);
    const signed = parseFloat(signedVersion);
    return current - signed;
  };

  // Helper function to get color based on version distance
  const getVersionColor = (signedVersion: string | null): {
    bg: string;
    border: string;
    text: string;
    status: string;
  } => {
    if (!signedVersion) {
      return {
        bg: 'bg-red-50 dark:bg-red-900/10',
        border: 'border-red-500/30',
        text: 'text-red-600 dark:text-red-400',
        status: 'Not Signed'
      };
    }

    const distance = getVersionDistance(signedVersion);

    if (distance === 0) {
      // Current version - green
      return {
        bg: 'bg-green-50 dark:bg-green-900/10',
        border: 'border-green-500/30',
        text: 'text-green-600 dark:text-green-400',
        status: 'Current'
      };
    } else if (distance === 0.1) {
      // 0.1 versions behind - yellow-green
      return {
        bg: 'bg-lime-50 dark:bg-lime-900/10',
        border: 'border-lime-500/30',
        text: 'text-lime-600 dark:text-lime-400',
        status: 'Slightly Outdated'
      };
    } else if (distance === 0.2) {
      // 0.2 versions behind - yellow
      return {
        bg: 'bg-yellow-50 dark:bg-yellow-900/10',
        border: 'border-yellow-500/30',
        text: 'text-yellow-600 dark:text-yellow-400',
        status: 'Outdated'
      };
    } else {
      // 0.3+ versions behind - orange/red
      return {
        bg: 'bg-orange-50 dark:bg-orange-900/10',
        border: 'border-orange-500/30',
        text: 'text-orange-600 dark:text-orange-400',
        status: 'Very Outdated'
      };
    }
  };

  // Calculate signature statistics (only count current version as "signed")
  const totalAlliances = signatureData.alliances.length;
  const currentVersionAlliances = signatureData.alliances.filter(alliance => {
    const currentR5 = alliance.r5History?.find(r5 => r5.current);
    const latestSignature = currentR5?.signatures[currentR5.signatures.length - 1];
    return latestSignature && latestSignature.version === currentVersion;
  });
  const signedCount = currentVersionAlliances.length;

  const outdatedAlliances = signatureData.alliances.filter(alliance => {
    const currentR5 = alliance.r5History?.find(r5 => r5.current);
    const latestSignature = currentR5?.signatures[currentR5.signatures.length - 1];
    return latestSignature && latestSignature.version !== currentVersion;
  });
  const outdatedCount = outdatedAlliances.length;

  const unsignedAlliances = signatureData.alliances.filter(alliance => {
    const currentR5 = alliance.r5History?.find(r5 => r5.current);
    return !currentR5 || currentR5.signatures.length === 0;
  });
  const unsignedCount = unsignedAlliances.length;

  const adoptionRate = ((signedCount / totalAlliances) * 100).toFixed(1);

  return (
    <Card variant="secondary" className="p-6">
      <Card.Header>
        <Card.Title className="text-2xl">📝 NAP15 Signature Status</Card.Title>
        <Card.Description>
          Top 15 Alliance Adoption of Rules v{signatureData.currentRulesVersion}
        </Card.Description>
      </Card.Header>

      <Card.Content className="mt-6">
        {/* Summary Statistics */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          {/* Adoption Rate */}
          <div className="p-4 bg-accent/10 border-2 border-accent/30 rounded-lg text-center">
            <div className="text-3xl font-bold text-accent">{adoptionRate}%</div>
            <div className="text-sm opacity-75 mt-1">Current Version</div>
          </div>

          {/* Current Version Signed */}
          <div className="p-4 bg-green-50 dark:bg-green-900/20 border-2 border-green-500/30 rounded-lg text-center">
            <div className="text-3xl font-bold text-green-600 dark:text-green-400">{signedCount}</div>
            <div className="text-sm opacity-75 mt-1">Up to Date</div>
          </div>

          {/* Outdated */}
          <div className="p-4 bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-500/30 rounded-lg text-center">
            <div className="text-3xl font-bold text-orange-600 dark:text-orange-400">{outdatedCount}</div>
            <div className="text-sm opacity-75 mt-1">Outdated</div>
          </div>

          {/* Unsigned */}
          <div className="p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-500/30 rounded-lg text-center">
            <div className="text-3xl font-bold text-red-600 dark:text-red-400">{unsignedCount}</div>
            <div className="text-sm opacity-75 mt-1">Not Signed</div>
          </div>
        </div>

        {/* Alliance Grid */}
        <div className="space-y-2">
          <h3 className="font-semibold text-sm opacity-75 mb-3">Top 15 Alliances</h3>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {signatureData.alliances.map((alliance) => {
              const currentR5 = alliance.r5History?.find(r5 => r5.current);
              const latestSignature = currentR5?.signatures[currentR5.signatures.length - 1];
              const colorScheme = getVersionColor(latestSignature?.version || null);

              const isCurrent = latestSignature?.version === currentVersion;
              const isOutdated = latestSignature && latestSignature.version !== currentVersion;

              // Icon based on status
              const getStatusIcon = () => {
                if (isCurrent) return '✅';
                if (isOutdated) return '⚠️';
                return '❌';
              };

              return (
                <div
                  key={alliance.tag}
                  className={`p-4 rounded-lg border-2 transition-all ${colorScheme.bg} ${colorScheme.border}`}
                >
                  {/* Alliance Info */}
                  <div className="flex items-start justify-between mb-2">
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-bold opacity-60">#{alliance.rank}</span>
                        <span className="font-bold truncate">{alliance.tag}</span>
                      </div>
                      <div className="text-xs opacity-75 truncate">{alliance.name}</div>
                    </div>

                    {/* Status Icon */}
                    <div className="text-2xl ml-2 flex-shrink-0">
                      {getStatusIcon()}
                    </div>
                  </div>

                  {/* Signature Details */}
                  {latestSignature ? (
                    <div className="text-xs space-y-1">
                      <div className="flex justify-between items-center">
                        <span className="opacity-75">Version:</span>
                        <span className={`font-semibold ${colorScheme.text}`}>
                          v{latestSignature.version}
                          {isOutdated && ' (old)'}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="opacity-75">Status:</span>
                        <span className={`font-semibold ${colorScheme.text}`}>
                          {colorScheme.status}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="opacity-75">Signed:</span>
                        <span className="font-semibold opacity-75">
                          {new Date(latestSignature.signedAt).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                  ) : (
                    <div className={`text-xs font-semibold ${colorScheme.text}`}>
                      {colorScheme.status}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Outdated Signatures Alert */}
        {outdatedCount > 0 && (
          <div className="mt-6 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-500/30 rounded-lg">
            <div className="flex items-start gap-3">
              <span className="text-2xl">⚠️</span>
              <div>
                <p className="font-semibold text-sm">Outdated Signatures</p>
                <p className="text-sm opacity-75 mt-1">
                  The following alliances need to update their signatures to v{currentVersion}:{' '}
                  <span className="font-semibold">
                    {outdatedAlliances.map(a => a.tag).join(', ')}
                  </span>
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Unsigned Alliances Alert */}
        {unsignedCount > 0 && (
          <div className="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-500/30 rounded-lg">
            <div className="flex items-start gap-3">
              <span className="text-2xl">❌</span>
              <div>
                <p className="font-semibold text-sm">Not Signed</p>
                <p className="text-sm opacity-75 mt-1">
                  The following alliances have not yet signed the NAP15 rules:{' '}
                  <span className="font-semibold">
                    {unsignedAlliances.map(a => a.tag).join(', ')}
                  </span>
                </p>
              </div>
            </div>
          </div>
        )}
      </Card.Content>
    </Card>
  );
}
