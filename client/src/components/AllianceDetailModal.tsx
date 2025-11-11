import { Avatar, Chip, CloseButton } from '@heroui/react';
import type { Alliance } from '../types';

interface AllianceDetailModalProps {
  alliance: Alliance | null;
  rank: number;
  onClose: () => void;
}

export function AllianceDetailModal({ alliance, rank, onClose }: AllianceDetailModalProps) {
  if (!alliance) return null;

  const formatPower = (power: number | null | undefined) => {
    if (!power || power === 0) return 'N/A';
    if (power >= 1_000_000_000) {
      return `${(power / 1_000_000_000).toFixed(2)}B`;
    }
    if (power >= 1_000_000) {
      return `${(power / 1_000_000).toFixed(2)}M`;
    }
    return power.toLocaleString();
  };

  return (
    <div
      className="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      onClick={onClose}
    >
      <div
        className="max-w-2xl w-full max-h-[90vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border-2 border-gray-200 dark:border-gray-700 p-8"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-start justify-between gap-4 pb-6 border-b border-gray-200 dark:border-gray-700 mb-6">
          <div className="flex items-center gap-4 flex-1">
            <Avatar size="lg">
              {alliance.discord?.logoUrl && (
                <Avatar.Image src={alliance.discord.logoUrl} alt={alliance.name} />
              )}
              <Avatar.Fallback className="text-2xl font-bold bg-indigo-600 text-white">
                {alliance.tag}
              </Avatar.Fallback>
            </Avatar>
            <div>
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white">{alliance.name}</h2>
              <p className="text-base text-gray-600 dark:text-gray-300">
                #{rank} • [{alliance.tag}]
              </p>
            </div>
          </div>
          <CloseButton
            onPress={onClose}
            aria-label="Close alliance details"
            className="flex-shrink-0"
          />
        </div>

        <div className="space-y-6">
          {/* Alliance Description */}
          {alliance.info?.description && (
            <div className="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-xl border-l-4 border-blue-500">
              <p className="text-gray-800 dark:text-gray-200 italic">{alliance.info.description}</p>
            </div>
          )}

          {/* Basic Information */}
          <div>
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Basic Information</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Rank</div>
                <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">#{rank}</div>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Power</div>
                <div className="text-2xl font-bold text-gray-900 dark:text-white">{formatPower(alliance.power)}</div>
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">R5 Leader</div>
                <div className="font-bold text-gray-900 dark:text-white">{alliance.r5.name}</div>
                {alliance.r5.gameId && (
                  <div className="text-xs text-gray-600 dark:text-gray-400 mt-1">ID: {alliance.r5.gameId}</div>
                )}
              </div>
              <div className="bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">NAP15 Status</div>
                <div className="mt-2">
                  {alliance.signed ? (
                    <Chip color="success" size="sm">✓ Signed</Chip>
                  ) : (
                    <Chip color="warning" size="sm">⏳ Pending</Chip>
                  )}
                </div>
              </div>
            </div>
          </div>

          {/* Alliance Info */}
          {alliance.info && (
            <div className="bg-green-50 dark:bg-green-900/30 p-6 rounded-xl">
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Alliance Details</h3>
              <div className="grid grid-cols-2 gap-4">
                {alliance.info.languages && alliance.info.languages.length > 0 && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Languages</div>
                    <div className="font-bold text-gray-900 dark:text-white">{alliance.info.languages.join(', ')}</div>
                  </div>
                )}
                {alliance.info.timezone && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Timezone</div>
                    <div className="font-bold text-gray-900 dark:text-white">{alliance.info.timezone}</div>
                  </div>
                )}
                <div>
                  <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Recruiting</div>
                  <div className="mt-1">
                    {alliance.info.recruiting ? (
                      <Chip color="success" size="sm">✓ Open</Chip>
                    ) : (
                      <Chip color="danger" size="sm">✗ Closed</Chip>
                    )}
                  </div>
                </div>
                {alliance.info.founded && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Founded</div>
                    <div className="font-bold text-gray-900 dark:text-white">
                      {new Date(alliance.info.founded).toLocaleDateString()}
                    </div>
                  </div>
                )}
              </div>

              {/* Requirements */}
              {alliance.info.requirements && (
                <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                  <div className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Requirements</div>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Min Power: </span>
                      <span className="font-bold text-gray-900 dark:text-white">
                        {formatPower(alliance.info.requirements.minPower)}
                      </span>
                    </div>
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Min Level: </span>
                      <span className="font-bold text-gray-900 dark:text-white">{alliance.info.requirements.minLevel}</span>
                    </div>
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Activity: </span>
                      <span className="font-bold text-gray-900 dark:text-white">{alliance.info.requirements.activity}</span>
                    </div>
                  </div>
                  {alliance.info.requirements.notes && (
                    <div className="mt-3 text-sm">
                      <span className="text-gray-600 dark:text-gray-400">Notes: </span>
                      <span className="text-gray-900 dark:text-white">{alliance.info.requirements.notes}</span>
                    </div>
                  )}
                </div>
              )}
            </div>
          )}

          {/* Achievements */}
          {alliance.achievements && (alliance.achievements.peakRank || alliance.achievements.peakPower || (alliance.achievements.specialties && alliance.achievements.specialties.length > 0)) && (
            <div className="bg-yellow-50 dark:bg-yellow-900/30 p-6 rounded-xl">
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">🏆 Achievements</h3>
              <div className="grid grid-cols-2 gap-4 mb-4">
                {alliance.achievements.peakRank && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Peak Rank</div>
                    <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">#{alliance.achievements.peakRank}</div>
                  </div>
                )}
                {alliance.achievements.peakPower && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Peak Power</div>
                    <div className="text-2xl font-bold text-gray-900 dark:text-white">
                      {formatPower(alliance.achievements.peakPower)}
                    </div>
                  </div>
                )}
              </div>
              {alliance.achievements.specialties && alliance.achievements.specialties.length > 0 && (
                <div>
                  <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">Specialties</div>
                  <div className="flex flex-wrap gap-2">
                    {alliance.achievements.specialties.map((specialty, idx) => (
                      <Chip key={idx} color="default" size="sm">{specialty}</Chip>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Discord Information */}
          {alliance.discord && (
            <div className="bg-indigo-50 dark:bg-indigo-900/30 p-6 rounded-xl">
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Discord Server</h3>
              <div className="space-y-3">
                <div>
                  <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Server Name</div>
                  <div className="font-bold text-gray-900 dark:text-white">{alliance.discord.serverName}</div>
                </div>
                {alliance.discord.inviteUrl && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Invite Link</div>
                    <a
                      href={alliance.discord.inviteUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold break-all"
                    >
                      {alliance.discord.inviteUrl}
                    </a>
                  </div>
                )}
                {alliance.discord.memberCount != null && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Members</div>
                    <div className="font-bold text-gray-900 dark:text-white">{alliance.discord.memberCount.toLocaleString()}</div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Recruitment Contact */}
          {alliance.contact && (alliance.contact.recruitmentContact || alliance.contact.discordRecruitment) && (
            <div className="bg-orange-50 dark:bg-orange-900/30 p-6 rounded-xl">
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">📞 Recruitment Contact</h3>
              <div className="space-y-3">
                {alliance.contact.recruitmentContact && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Contact</div>
                    <div className="font-bold text-gray-900 dark:text-white">{alliance.contact.recruitmentContact}</div>
                  </div>
                )}
                {alliance.contact.discordRecruitment && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Discord Recruitment</div>
                    <div className="font-bold text-gray-900 dark:text-white">{alliance.contact.discordRecruitment}</div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Cross-Server Partnership */}
          {alliance.crossServer?.hasPartner && (
            <div className="bg-purple-50 dark:bg-purple-900/30 p-6 rounded-xl">
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Cross-Server Partnership</h3>
              <div className="space-y-3">
                <Chip color="accent" size="sm">Active Partnership</Chip>
                {alliance.crossServer.partnerTag && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Partner Alliance</div>
                    <div className="font-bold text-gray-900 dark:text-white">[{alliance.crossServer.partnerTag}]</div>
                  </div>
                )}
                {alliance.crossServer.servers && alliance.crossServer.servers.length > 0 && (
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Partner Servers</div>
                    <div className="font-bold text-gray-900 dark:text-white">
                      {alliance.crossServer.servers.join(', ')}
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
