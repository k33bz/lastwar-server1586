/**
 * Generate Council Rotation Schedule
 *
 * Creates a fair rotation schedule from Week 1 to future weeks
 * ensuring all alliances in ranks 6-15 rotate equally before repeating.
 *
 * Usage: node scripts/generate-rotation-schedule.js
 */

const fs = require('fs');
const path = require('path');

// Configuration
const WEEK_1_START = new Date('2025-05-18T22:00:00-04:00'); // Sunday, May 18, 2025, 10 PM EDT
const WEEKS_TO_GENERATE = 52; // Generate 1 year of schedule (can extend as needed)

// Rotating pool: alliances ranked 6-15
const rotatingPool = [
    { rank: 6, tag: "STR8" },
    { rank: 7, tag: "EPIC" },
    { rank: 8, tag: "NYPR" },
    { rank: 9, tag: "86KO" },
    { rank: 10, tag: "SWBA" },
    { rank: 11, tag: "MTOP" },
    { rank: 12, tag: "UUSN" },
    { rank: 13, tag: "FNXS" },
    { rank: 14, tag: "L4TM" },
    { rank: 15, tag: "NiKi" }
];

/**
 * Generate fair rotation ensuring all alliances get equal representation
 * Algorithm: Round-robin with pairs
 */
function generateFairRotation(weekCount) {
    const schedule = [];
    const pool = [...rotatingPool];
    const totalAlliances = pool.length;

    // Create pairs ensuring everyone rotates fairly
    let usedIndices = [];

    for (let week = 1; week <= weekCount; week++) {
        // Reset when everyone has had a turn
        if (usedIndices.length >= totalAlliances) {
            usedIndices = [];
        }

        // Find available alliances (not used recently)
        let availableIndices = [];
        for (let i = 0; i < totalAlliances; i++) {
            if (!usedIndices.includes(i)) {
                availableIndices.push(i);
            }
        }

        // If we need to reset mid-selection, do so
        if (availableIndices.length < 2) {
            usedIndices = [];
            availableIndices = Array.from({ length: totalAlliances }, (_, i) => i);
        }

        // Pick first alliance
        const first = availableIndices[0];
        usedIndices.push(first);

        // Pick second alliance (different from first)
        availableIndices = availableIndices.filter(i => i !== first);
        if (availableIndices.length === 0) {
            // If no more available, reset and pick different from first
            usedIndices = [first];
            availableIndices = Array.from({ length: totalAlliances }, (_, i) => i).filter(i => i !== first);
        }
        const second = availableIndices[0];
        usedIndices.push(second);

        // Calculate week start date
        const weekStartDate = new Date(WEEK_1_START);
        weekStartDate.setDate(weekStartDate.getDate() + (week - 1) * 7);

        schedule.push({
            weekNumber: week,
            startDate: weekStartDate.toISOString(),
            rotatingMembers: [
                pool[first].rank,
                pool[second].rank
            ]
        });
    }

    return schedule;
}

// Generate schedule
console.log('Generating rotation schedule...');
console.log(`Week 1 starts: ${WEEK_1_START.toISOString()}`);
console.log(`Generating ${WEEKS_TO_GENERATE} weeks of rotation`);

const schedule = generateFairRotation(WEEKS_TO_GENERATE);

// Calculate current week for reference
const now = new Date();
const timeSinceStart = now.getTime() - WEEK_1_START.getTime();
const currentWeekNumber = Math.floor(timeSinceStart / (7 * 24 * 60 * 60 * 1000)) + 1;

console.log(`Current week number: ${currentWeekNumber}`);
console.log(`Schedule contains ${schedule.length} weeks`);

// Write to JSON file
const outputPath = path.join(__dirname, '../data/rotation-schedule.json');
const output = {
    generatedAt: new Date().toISOString(),
    epoch: WEEK_1_START.toISOString(),
    currentWeekNumber: currentWeekNumber,
    schedule: schedule
};

fs.writeFileSync(outputPath, JSON.stringify(output, null, 4));

console.log(`✓ Schedule written to: ${outputPath}`);
console.log('\nFirst 10 weeks:');
schedule.slice(0, 10).forEach(week => {
    const date = new Date(week.startDate);
    console.log(`  Week ${week.weekNumber}: ${date.toLocaleDateString()} - Ranks ${week.rotatingMembers.join(', ')}`);
});

console.log('\nRotation fairness check:');
const allianceCounts = {};
rotatingPool.forEach(a => allianceCounts[a.rank] = 0);

schedule.forEach(week => {
    week.rotatingMembers.forEach(rank => {
        allianceCounts[rank]++;
    });
});

console.log('Times each alliance appears in rotation:');
Object.entries(allianceCounts).forEach(([rank, count]) => {
    const alliance = rotatingPool.find(a => a.rank === parseInt(rank));
    console.log(`  Rank ${rank} (${alliance.tag}): ${count} times`);
});
