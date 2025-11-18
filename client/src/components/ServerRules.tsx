import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Accordion, Switch, Chip } from '@heroui/react';
import { useApi } from '../hooks/useApi';
import DOMPurify from 'dompurify';

interface Amendment {
  version: string;
  date: string;
  changes: {
    type: 'add' | 'remove' | 'modify';
    text: string;
  }[];
}

interface RuleSection {
  title: string;
  content?: string[];
  items?: string[];
  amendments?: Amendment[];
}

export function ServerRules() {
  const { t, i18n } = useTranslation('public');
  const [showDiff, setShowDiff] = useState(false);

  // Load language-specific rules file, fallback to en-US if not found
  const rulesFile = `rules-${i18n.language}.json`;
  const { data: rules, error, loading: rulesLoading } = useApi<RuleSection[]>(rulesFile);
  const { data: fallbackRules } = useApi<RuleSection[]>('rules-en-US.json');

  // Use translated rules if available, otherwise fall back to English
  const displayRules = (error && fallbackRules) ? fallbackRules : rules;
  const loading = rulesLoading;

  // Sanitize HTML to prevent XSS attacks
  const sanitizeHtml = (html: string): string => {
    return DOMPurify.sanitize(html, {
      ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'a', 'br', 'p', 'span', 'ul', 'ol', 'li'],
      ALLOWED_ATTR: ['href', 'target', 'rel']
    });
  };

  if (loading) return null;
  if (!displayRules || displayRules.length === 0) return null;

  const getChangeBackground = (type: 'add' | 'remove' | 'modify') => {
    switch (type) {
      case 'add':
        return 'bg-green-500/20 dark:bg-green-500/30 border-l-4 border-green-500';
      case 'remove':
        return 'bg-red-500/20 dark:bg-red-500/30 border-l-4 border-red-500 line-through opacity-75';
      case 'modify':
        return 'bg-yellow-500/20 dark:bg-yellow-500/30 border-l-4 border-yellow-500';
    }
  };

  const getChangeChipColor = (type: 'add' | 'remove' | 'modify') => {
    switch (type) {
      case 'add':
        return 'success';
      case 'remove':
        return 'danger';
      case 'modify':
        return 'warning';
    }
  };

  return (
    <section className="mb-12" id="rules">
      <div className="bg-surface rounded-lg p-6">
        {/* Header with Toggle */}
        <div className="mb-6 flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold mb-2">{t('rules.title')}</h2>
            <span className="text-sm opacity-60">{t('rules.subtitle')}</span>
          </div>
          <Switch isSelected={showDiff} onChange={setShowDiff}>
            <Switch.Control>
              <Switch.Thumb />
            </Switch.Control>
            <span className="text-sm font-medium ml-2">{t('rules.showChanges')}</span>
          </Switch>
        </div>

        {/* Rules Content */}
        <Accordion
          allowsMultipleExpanded
          className="space-y-4"
        >
          {displayRules.map((section, sectionIdx) => {
            const hasChanges = (section.amendments?.length || 0) > 0;

            return (
              <Accordion.Item
                key={`section-${sectionIdx}`}
                id={`section-${sectionIdx}`}
                className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
              >
                <Accordion.Heading>
                  <Accordion.Trigger className="w-full px-4 py-3 text-left hover:bg-accent/5 transition-colors flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className="font-semibold text-lg" dangerouslySetInnerHTML={{ __html: sanitizeHtml(section.title) }} />
                      {hasChanges && showDiff && (
                        <Chip color="accent" size="sm">
                          {t('rules.versions', { count: section.amendments!.length })}
                        </Chip>
                      )}
                    </div>
                    <Accordion.Indicator />
                  </Accordion.Trigger>
                </Accordion.Heading>
                <Accordion.Panel>
                  <Accordion.Body className="px-4 py-3 space-y-3">
                    {!showDiff ? (
                      // Normal View - Show current rules including additions
                      <>
                        {section.content && section.content.map((para, idx) => (
                          <p key={idx} className="text-sm leading-relaxed" dangerouslySetInnerHTML={{ __html: sanitizeHtml(para) }} />
                        ))}
                        {section.items && (
                          <ul className="list-none space-y-2">
                            {section.items.map((item, idx) => (
                              <li key={idx} className="flex gap-3 text-sm">
                                <span className="text-accent mt-1">•</span>
                                <span className="flex-1" dangerouslySetInnerHTML={{ __html: sanitizeHtml(item) }} />
                              </li>
                            ))}
                          </ul>
                        )}

                        {/* Show additions from amendments in normal view */}
                        {section.amendments && section.amendments.length > 0 && (
                          <div className="space-y-2 mt-4">
                            {section.amendments.flatMap(amendment =>
                              amendment.changes
                                .filter(change => change.type === 'add')
                                .map((change, idx) => (
                                  <p key={`add-${idx}`} className="text-sm leading-relaxed" dangerouslySetInnerHTML={{ __html: sanitizeHtml(change.text) }} />
                                ))
                            )}
                          </div>
                        )}
                      </>
                    ) : (
                      // Diff View - Show changes with colored backgrounds
                      <div className="space-y-4">
                        {/* Show current content first */}
                        <div className="space-y-2">
                          <div className="text-xs font-semibold opacity-60 uppercase mb-2">{t('rules.currentVersion')}</div>
                          {section.content && section.content.map((para, idx) => (
                            <p key={idx} className="text-sm leading-relaxed px-3 py-2" dangerouslySetInnerHTML={{ __html: sanitizeHtml(para) }} />
                          ))}
                          {section.items && (
                            <ul className="list-none space-y-2">
                              {section.items.map((item, idx) => (
                                <li key={idx} className="flex gap-3 text-sm px-3 py-2">
                                  <span className="text-accent mt-1">•</span>
                                  <span className="flex-1" dangerouslySetInnerHTML={{ __html: sanitizeHtml(item) }} />
                                </li>
                              ))}
                            </ul>
                          )}
                        </div>

                        {/* Show amendments if they exist */}
                        {section.amendments && section.amendments.length > 0 && (
                          <div className="space-y-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            {section.amendments.map((amendment, amendIdx) => (
                              <div key={amendIdx} className="space-y-2">
                                <div className="flex items-center gap-2 mb-3">
                                  <Chip color="accent" size="sm">v{amendment.version}</Chip>
                                  <span className="text-xs opacity-60">
                                    {new Date(amendment.date).toLocaleDateString()}
                                  </span>
                                </div>
                                <div className="space-y-2">
                                  {amendment.changes.map((change, changeIdx) => (
                                    <div
                                      key={changeIdx}
                                      className={`px-3 py-2 rounded ${getChangeBackground(change.type)}`}
                                    >
                                      <div className="flex items-start gap-3">
                                        <Chip
                                          color={getChangeChipColor(change.type)}
                                          size="sm"
                                          className="mt-0.5 flex-shrink-0"
                                        >
                                          {change.type}
                                        </Chip>
                                        <div
                                          className="text-sm flex-1"
                                          dangerouslySetInnerHTML={{ __html: sanitizeHtml(change.text) }}
                                        />
                                      </div>
                                    </div>
                                  ))}
                                </div>
                              </div>
                            ))}
                          </div>
                        )}

                        {(!section.amendments || section.amendments.length === 0) && (
                          <div className="text-sm opacity-60 italic px-3 py-2">
                            {t('rules.noChanges')}
                          </div>
                        )}
                      </div>
                    )}
                  </Accordion.Body>
                </Accordion.Panel>
              </Accordion.Item>
            );
          })}
        </Accordion>
      </div>
    </section>
  );
}
