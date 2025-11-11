import { useEffect, useState } from 'react';
import { Card, Slider, Tabs } from '@heroui/react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  TimeScale,
} from 'chart.js';
import type { ChartOptions } from 'chart.js';
import { Chart } from 'react-chartjs-2';
import 'chartjs-adapter-date-fns';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  TimeScale
);

interface PowerHistoryData {
  datetime: string;
  [alliance: string]: string | number;
}

export function PowerTrends() {
  const [powerData, setPowerData] = useState<PowerHistoryData[]>([]);
  const [rangeValue, setRangeValue] = useState<number[]>([1, 5]); // Default to alliances 1-5
  const [season, setSeason] = useState<'all' | 'current'>('current');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // SECURITY: Fetch from API endpoint instead of direct /data/ access
    fetch('/api/power-history.php')
      .then(response => response.text())
      .then(csv => {
        // Parse CSV with proper handling of quoted fields and commas
        const parseCSVLine = (line: string): string[] => {
          const result: string[] = [];
          let current = '';
          let inQuotes = false;

          for (let i = 0; i < line.length; i++) {
            const char = line[i];

            if (char === '"') {
              inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
              result.push(current.trim());
              current = '';
            } else {
              current += char;
            }
          }
          result.push(current.trim());
          return result;
        };

        const lines = csv.trim().split('\n').filter(line => line.trim());
        if (lines.length === 0) {
          setLoading(false);
          return;
        }

        const headers = parseCSVLine(lines[0]);

        // Sanitize headers to prevent injection attacks
        const sanitizedHeaders = headers.map(h => h.replace(/[^a-zA-Z0-9_-]/g, ''));

        const data = lines.slice(1).map(line => {
          const values = parseCSVLine(line);
          const row: PowerHistoryData = { datetime: values[0] || '' };

          for (let i = 1; i < sanitizedHeaders.length && i < values.length; i++) {
            // Validate and parse numeric values only
            const value = parseInt(values[i], 10);
            row[sanitizedHeaders[i]] = isNaN(value) || value < 0 ? 0 : value;
          }

          return row;
        });

        // Sort by date (oldest first)
        data.sort((a, b) => new Date(a.datetime).getTime() - new Date(b.datetime).getTime());

        setPowerData(data);
        setLoading(false);
      })
      .catch(err => {
        console.error('Failed to load power history:', err);
        setLoading(false);
      });
  }, []);

  if (loading || powerData.length === 0) {
    return null;
  }

  // Get unique alliance tags (excluding datetime column)
  const alliances = Object.keys(powerData[0]).filter(key => key !== 'datetime');

  // Use the LATEST data point to determine top alliances (most accurate ranking)
  const latestData = powerData[powerData.length - 1];
  const alliancePowers = alliances.map(tag => ({
    tag,
    power: Number(latestData[tag]) || 0,
  }));

  // Sort by power
  const sortedAlliances = alliancePowers
    .filter(a => a.power > 0) // Exclude alliances with no power
    .sort((a, b) => b.power - a.power);

  // Get alliances in the selected rank range (e.g., ranks 1-5 = indexes 0-4)
  const startIndex = rangeValue[0] - 1; // Convert rank to 0-based index
  const endIndex = rangeValue[1]; // Slice is exclusive of end, so this works perfectly
  const topAlliances = sortedAlliances.slice(startIndex, endIndex).map(a => a.tag);

  // Filter data by season
  let filteredData = powerData;
  if (season === 'current') {
    // Season 1: September 29, 2025 (Day 1) to November 23, 2025 (Day 56 / Week 8)
    // Current: Day 44 (November 11, 2025) - 12 days remaining
    const season1Start = new Date('2025-09-29T00:00:00');
    const season1End = new Date('2025-11-23T23:59:59');

    filteredData = powerData.filter(row => {
      const rowDate = new Date(row.datetime);
      return rowDate >= season1Start && rowDate <= season1End;
    });
  }

  // Generate vibrant colors for each alliance
  const colors = [
    'rgb(239, 68, 68)',    // red-500
    'rgb(59, 130, 246)',   // blue-500
    'rgb(234, 179, 8)',    // yellow-500
    'rgb(34, 197, 94)',    // green-500
    'rgb(168, 85, 247)',   // purple-500
    'rgb(249, 115, 22)',   // orange-500
    'rgb(236, 72, 153)',   // pink-500
    'rgb(20, 184, 166)',   // teal-500
    'rgb(99, 102, 241)',   // indigo-500
    'rgb(132, 204, 22)',   // lime-500
    'rgb(244, 63, 94)',    // rose-500
    'rgb(14, 165, 233)',   // sky-500
    'rgb(251, 146, 60)',   // amber-500
    'rgb(217, 70, 239)',   // fuchsia-500
    'rgb(6, 182, 212)',    // cyan-500
  ];

  const datasets = topAlliances.map((tag, idx) => ({
    label: `[${tag}]`,
    data: filteredData.map(row => ({
      x: new Date(row.datetime).getTime(),
      y: Number(row[tag]) || null, // Use null instead of 0 to break the line
    })),
    borderColor: colors[idx % colors.length],
    backgroundColor: colors[idx % colors.length],
    borderWidth: 2,
    pointRadius: 4,
    pointHoverRadius: 6,
    tension: 0.4,
    spanGaps: false, // Don't connect points across null values
  }));

  const chartData = {
    datasets,
  };

  const options: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'nearest',
      axis: 'x',
      intersect: false,
    },
    plugins: {
      legend: {
        position: 'top' as const,
        labels: {
          color: 'rgb(156, 163, 175)',
          font: {
            size: 11,
            weight: 'bold',
          },
          padding: 12,
          usePointStyle: true,
        },
      },
      title: {
        display: false,
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleFont: {
          size: 14,
          weight: 'bold',
        },
        bodyFont: {
          size: 13,
        },
        callbacks: {
          label: (context) => {
            const value = context.parsed.y ?? 0;
            if (value === 0) return `${context.dataset.label}: No data`;
            return `${context.dataset.label}: ${value.toLocaleString()}`;
          },
        },
      },
    },
    scales: {
      x: {
        type: 'time',
        time: {
          unit: 'day',
          displayFormats: {
            day: 'MMM d',
          },
        },
        ticks: {
          color: 'rgb(156, 163, 175)',
          maxRotation: 45,
          minRotation: 0,
        },
        grid: {
          color: 'rgba(156, 163, 175, 0.1)',
        },
      },
      y: {
        beginAtZero: false,
        ticks: {
          color: 'rgb(156, 163, 175)',
          callback: (value) => {
            const num = Number(value);
            if (num >= 1000000000) return (num / 1000000000).toFixed(1) + 'B';
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
            return num.toLocaleString();
          },
        },
        grid: {
          color: 'rgba(156, 163, 175, 0.1)',
        },
      },
    },
  };

  const lastUpdate = filteredData.length > 0
    ? new Date(filteredData[filteredData.length - 1].datetime).toLocaleString()
    : 'N/A';

  return (
    <section className="mb-16" id="power-trends">
      <h2 className="text-4xl font-bold text-center mb-8">Alliance Power Trends</h2>

      <Card variant="secondary" className="p-6">
        {/* Controls */}
        <div className="mb-6">
          <div className="flex flex-col gap-6">
            {/* Alliance Rank Range Selector */}
            <div>
              <div className="flex items-center justify-between mb-3">
                <label className="text-sm font-semibold">
                  Alliance Rank Range
                </label>
                <span className="text-accent font-bold text-lg">
                  Ranks {rangeValue[0]} - {rangeValue[1]}
                </span>
              </div>
              <Slider
                className="w-full mb-2"
                value={rangeValue}
                onChange={(value) => setRangeValue(value as number[])}
                minValue={1}
                maxValue={sortedAlliances.length}
                step={1}
                aria-label="Alliance rank range to display"
              >
                <Slider.Track className="h-2 bg-gray-300 dark:bg-gray-600 rounded-full">
                  {({state}) => (
                    <>
                      <Slider.Fill className="bg-accent h-full rounded-full" />
                      {state.values.map((_, i) => (
                        <Slider.Thumb
                          key={i}
                          index={i}
                          className="w-5 h-5 bg-white dark:bg-gray-200 border-2 border-accent rounded-full shadow-lg cursor-pointer hover:scale-110 transition-transform"
                        />
                      ))}
                    </>
                  )}
                </Slider.Track>
              </Slider>
              <div className="flex justify-between text-xs opacity-60 px-1">
                <span>Rank 1</span>
                <span>Rank {sortedAlliances.length}</span>
              </div>
            </div>

            {/* Season Filter */}
            <div>
              <label className="text-sm font-semibold mb-3 block">
                Time Period
              </label>
              <Tabs
                selectedKey={season}
                onSelectionChange={(key) => setSeason(key as 'all' | 'current')}
              >
                <Tabs.ListContainer>
                  <Tabs.List aria-label="Season filter">
                    <Tabs.Tab id="current">
                      Season 1 (Sep 29 - Nov 23)
                      <Tabs.Indicator />
                    </Tabs.Tab>
                    <Tabs.Tab id="all">
                      All-Time
                      <Tabs.Indicator />
                    </Tabs.Tab>
                  </Tabs.List>
                </Tabs.ListContainer>
              </Tabs>
            </div>
          </div>

          <p className="text-xs opacity-60 mt-4 text-center">
            Rankings based on latest data point: {lastUpdate}
          </p>
        </div>

        {/* Chart */}
        <div className="h-96 mb-6">
          <Chart type="line" data={chartData} options={options} />
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm opacity-75">
          <div>
            <strong>Last Updated:</strong> {lastUpdate}
          </div>
          <div>
            <strong>Data Points:</strong> {filteredData.length}
          </div>
          <div>
            <strong>Alliances Tracked:</strong> {topAlliances.length}
          </div>
        </div>
      </Card>
    </section>
  );
}
