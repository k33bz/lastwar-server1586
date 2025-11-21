<?php
/**
 * Season 1 Status Page
 * Display Season 1 progress with live countdown
 *
 * @version 2.0.0
 * @date 2025-11-21
 *
 * Changelog:
 *   2.0.0 - Added live countdown, UTC date handling, progress tracking
 *   1.0.0 - Initial static page
 */

// Require JWT authentication
define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once 'jwt.php';
require_once 'json_helpers.php';

$user = require_jwt_session();

// Load Season 1 configuration
$season1_config = read_json_file('season1_config.json');

// Calculate season progress
$start_datetime = new DateTime($season1_config['season_start_date'] . ' ' . $season1_config['season_start_time'], new DateTimeZone('UTC'));
$end_datetime = new DateTime($season1_config['season_end_date'] . ' ' . $season1_config['season_end_time'], new DateTimeZone('UTC'));
$now = new DateTime('now', new DateTimeZone('UTC'));

$total_seconds = $end_datetime->getTimestamp() - $start_datetime->getTimestamp();
$elapsed_seconds = $now->getTimestamp() - $start_datetime->getTimestamp();
$remaining_seconds = $end_datetime->getTimestamp() - $now->getTimestamp();

$progress_percent = min(100, max(0, ($elapsed_seconds / $total_seconds) * 100));
$current_day = floor($elapsed_seconds / 86400) + 1; // +1 because day 1 is the first day

$is_active = $now >= $start_datetime && $now <= $end_datetime;
$is_ended = $now > $end_datetime;

// Set page title
$page_title = 'Season 1 Status';

// Include header
require_once 'includes/header.php';
?>

<style>
.season-container {
    max-width: 900px;
    margin: 0 auto;
}

.season-header {
    text-align: center;
    margin-bottom: 2rem;
}

.season-status-badge {
    display: inline-block;
    padding: 0.5rem 1.5rem;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 1rem;
}

.season-status-badge.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.season-status-badge.ended {
    background: #6c757d;
    color: white;
}

.countdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.countdown-box {
    background: var(--card-background, #fff);
    border: 2px solid #667eea;
    border-radius: 8px;
    padding: 1.5rem 1rem;
    text-align: center;
}

.countdown-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.countdown-label {
    font-size: 0.875rem;
    color: var(--text-secondary, #6c757d);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress-bar-container {
    background: #e9ecef;
    border-radius: 10px;
    height: 30px;
    overflow: hidden;
    position: relative;
    margin: 2rem 0;
}

.progress-bar {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    height: 100%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.info-card {
    background: var(--card-background, #fff);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.info-card h3 {
    font-size: 0.875rem;
    color: var(--text-secondary, #6c757d);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.info-card .value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

.ended-message {
    background: linear-gradient(135deg, #52c234 0%, #061700 100%);
    color: white;
    padding: 3rem;
    border-radius: 12px;
    text-align: center;
    margin: 2rem 0;
}

.ended-message h2 {
    color: white;
    margin-bottom: 1rem;
}
</style>

<div class="season-container">
    <div class="season-header">
        <h1>⚔️ Season 1: Settling the Storm</h1>
        <div class="season-status-badge <?php echo $is_ended ? 'ended' : 'active'; ?>">
            <?php echo $is_ended ? '✅ Completed' : '🔥 Active'; ?>
        </div>
    </div>

    <?php if ($is_ended): ?>
        <div class="ended-message">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🏆</div>
            <h2>Season 1 Has Concluded!</h2>
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">
                Thank you to all alliances for an epic season!
            </p>
            <p style="opacity: 0.9;">
                Season ran from <?php echo $start_datetime->format('M j, Y'); ?>
                to <?php echo $end_datetime->format('M j, Y'); ?>
            </p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 1.5rem;">⏱️ Time Remaining</h2>

            <div class="countdown-grid" id="countdown">
                <div class="countdown-box">
                    <div class="countdown-number" id="days">--</div>
                    <div class="countdown-label">Days</div>
                </div>
                <div class="countdown-box">
                    <div class="countdown-number" id="hours">--</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-box">
                    <div class="countdown-number" id="minutes">--</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-box">
                    <div class="countdown-number" id="seconds">--</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo round($progress_percent, 1); ?>%">
                    <?php echo round($progress_percent, 1); ?>% Complete
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="info-grid">
        <div class="info-card">
            <h3>📅 Current Day</h3>
            <div class="value" id="currentDay"><?php echo $current_day; ?> / <?php echo $season1_config['total_days']; ?></div>
        </div>
        <div class="info-card">
            <h3>🚀 Season Started</h3>
            <div class="value" id="startDate">--</div>
        </div>
        <div class="info-card">
            <h3>🏁 Season Ends</h3>
            <div class="value" id="endDate">--</div>
        </div>
        <div class="info-card">
            <h3>⏰ Your Local Time</h3>
            <div class="value" id="localTime">--</div>
        </div>
    </div>
</div>

<script>
// UTC timestamps from server
const seasonEndUTC = <?php echo $end_datetime->getTimestamp(); ?> * 1000;
const seasonStartUTC = <?php echo $start_datetime->getTimestamp(); ?> * 1000;
const totalDays = <?php echo $season1_config['total_days']; ?>;
const isEnded = <?php echo $is_ended ? 'true' : 'false'; ?>;

// Format date in user's local timezone
function formatLocalDate(utcTimestamp) {
    const date = new Date(utcTimestamp);
    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZoneName: 'short'
    });
}

// Update countdown
function updateCountdown() {
    if (isEnded) return;

    const now = Date.now();
    const remaining = seasonEndUTC - now;

    if (remaining <= 0) {
        location.reload(); // Reload when season ends
        return;
    }

    const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
    const hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

    document.getElementById('days').textContent = days;
    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');

    // Update current day
    const elapsed = now - seasonStartUTC;
    const currentDay = Math.floor(elapsed / (1000 * 60 * 60 * 24)) + 1;
    document.getElementById('currentDay').textContent = `${currentDay} / ${totalDays}`;
}

// Update local time display
function updateLocalTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('localTime').textContent = timeString;
}

// Initialize
document.getElementById('startDate').textContent = formatLocalDate(seasonStartUTC);
document.getElementById('endDate').textContent = formatLocalDate(seasonEndUTC);

if (!isEnded) {
    updateCountdown();
    updateLocalTime();

    // Update every second
    setInterval(updateCountdown, 1000);
    setInterval(updateLocalTime, 1000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
