<?php
/**
 * Alliance Power History
 *
 * Display power change history for user's assigned alliances
 * Uses alliance_history.json for detailed change log and power-history.csv for trends
 * R4/R5 see only their alliances, Admins/Power Editors see all
 *
 * @version 1.1.0
 * @date 2025-10-16
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'includes/alliance_helper.php';

$user = require_jwt_session();

// Set page title for header
$page_title = "Alliance Power History";

// Include shared header
include 'includes/header.php';

// Add Chart.js scripts
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>';

// Get alliances data
$alliances = AllianceHelper::loadAlliances();

// Filter alliances based on user role
$user_alliance_tags = [];
if ($user->aud === 'admin' || is_power_editor($user)) {
    // Admin and power editors see all alliances
    $user_alliance_tags = array_column($alliances, 'tag');
} else {
    // R4/R5 see only their assigned alliances
    $allowed_tags = $user->alliances ?? [];

    // Handle wildcard access
    if (in_array('*', $allowed_tags)) {
        $user_alliance_tags = array_column($alliances, 'tag');
    } else {
        $user_alliance_tags = $allowed_tags;
    }
}

// Get power history
$history_file = __DIR__ . '/../data/alliance_history.json';
$all_history = [];
if (file_exists($history_file)) {
    $history_data = json_decode(file_get_contents($history_file), true);
    if ($history_data && is_array($history_data)) {
        $all_history = $history_data;
    }
}

// Filter history for user's alliances
$user_history = array_filter($all_history, function($record) use ($user_alliance_tags) {
    return in_array($record['alliance_tag'] ?? '', $user_alliance_tags);
});

// Sort by timestamp descending (newest first)
usort($user_history, function($a, $b) {
    return strtotime($b['timestamp'] ?? '0') - strtotime($a['timestamp'] ?? '0');
});

// Limit to last 100 records
$user_history = array_slice($user_history, 0, 100);

// Get current power for each alliance
$current_power = [];
foreach ($alliances as $alliance) {
    if (in_array($alliance['tag'], $user_alliance_tags)) {
        $current_power[$alliance['tag']] = [
            'name' => $alliance['name'],
            'power' => $alliance['power']
        ];
    }
}

// Calculate statistics
$total_changes = count($user_history);
$power_increases = 0;
$power_decreases = 0;
$total_increase = 0;
$total_decrease = 0;

foreach ($user_history as $record) {
    $change = $record['power_change'] ?? 0;
    if ($change > 0) {
        $power_increases++;
        $total_increase += $change;
    } elseif ($change < 0) {
        $power_decreases++;
        $total_decrease += abs($change);
    }
}

// Load power history CSV for chart data
$power_history_csv = __DIR__ . '/../data/power-history.csv';
$chart_data = [];
if (file_exists($power_history_csv)) {
    $csv_content = file_get_contents($power_history_csv);
    $lines = explode("\n", trim($csv_content));
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = str_getcsv($line, ',', '"', '\\');
        if (count($parts) >= 3) {
            $date = $parts[0];
            $alliance = $parts[1];
            $power = intval($parts[2]);
            
            // Only include alliances user has access to
            if (in_array($alliance, $user_alliance_tags)) {
                $chart_data[] = [
                    'date' => $date,
                    'alliance' => $alliance,
                    'power' => $power
                ];
            }
        }
    }
}
?>

<div class="history-container">
    <div class="history-header">
        <h1 class="page-title">
            <span class="title-icon">📈</span>
            Alliance Power History
        </h1>
        <p class="page-subtitle">
            <?php if ($user->aud === 'admin' || is_power_editor($user)): ?>
                Viewing power change history for all alliances
            <?php else: ?>
                Viewing power change history for your assigned <?php echo count($user_alliance_tags) === 1 ? 'alliance' : 'alliances'; ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Summary Stats -->
    <div class="summary-grid">
        <div class="summary-card total">
            <div class="summary-icon">📊</div>
            <div class="summary-value"><?php echo number_format($total_changes); ?></div>
            <div class="summary-label">Total Changes</div>
        </div>

        <div class="summary-card increases">
            <div class="summary-icon">📈</div>
            <div class="summary-value"><?php echo number_format($power_increases); ?></div>
            <div class="summary-label">Power Increases</div>
            <div class="summary-detail">+<?php echo number_format($total_increase); ?> total</div>
        </div>

        <div class="summary-card decreases">
            <div class="summary-icon">📉</div>
            <div class="summary-value"><?php echo number_format($power_decreases); ?></div>
            <div class="summary-label">Power Decreases</div>
            <div class="summary-detail">-<?php echo number_format($total_decrease); ?> total</div>
        </div>

        <div class="summary-card net">
            <div class="summary-icon">⚖️</div>
            <div class="summary-value <?php echo ($total_increase - $total_decrease) >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format($total_increase - $total_decrease); ?>
            </div>
            <div class="summary-label">Net Change</div>
        </div>
    </div>

    <!-- Power Trends Chart -->
    <?php if (!empty($chart_data)): ?>
    <div class="chart-section">
        <h2 class="section-title">
            <span class="section-icon">📈</span>
            Power Trends Over Time
        </h2>
        <div class="chart-container">
            <canvas id="powerTrendsChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Current Power -->
    <div class="current-power-section">
        <h2 class="section-title">
            <span class="section-icon">⚡</span>
            Current Power
        </h2>

        <div class="current-power-grid">
            <?php if (empty($current_power)): ?>
                <div class="no-data">No alliances assigned</div>
            <?php else: ?>
                <?php foreach ($current_power as $tag => $data): ?>
                    <div class="power-card">
                        <div class="power-tag"><?php echo htmlspecialchars($tag); ?></div>
                        <div class="power-name"><?php echo htmlspecialchars($data['name']); ?></div>
                        <div class="power-value"><?php echo number_format($data['power']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Change History -->
    <div class="history-section">
        <h2 class="section-title">
            <span class="section-icon">📋</span>
            Change History
        </h2>

        <div class="history-table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Alliance</th>
                        <th>Old Power</th>
                        <th>New Power</th>
                        <th>Change</th>
                        <th>Changed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_history)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #6c757d;">
                                No power change history available
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($user_history as $record): ?>
                            <?php
                            $timestamp = $record['timestamp'] ?? '';
                            $alliance_tag = $record['alliance_tag'] ?? 'Unknown';
                            $old_power = $record['old_power'] ?? 0;
                            $new_power = $record['new_power'] ?? 0;
                            $change = $record['power_change'] ?? 0;
                            $changed_by = $record['user']['sub'] ?? 'System';

                            $change_class = $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'neutral');
                            $change_icon = $change > 0 ? '↑' : ($change < 0 ? '↓' : '—');
                            ?>
                            <tr class="history-row">
                                <td class="timestamp-cell">
                                    <?php echo date('M j, Y H:i', strtotime($timestamp)); ?>
                                </td>
                                <td class="alliance-cell">
                                    <span class="alliance-tag"><?php echo htmlspecialchars($alliance_tag); ?></span>
                                </td>
                                <td class="power-cell">
                                    <?php echo number_format($old_power); ?>
                                </td>
                                <td class="power-cell">
                                    <?php echo number_format($new_power); ?>
                                </td>
                                <td class="change-cell">
                                    <span class="change-badge <?php echo $change_class; ?>">
                                        <?php echo $change_icon; ?> <?php echo number_format(abs($change)); ?>
                                    </span>
                                </td>
                                <td class="user-cell">
                                    <?php
                                    // Show only first part of email before @
                                    $email_parts = explode('@', $changed_by);
                                    echo htmlspecialchars($email_parts[0]);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <a href="alliance_stats.php" class="action-btn">
            <span class="btn-icon">📊</span>
            View Statistics
        </a>
        <a href="alliance_edit.php" class="action-btn">
            <span class="btn-icon">✏️</span>
            Edit Alliance
        </a>
        <a href="dashboard.php" class="action-btn secondary">
            <span class="btn-icon">🏠</span>
            Back to Dashboard
        </a>
    </div>
</div>

<style>
.history-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.history-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.title-icon {
    font-size: 3rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
}

/* Chart Section */
.chart-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 3rem;
}

