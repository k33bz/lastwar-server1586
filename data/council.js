/**
 * Server 1586 - Council Voting Members Management
 *
 * This file manages council voting utilities and timezone displays.
 * Rotation schedule is now stored in rotation-schedule.json
 *
 * CHANGELOG:
 * v2.0.0 - 2025-10-07
 * - Removed dynamic rotation generation logic
 * - Now reads from pre-generated rotation-schedule.json
 * - Simplified to utility functions only
 * - Schedule is hardcoded and can be edited externally
 *
 * v1.3.2 - 2025-10-06
 * - Updated generateRotationSchedule() to include previous week with isPrevious flag
 * - Previous week now appears first in schedule list (greyed out)
 * - Removed getPreviousWeekRotatingMembers() in favor of generateRotationSchedule approach
 *
 * ROTATION RULES:
 * - Top 5 alliances are permanent voting members
 * - 2 alliances from ranks 6-15 rotate weekly
 * - Rotation changes every Monday at 02:00 UTC (Sunday 10:00 PM EDT)
 * - Schedule is pre-generated to ensure fair rotation (all alliances rotate before repeating)
 * - Week 1 epoch: Monday, May 19, 2025 at 02:00 UTC
 */

/**
 * Week 1 epoch constant (Monday, May 19, 2025 at 02:00 UTC)
 */
const WEEK_1_EPOCH = new Date('2025-05-19T02:00:00Z');

/**
 * Calculate the current week number since Week 1
 * Week 1 started: Monday, May 19, 2025 at 02:00 UTC
 * @returns {number} Week number (1-based)
 */
function getCurrentWeekNumber() {
    var now = new Date();
    var timeSinceStart = now.getTime() - WEEK_1_EPOCH.getTime();
    var millisecondsInWeek = 7 * 24 * 60 * 60 * 1000;
    var weekNumber = Math.floor(timeSinceStart / millisecondsInWeek) + 1;

    if (weekNumber < 1) {
        weekNumber = 1;
    }

    return weekNumber;
}

/**
 * Calculate the next week rotation time
 * @returns {Date} Next Monday 02:00 UTC
 */
function getNextWeekReset() {
    var now = new Date();

    // Get current time in UTC
    var utcYear = now.getUTCFullYear();
    var utcMonth = now.getUTCMonth();
    var utcDate = now.getUTCDate();
    var utcDay = now.getUTCDay();
    var utcHours = now.getUTCHours();

    // Find next Monday (day 1)
    var daysUntilMonday = (1 - utcDay + 7) % 7;

    // If today is Monday but past 02:00 UTC, go to next Monday
    if (daysUntilMonday === 0 && utcHours >= 2) {
        daysUntilMonday = 7;
    }

    // Create next rotation date in UTC
    var nextReset = new Date(Date.UTC(utcYear, utcMonth, utcDate + daysUntilMonday, 2, 0, 0, 0));

    return nextReset;
}

/**
 * Format date/time in UTC (primary display format)
 * @param {Date} date - Date to format
 * @returns {string} Formatted UTC string
 */
function formatGMT(date) {
    var options = {
        timeZone: 'UTC',
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    };
    return date.toLocaleString('en-US', options) + ' UTC';
}

/**
 * Format date/time for all major timezones (tooltip display)
 * @param {Date} date - Date to format
 * @returns {Array} Array of formatted timezone strings
 */
function formatAllTimezones(date) {
    var timezones = [
        { tz: 'UTC' },
        { tz: 'America/New_York' },
        { tz: 'America/Los_Angeles' },
        { tz: 'America/Sao_Paulo' },
        { tz: 'Asia/Seoul' },
        { tz: 'Australia/Sydney' },
        { tz: 'Europe/Berlin' }
    ];

    var result = [];
    for (var i = 0; i < timezones.length; i++) {
        try {
            var options = {
                timeZone: timezones[i].tz,
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZoneName: 'short'
            };
            var formatted = date.toLocaleString('en-US', options);
            result.push(formatted);
        } catch (e) {
            result.push(timezones[i].tz + ': N/A');
        }
    }
    return result;
}

/**
 * Format countdown time remaining
 * @param {Date} targetDate - Target date
 * @returns {string} Formatted countdown string
 */
function formatCountdown(targetDate) {
    var now = new Date();
    var timeRemaining = targetDate - now;

    if (timeRemaining <= 0) {
        return 'Rotation happening now...';
    }

    var days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
    var hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

    if (days > 0) {
        return days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's';
    } else if (hours > 0) {
        return hours + 'h ' + minutes + 'm ' + seconds + 's';
    } else if (minutes > 0) {
        return minutes + 'm ' + seconds + 's';
    } else {
        return seconds + 's';
    }
}
