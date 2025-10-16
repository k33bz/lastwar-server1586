<?php
/**
 * Alliance Statistics
 *
 * Display statistics for user's assigned alliances
 * R4/R5 see only their alliances, Admins/Power Editors see all
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'includes/alliance_helper.php';

$user = require_jwt_session();

// Set page title for header
$page_title = "Alliance Statistics";

// Include shared header
include 'includes/header.php';

// Get alliances data
$alliances = AllianceHelper::loadAlliances();

// Filter alliances based on user role
$user_alliances = [];
if ($user->aud === 'admin' || is_power_editor($user)) {
    // Admin and power editors see all alliances
    $user_alliances = $alliances;
} else {
    // R4/R5 see only their assigned alliances
    $allowed_tags = $user->alliances ?? [];

    // Handle wildcard access
    if (in_array('*', $allowed_tags)) {
        $user_alliances = $alliances;
    } else {
        foreach ($alliances as $alliance) {
            if (in_array($alliance['tag'], $allowed_tags)) {
                $user_alliances[] = $alliance;
            }
        }
    }
}

// Calculate statistics
$total_alliances = count($user_alliances);
$total_power = array_sum(array_column($user_alliances, 'power'));
$average_power = $total_alliances > 0 ? round($total_power / $total_alliances) : 0;

$powers = array_column($user_alliances, 'power');
sort($powers);
$median_power = 0;
if ($total_alliances > 0) {
    $middle = floor($total_alliances / 2);
    if ($total_alliances % 2 === 0 && $middle > 0) {
        $median_power = round(($powers[$middle - 1] + $powers[$middle]) / 2);
    } else {
        $median_power = round($powers[$middle]);
    }
}

$highest_power = $powers ? max($powers) : 0;
$lowest_power = $powers ? min($powers) : 0;

// Sort alliances by power descending
usort($user_alliances, function($a, $b) {
    return ($b['power'] ?? 0) - ($a['power'] ?? 0);
});

// Calculate ranks
$ranks = AllianceHelper::calculateRanks($alliances);
?>

<div class="stats-container">
    <div class="stats-header">
        <h1 class="page-title">
            <span class="title-icon">📊</span>
            Alliance Statistics
        </h1>
        <p class="page-subtitle">
            <?php if ($user->aud === 'admin' || is_power_editor($user)): ?>
                Viewing statistics for all alliances
            <?php else: ?>
                Viewing statistics for your assigned <?php echo $total_alliances === 1 ? 'alliance' : 'alliances'; ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Overview Stats -->
    <div class="overview-grid">
        <div class="stat-box total">
            <div class="stat-icon">⚔️</div>
            <div class="stat-value"><?php echo number_format($total_alliances); ?></div>
            <div class="stat-label">Total Alliances</div>
        </div>

        <div class="stat-box power">
            <div class="stat-icon">⚡</div>
            <div class="stat-value"><?php echo number_format($total_power); ?></div>
            <div class="stat-label">Total Power</div>
        </div>

        <div class="stat-box average">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?php echo number_format($average_power); ?></div>
            <div class="stat-label">Average Power</div>
        </div>

        <div class="stat-box median">
            <div class="stat-icon">📈</div>
            <div class="stat-value"><?php echo number_format($median_power); ?></div>
            <div class="stat-label">Median Power</div>
        </div>
    </div>

    <!-- Alliance Rankings -->
    <div class="rankings-section">
        <h2 class="section-title">
            <span class="section-icon">🏆</span>
            Alliance Rankings
        </h2>

        <div class="rankings-table-container">
            <table class="rankings-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Tag</th>
                        <th>Name</th>
                        <th>Power</th>
                        <th>R5</th>
                        <th>Signed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_alliances)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #6c757d;">
                                No alliances assigned
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($user_alliances as $alliance): ?>
                            <?php
                            $rank = $ranks[$alliance['tag']] ?? '?';
                            $r5_name = is_array($alliance['r5'] ?? null) ?
                                      ($alliance['r5']['name'] ?? 'Unknown') :
                                      ($alliance['r5'] ?? 'Unknown');
                            $signed = $alliance['signed'] ?? false;
                            ?>
                            <tr class="alliance-row">
                                <td class="rank-cell">
                                    <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                        #<?php echo $rank; ?>
                                    </span>
                                </td>
                                <td class="tag-cell">
                                    <span class="alliance-tag"><?php echo htmlspecialchars($alliance['tag']); ?></span>
                                </td>
                                <td class="name-cell">
                                    <?php echo htmlspecialchars($alliance['name']); ?>
                                </td>
                                <td class="power-cell">
                                    <span class="power-value"><?php echo number_format($alliance['power']); ?></span>
                                </td>
                                <td class="r5-cell">
                                    <?php echo htmlspecialchars($r5_name); ?>
                                </td>
                                <td class="signed-cell">
                                    <span class="signed-badge <?php echo $signed ? 'signed' : 'unsigned'; ?>">
                                        <?php echo $signed ? '✓ Signed' : '✗ Unsigned'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Power Distribution -->
    <div class="distribution-section">
        <h2 class="section-title">
            <span class="section-icon">📊</span>
            Power Distribution
        </h2>

        <div class="distribution-grid">
            <div class="distribution-card">
                <div class="dist-label">Highest Power</div>
                <div class="dist-value highest"><?php echo number_format($highest_power); ?></div>
                <div class="dist-sublabel">
                    <?php
                    $highest_alliance = null;
                    foreach ($user_alliances as $a) {
                        if ($a['power'] == $highest_power) {
                            $highest_alliance = $a;
                            break;
                        }
                    }
                    echo $highest_alliance ? htmlspecialchars($highest_alliance['tag']) : '';
                    ?>
                </div>
            </div>

            <div class="distribution-card">
                <div class="dist-label">Lowest Power</div>
                <div class="dist-value lowest"><?php echo number_format($lowest_power); ?></div>
                <div class="dist-sublabel">
                    <?php
                    $lowest_alliance = null;
                    foreach (array_reverse($user_alliances) as $a) {
                        if ($a['power'] == $lowest_power) {
                            $lowest_alliance = $a;
                            break;
                        }
                    }
                    echo $lowest_alliance ? htmlspecialchars($lowest_alliance['tag']) : '';
                    ?>
                </div>
            </div>

            <div class="distribution-card">
                <div class="dist-label">Power Range</div>
                <div class="dist-value range"><?php echo number_format($highest_power - $lowest_power); ?></div>
                <div class="dist-sublabel">Difference</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <a href="alliance_power_history.php" class="action-btn">
            <span class="btn-icon">📈</span>
            View Power History
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
.stats-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.stats-header {
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

/* Overview Stats */
.overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-box {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    text-align: center;
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.stat-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.stat-box.total { border-left-color: #e74c3c; }
.stat-box.power { border-left-color: #f39c12; }
.stat-box.average { border-left-color: #3498db; }
.stat-box.median { border-left-color: #27ae60; }

.stat-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 500;
}

/* Rankings Section */
.rankings-section {
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

.rankings-table-container {
    overflow-x: auto;
}

.rankings-table {
    width: 100%;
    border-collapse: collapse;
}

.rankings-table thead {
    background: #f8f9fa;
}

.rankings-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #dee2e6;
}

.rankings-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f5;
}

.alliance-row:hover {
    background: #f8f9fa;
}

.rank-badge {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
}

.rank-badge.rank-1 {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: white;
}

.rank-badge.rank-2 {
    background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
    color: white;
}

.rank-badge.rank-3 {
    background: linear-gradient(135deg, #CD7F32, #8B4513);
    color: white;
}

.rank-badge.rank-other {
    background: #e9ecef;
    color: #495057;
}

.alliance-tag {
    background: #667eea;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
}

.power-value {
    font-weight: 600;
    color: #f39c12;
}

.signed-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.signed-badge.signed {
    background: #d4edda;
    color: #155724;
}

.signed-badge.unsigned {
    background: #f8d7da;
    color: #721c24;
}

/* Distribution Section */
.distribution-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 3rem;
}

.distribution-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.distribution-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    border: 2px solid #e9ecef;
}

.dist-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0.75rem;
}

.dist-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.dist-value.highest { color: #27ae60; }
.dist-value.lowest { color: #e74c3c; }
.dist-value.range { color: #3498db; }

.dist-sublabel {
    font-size: 0.85rem;
    color: #95a5a6;
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
    .stats-container {
        padding: 1rem;
    }

    .page-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .overview-grid {
        grid-template-columns: 1fr;
    }

    .rankings-table {
        font-size: 0.9rem;
    }

    .rankings-table th,
    .rankings-table td {
        padding: 0.75rem 0.5rem;
    }

    .distribution-grid {
        grid-template-columns: 1fr;
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

<?php include 'includes/footer.php'; ?>