.chart-container {
    position: relative;
    height: 400px;
    margin-top: 1rem;
}

#powerTrendsChart {
    max-height: 400px;
}

/* Summary Stats */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.summary-card {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    text-align: center;
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.summary-card.total { border-left-color: #3498db; }
.summary-card.increases { border-left-color: #27ae60; }
.summary-card.decreases { border-left-color: #e74c3c; }
.summary-card.net { border-left-color: #f39c12; }

.summary-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.summary-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.summary-value.positive {
    color: #27ae60;
}

.summary-value.negative {
    color: #e74c3c;
}

.summary-label {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.summary-detail {
    font-size: 0.85rem;
    color: #95a5a6;
}

/* Current Power Section */
.current-power-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-icon {
    font-size: 2rem;
}

.current-power-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.power-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
}

.power-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.power-tag {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.power-name {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.power-value {
    font-size: 2rem;
    font-weight: 700;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

/* History Section */
.history-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 3rem;
}

.history-table-container {
    overflow-x: auto;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table thead {
    background: #f8f9fa;
}

.history-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #dee2e6;
}

.history-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f5;
}

.history-row:hover {
    background: #f8f9fa;
}

.timestamp-cell {
    color: #6c757d;
    font-size: 0.9rem;
}

.alliance-tag {
    background: #667eea;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
}

.power-cell {
    font-weight: 600;
    color: #495057;
}

.change-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
}

.change-badge.increase {
    background: #d4edda;
    color: #155724;
}

.change-badge.decrease {
    background: #f8d7da;
    color: #721c24;
}

.change-badge.neutral {
    background: #e2e3e5;
    color: #6c757d;
}

.user-cell {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Quick Actions */
.quick-actions-section {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.action-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.action-btn.secondary {
    background: #6c757d;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.action-btn.secondary:hover {
    background: #5a6268;
    box-shadow: 0 6px 25px rgba(108, 117, 125, 0.4);
}

.btn-icon {
    font-size: 1.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .history-container {
        padding: 1rem;
    }

    .page-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .current-power-grid {
        grid-template-columns: 1fr;
    }

    .history-table {
        font-size: 0.85rem;
    }

    .history-table th,
    .history-table td {
        padding: 0.75rem 0.5rem;
    }

    .quick-actions-section {
        flex-direction: column;
    }

    .action-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php if (!empty($chart_data)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?php echo json_encode($chart_data); ?>;
    
    if (chartData && chartData.length > 0) {
        renderPowerTrendsChart(chartData);
    }
});

function renderPowerTrendsChart(data) {
    const ctx = document.getElementById('powerTrendsChart').getContext('2d');
    
    // Group data by alliance
    const allianceData = {};
    const allDates = new Set();
    
    data.forEach(entry => {
        const alliance = entry.alliance;
        const date = entry.date;
        const power = entry.power;
        
        if (!allianceData[alliance]) {
            allianceData[alliance] = {};
        }
        allianceData[alliance][date] = power;
        allDates.add(date);
    });
    
    // Sort dates
    const sortedDates = Array.from(allDates).sort();
    
    // Create datasets for each alliance
    const datasets = [];
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
        '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
    ];
    
    let colorIndex = 0;
    Object.keys(allianceData).forEach(alliance => {
        const chartPoints = sortedDates.map(date => ({
            x: date,
            y: allianceData[alliance][date] || null
        }));
        
        datasets.push({
            label: alliance,
            data: chartPoints,
            borderColor: colors[colorIndex % colors.length],
            backgroundColor: colors[colorIndex % colors.length] + '20',
            borderWidth: 3,
            fill: false,
            tension: 0.1,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: colors[colorIndex % colors.length],
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        });
        colorIndex++;
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            return 'Date: ' + context[0].parsed.x;
                        },
                        label: function(context) {
                            const alliance = context.dataset.label;
                            const power = context.parsed.y;
                            return alliance + ': ' + (power ? power.toLocaleString() : 'No data');
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD',
                        tooltipFormat: 'MMM DD, YYYY',
                        displayFormats: {
                            day: 'MMM DD',
                            week: 'MMM DD',
                            month: 'MMM YYYY'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        font: {
                            size: 14,
                            weight: '600'
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Alliance Power',
                        font: {
                            size: 14,
                            weight: '600'
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
