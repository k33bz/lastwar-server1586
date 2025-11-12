/**
 * Power Analytics Layout Options
 *
 * This file demonstrates different layout approaches for analytics charts:
 * 1. Stacked Layout (default)
 * 2. Grid Layout (responsive)
 * 3. With Fullscreen Mode
 */

import { Separator } from '@heroui/react';
import { PowerDistributionEnhanced } from './PowerDistributionEnhanced';
import { PowerTrendsEnhanced } from './PowerTrendsEnhanced';

/**
 * OPTION 1: Stacked Layout (Current Implementation)
 *
 * Best for: Most use cases, mobile-first
 * Pros: Simple, works great on all screen sizes
 * Cons: None really - it's the recommended approach
 */
export function PowerAnalyticsStacked() {
  return (
    <>
      <PowerDistributionEnhanced />
      <Separator className="my-12" />
      <PowerTrendsEnhanced />
    </>
  );
}

/**
 * OPTION 2: Grid Layout (Side-by-Side on Desktop)
 *
 * Best for: Large screens where side-by-side comparison is valuable
 * Pros: Visual comparison, efficient use of space on desktop
 * Cons: PowerTrends might feel cramped on smaller desktop screens
 *
 * Responsive behavior:
 * - Mobile/Tablet (< 1024px): Stacks vertically
 * - Desktop (>= 1024px): Side-by-side
 */
export function PowerAnalyticsGrid() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div>
        <PowerDistributionEnhanced />
      </div>
      <div>
        <PowerTrendsEnhanced />
      </div>
    </div>
  );
}

/**
 * OPTION 3: Hybrid Layout (Distribution in Card, Trends Full Width)
 *
 * Best for: When you want PowerTrends to have more horizontal space
 * Pros: PowerTrends gets full width for better timeline visualization
 * Cons: Takes more vertical space
 */
export function PowerAnalyticsHybrid() {
  return (
    <>
      {/* Distribution stays compact */}
      <div className="max-w-4xl mx-auto">
        <PowerDistributionEnhanced />
      </div>

      <Separator className="my-12" />

      {/* Trends gets full width */}
      <PowerTrendsEnhanced />
    </>
  );
}

/**
 * OPTION 4: Tabbed Views (Alternative Organization)
 *
 * Note: This requires importing Tabs from HeroUI
 * Best for: When you want to focus on one chart at a time
 */
export function PowerAnalyticsTabbedExample() {
  // This is just a code example - implement in your actual component
  const example = `
    import { Tabs } from '@heroui/react';

    <Tabs defaultSelectedKey="distribution">
      <Tabs.ListContainer>
        <Tabs.List>
          <Tabs.Tab id="distribution">
            Distribution View
            <Tabs.Indicator />
          </Tabs.Tab>
          <Tabs.Tab id="trends">
            Trends View
            <Tabs.Indicator />
          </Tabs.Tab>
          <Tabs.Tab id="both">
            Combined View
            <Tabs.Indicator />
          </Tabs.Tab>
        </Tabs.List>
      </Tabs.ListContainer>

      <Tabs.Panel id="distribution">
        <PowerDistributionEnhanced />
      </Tabs.Panel>

      <Tabs.Panel id="trends">
        <PowerTrendsEnhanced />
      </Tabs.Panel>

      <Tabs.Panel id="both">
        <PowerAnalyticsStacked />
      </Tabs.Panel>
    </Tabs>
  `;

  return (
    <div className="p-6 bg-gray-100 dark:bg-gray-800 rounded-lg">
      <h3 className="font-bold mb-2">Tabbed Layout Code Example:</h3>
      <pre className="text-xs overflow-x-auto">{example}</pre>
    </div>
  );
}
