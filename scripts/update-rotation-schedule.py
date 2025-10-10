#!/usr/bin/env python3
"""
Update Council Rotation Schedule

Version: 2.2.0
Last Updated: 2025-10-08

This script updates the rotation schedule for the next 52 weeks based on:
- Current top 15 alliances from alliances.json
- Fair distribution ensuring all rotating alliances (ranks 6-15) get equal representation
- Looks back 10 weeks to ensure fairness across the transition period
- Does not penalize existing alliances when new ones are added
- Prevents back-to-back rotations (same alliance in consecutive weeks)

Usage: python scripts/update-rotation-schedule.py

CHANGELOG:
v2.2.0 - 2025-10-08
- Made minimum weeks between rotations configurable (MIN_WEEKS_BETWEEN_ROTATIONS)
- Default: 2 weeks (no back-to-back, must skip at least 1 week)
- Implemented sliding window history tracking for multi-week gaps
- Graduated penalty system for recent rotations (more recent = stronger penalty)

v2.1.0 - 2025-10-08
- Added back-to-back prevention logic
- No alliance will rotate in consecutive weeks
- Strong penalty (-10 weight) applied to alliances that rotated in previous week

v2.0.0 - 2025-10-08
- Changed to UTC-based calculations (Monday 02:00 UTC = Sunday 10PM EDT)
- Fixed week number calculation to use epoch-based calculation
- Improved fairness algorithm with cycle tracking

Algorithm:
1. Load current schedule and alliances
2. Determine current week number
3. Look back 10 weeks to count recent rotations
4. Generate next 52 weeks using weighted fair selection with back-to-back prevention
5. Preserve past schedule (before next rotation)
6. Update schedule file
"""

import json
import os
from datetime import datetime, timedelta
from pathlib import Path
from collections import Counter
from typing import List, Dict, Tuple

# Configuration
# Server reset: Sunday 10 PM EDT = Monday 02:00 UTC (EDT is UTC-4)
WEEK_1_EPOCH = datetime.fromisoformat('2025-05-19T02:00:00+00:00')
ROTATION_DAY = 0  # Monday (in UTC)
ROTATION_HOUR = 2  # 2 AM UTC (10 PM EDT)
ROTATION_MINUTE = 0
WEEKS_TO_GENERATE = 52
LOOKBACK_WEEKS = 10
MIN_WEEKS_BETWEEN_ROTATIONS = 2  # Minimum weeks before same alliance can rotate again (1 = allow back-to-back, 2 = skip 1 week, etc.)

# File paths
SCRIPT_DIR = Path(__file__).parent
PROJECT_DIR = SCRIPT_DIR.parent
DATA_DIR = PROJECT_DIR / 'data'
ALLIANCES_FILE = DATA_DIR / 'alliances.json'
SCHEDULE_FILE = DATA_DIR / 'rotation-schedule.json'


def get_current_week_number() -> int:
    """Calculate current week number since Week 1 epoch."""
    from datetime import timezone
    now = datetime.now(timezone.utc)

    # Ensure epoch is timezone-aware (UTC)
    if WEEK_1_EPOCH.tzinfo:
        epoch = WEEK_1_EPOCH
    else:
        epoch = WEEK_1_EPOCH.replace(tzinfo=timezone.utc)

    time_since_start = now - epoch
    week_number = int(time_since_start.total_seconds() / (7 * 24 * 60 * 60)) + 1

    return max(1, week_number)


def get_next_rotation_date() -> datetime:
    """Get the date of the next rotation (Monday 02:00 UTC)."""
    from datetime import timezone
    now = datetime.now(timezone.utc)

    # Calculate days until next Monday
    days_until_monday = (ROTATION_DAY - now.weekday()) % 7

    # If today is Monday but past rotation time, go to next Monday
    if days_until_monday == 0:
        if now.hour >= ROTATION_HOUR:
            days_until_monday = 7

    next_monday = now + timedelta(days=days_until_monday)
    next_rotation = next_monday.replace(hour=ROTATION_HOUR, minute=ROTATION_MINUTE, second=0, microsecond=0, tzinfo=timezone.utc)

    return next_rotation


def load_alliances() -> List[Dict]:
    """Load alliance data from JSON file."""
    with open(ALLIANCES_FILE, 'r', encoding='utf-8') as f:
        alliances = json.load(f)
    return alliances


def load_schedule() -> Dict:
    """Load existing rotation schedule. Returns empty schedule if file doesn't exist."""
    if not SCHEDULE_FILE.exists():
        # Return minimal schedule structure if file doesn't exist
        return {
            'generatedAt': None,
            'epoch': WEEK_1_EPOCH.isoformat(),
            'currentWeekNumber': 0,
            'schedule': []
        }

    with open(SCHEDULE_FILE, 'r', encoding='utf-8') as f:
        schedule = json.load(f)
    return schedule


def get_rotating_pool(alliances: List[Dict]) -> List[Dict]:
    """Get alliances with ranks 6-15 for rotation pool."""
    rotating_alliances = [a for a in alliances if 6 <= a['rank'] <= 15]
    return sorted(rotating_alliances, key=lambda x: x['rank'])


def count_recent_rotations(schedule: List[Dict], current_week: int, lookback_weeks: int) -> Counter:
    """
    Count how many times each alliance has rotated in the last N weeks.

    Args:
        schedule: Full schedule list
        current_week: Current week number
        lookback_weeks: How many weeks to look back

    Returns:
        Counter with alliance tag as key and rotation count as value
    """
    rotation_counts = Counter()

    # Look back from current week
    start_week = max(1, current_week - lookback_weeks)

    for week_data in schedule:
        week_num = week_data['weekNumber']
        if start_week <= week_num < current_week:
            for tag in week_data['rotatingMembers']:
                rotation_counts[tag] += 1

    return rotation_counts


def select_fair_pair(available_alliances: List[Dict], rotation_counts: Counter, used_this_cycle: set, recent_rotation_history: List[set] = None, min_weeks_gap: int = 2) -> Tuple[str, str]:
    """
    Select a fair pair of alliances to rotate.

    Algorithm:
    1. Prioritize alliances with lowest rotation count
    2. Among those with same count, prefer those not used in current cycle
    3. Avoid pairing same alliance twice in one week
    4. Prevent rotations within minimum gap (configurable, default 2 weeks)

    Args:
        available_alliances: List of alliance dicts available for rotation
        rotation_counts: How many times each alliance tag has rotated recently
        used_this_cycle: Alliance tags already used in current rotation cycle
        recent_rotation_history: List of sets containing tags from recent weeks (most recent first)
        min_weeks_gap: Minimum weeks that must pass before alliance can rotate again (1 = allow back-to-back)

    Returns:
        Tuple of two alliance tags
    """
    if recent_rotation_history is None:
        recent_rotation_history = []

    # Create weighted list prioritizing least-used alliances
    # Weight = (max_count - alliance_count) + bonus for not being used this cycle + penalty for recent rotations
    if rotation_counts:
        max_count = max(rotation_counts.values()) if rotation_counts else 0
    else:
        max_count = 0

    weighted_alliances = []
    for alliance in available_alliances:
        tag = alliance['tag']
        count = rotation_counts.get(tag, 0)
        cycle_bonus = 2 if tag not in used_this_cycle else 0

        # Check if alliance appears in recent history (within min_weeks_gap - 1 weeks back)
        recent_penalty = 0
        weeks_to_check = min(len(recent_rotation_history), min_weeks_gap - 1)
        for i in range(weeks_to_check):
            if tag in recent_rotation_history[i]:
                # Stronger penalty for more recent rotations
                recent_penalty -= (10 - i * 2)  # Week 0 (last week) = -10, Week 1 = -8, etc.

        weight = (max_count - count + 1) + cycle_bonus + recent_penalty
        weighted_alliances.append((tag, weight, alliance['rank']))

    # Sort by weight (descending) then by rank (ascending for consistency)
    weighted_alliances.sort(key=lambda x: (-x[1], x[2]))

    # Select first alliance (highest weight)
    first_tag = weighted_alliances[0][0]

    # Select second alliance (highest weight, different from first)
    second_tag = None
    for tag, weight, rank in weighted_alliances[1:]:
        if tag != first_tag:
            second_tag = tag
            break

    # Fallback if only one alliance available (shouldn't happen with 10 alliances)
    if second_tag is None:
        second_tag = weighted_alliances[1][0] if len(weighted_alliances) > 1 else first_tag

    return (first_tag, second_tag)


def generate_future_schedule(
    current_week: int,
    rotating_pool: List[Dict],
    recent_counts: Counter,
    weeks_to_generate: int,
    existing_schedule: List[Dict] = None,
    min_weeks_gap: int = 2
) -> List[Dict]:
    """
    Generate fair rotation schedule for future weeks.

    Args:
        current_week: Week number to start generating from
        rotating_pool: List of alliance dicts eligible for rotation
        recent_counts: Recent rotation counts for fairness
        weeks_to_generate: Number of weeks to generate
        existing_schedule: Existing schedule to check for minimum gap enforcement
        min_weeks_gap: Minimum weeks between rotations for same alliance

    Returns:
        List of week schedule dictionaries
    """
    schedule = []
    rotation_counts = recent_counts.copy()

    # Track which alliances have been used in current cycle (resets every len(pool)/2 weeks)
    cycle_length = len(rotating_pool) // 2
    used_this_cycle = set()

    # Track recent rotation history (sliding window of recent weeks)
    recent_rotation_history = []

    # Build initial history from existing schedule
    if existing_schedule:
        # Get the last (min_weeks_gap - 1) weeks from existing schedule
        weeks_to_load = min_weeks_gap - 1
        for i in range(weeks_to_load, 0, -1):
            week_num = current_week - i
            for week_data in existing_schedule:
                if week_data['weekNumber'] == week_num:
                    recent_rotation_history.append(set(week_data['rotatingMembers']))
                    break

    # Calculate start date for the current_week (not next rotation)
    # current_week is relative to Week 1, so subtract 1 for offset
    from datetime import timezone
    week_offset = current_week - 1
    first_week_start = WEEK_1_EPOCH + timedelta(weeks=week_offset)

    for i in range(weeks_to_generate):
        week_number = current_week + i
        week_start = first_week_start + timedelta(weeks=i)

        # Reset cycle tracking periodically
        if i > 0 and i % cycle_length == 0:
            used_this_cycle.clear()

        # Select fair pair (passing recent rotation history and min_weeks_gap)
        first_tag, second_tag = select_fair_pair(
            rotating_pool,
            rotation_counts,
            used_this_cycle,
            recent_rotation_history,
            min_weeks_gap
        )

        # Update tracking
        rotation_counts[first_tag] += 1
        rotation_counts[second_tag] += 1
        used_this_cycle.add(first_tag)
        used_this_cycle.add(second_tag)

        # Update recent rotation history (sliding window)
        current_week_tags = {first_tag, second_tag}
        recent_rotation_history.insert(0, current_week_tags)  # Add to front (most recent)

        # Keep only the last (min_weeks_gap - 1) weeks in history
        if len(recent_rotation_history) > min_weeks_gap - 1:
            recent_rotation_history.pop()

        # Create week entry
        schedule.append({
            'weekNumber': week_number,
            'startDate': week_start.isoformat().replace('+00:00', 'Z'),  # UTC format
            'rotatingMembers': sorted([first_tag, second_tag])  # Alphabetical order
        })

    return schedule


def update_schedule_file():
    """Main function to update rotation schedule."""
    print('=' * 60)
    print('Council Rotation Schedule Update')
    print('=' * 60)

    # Load data
    print('\n[1/6] Loading alliance data...')
    alliances = load_alliances()
    print(f'      Loaded {len(alliances)} alliances')

    print('\n[2/6] Loading existing schedule...')
    existing_schedule = load_schedule()
    if len(existing_schedule['schedule']) == 0:
        print(f'      No existing schedule found - will create new schedule')
    else:
        print(f'      Existing schedule has {len(existing_schedule["schedule"])} weeks')

    # Calculate current week
    print('\n[3/6] Calculating current week...')
    from datetime import timezone
    current_week = get_current_week_number()
    next_rotation = get_next_rotation_date()
    now_utc = datetime.now(timezone.utc)
    next_week = current_week if now_utc < next_rotation else current_week + 1
    print(f'      Current week: {current_week}')
    print(f'      Next rotation: {next_rotation.strftime("%Y-%m-%d %H:%M UTC")}')
    print(f'      Generating from week: {next_week}')

    # Get rotating pool
    print('\n[4/6] Analyzing rotation pool...')
    rotating_pool = get_rotating_pool(alliances)
    pool_tags = [a['tag'] for a in rotating_pool]
    print(f'      Rotating pool (ranks 6-15): {pool_tags}')

    # Count recent rotations for fairness
    print(f'\n[5/6] Analyzing recent rotation history (last {LOOKBACK_WEEKS} weeks)...')
    recent_counts = count_recent_rotations(existing_schedule['schedule'], current_week, LOOKBACK_WEEKS)

    if recent_counts and sum(recent_counts.values()) > 0:
        print('      Recent rotation counts:')
        for alliance in rotating_pool:
            tag = alliance['tag']
            rank = alliance['rank']
            count = recent_counts.get(tag, 0)
            print(f'        Rank {rank:2d} ({tag:4s}): {count} times')
    else:
        print('      No recent rotation history found (creating fresh schedule)')

    # Generate new schedule
    print(f'\n[6/6] Generating next {WEEKS_TO_GENERATE} weeks...')
    print(f'      Minimum weeks between rotations: {MIN_WEEKS_BETWEEN_ROTATIONS}')
    new_schedule = generate_future_schedule(
        next_week,
        rotating_pool,
        recent_counts,
        WEEKS_TO_GENERATE,
        existing_schedule['schedule'],  # Pass existing schedule for gap enforcement
        MIN_WEEKS_BETWEEN_ROTATIONS
    )

    # Preserve all past weeks (before next rotation), converting numeric ranks to tags
    if existing_schedule['schedule']:
        past_weeks = []
        for w in existing_schedule['schedule']:
            if w['weekNumber'] < next_week:
                # Check if rotatingMembers contains numeric ranks (need conversion)
                if w['rotatingMembers'] and isinstance(w['rotatingMembers'][0], int):
                    # Convert numeric ranks to alliance tags
                    converted_members = []
                    for rank in w['rotatingMembers']:
                        # Find alliance by rank
                        alliance = next((a for a in alliances if a['rank'] == rank), None)
                        if alliance:
                            converted_members.append(alliance['tag'])
                        else:
                            # If rank not found, keep as-is (shouldn't happen)
                            converted_members.append(str(rank))

                    past_weeks.append({
                        'weekNumber': w['weekNumber'],
                        'startDate': w['startDate'],
                        'rotatingMembers': sorted(converted_members)
                    })
                else:
                    # Already uses tags, keep as-is
                    past_weeks.append(w)
    else:
        # If no existing schedule, create minimal history from Week 1 to current week
        past_weeks = []
        if next_week > 1:
            print(f'      Creating minimal history for weeks 1-{next_week-1}...')
            # Use simple round-robin for past weeks
            for week_num in range(1, next_week):
                week_offset = (week_num - 1) % 5  # 5-week cycle for 10 alliances
                pair_offset = week_offset * 2
                alliance_pair = rotating_pool[pair_offset:pair_offset+2]
                if len(alliance_pair) < 2:
                    alliance_pair = rotating_pool[:2]

                tags = [a['tag'] for a in alliance_pair]
                week_start = WEEK_1_EPOCH + timedelta(weeks=(week_num - 1))
                past_weeks.append({
                    'weekNumber': week_num,
                    'startDate': week_start.isoformat().replace('+00:00', 'Z'),
                    'rotatingMembers': sorted(tags)  # Alphabetical order
                })

    # Combine: past + new future
    full_schedule = past_weeks + new_schedule

    # Create output
    output = {
        'generatedAt': datetime.now().isoformat() + 'Z',
        'epoch': WEEK_1_EPOCH.isoformat(),
        'currentWeekNumber': current_week,
        'schedule': full_schedule
    }

    # Write to file
    with open(SCHEDULE_FILE, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=4, ensure_ascii=False)

    print(f'      [OK] Generated {len(new_schedule)} new weeks')
    print(f'      [OK] Preserved {len(past_weeks)} past weeks')
    print(f'      [OK] Total schedule: {len(full_schedule)} weeks')

    # Show upcoming weeks preview
    print('\n' + '=' * 60)
    print('Upcoming Rotation Schedule (Next 10 weeks):')
    print('=' * 60)

    upcoming = [w for w in new_schedule[:10]]
    for week_data in upcoming:
        week_num = week_data['weekNumber']
        week_date = datetime.fromisoformat(week_data['startDate'].replace('Z', '+00:00'))
        tags = week_data['rotatingMembers']

        # Get alliance ranks for display
        ranks = []
        for tag in tags:
            alliance = next((a for a in alliances if a['tag'] == tag), None)
            ranks.append(alliance['rank'] if alliance else '??')

        status = '(NEXT)' if week_num == next_week else ''
        print(f'  Week {week_num:2d}: {week_date.strftime("%Y-%m-%d")} - {tags[0]:4s} (#{ranks[0]:2}), {tags[1]:4s} (#{ranks[1]:2}) {status}')

    # Verify fairness in generated schedule
    print('\n' + '=' * 60)
    print(f'Fairness Check (Next {WEEKS_TO_GENERATE} weeks):')
    print('=' * 60)

    future_counts = Counter()
    for week_data in new_schedule:
        for tag in week_data['rotatingMembers']:
            future_counts[tag] += 1

    for alliance in rotating_pool:
        tag = alliance['tag']
        rank = alliance['rank']
        count = future_counts.get(tag, 0)
        print(f'  {tag:4s} (Rank {rank:2d}): {count:2d} rotations')

    print('\n' + '=' * 60)
    print('[SUCCESS] Schedule updated successfully!')
    print(f'          File: {SCHEDULE_FILE}')
    print('=' * 60)


if __name__ == '__main__':
    try:
        update_schedule_file()
    except FileNotFoundError as e:
        print(f'\n[ERROR] Required file not found: {e}')
        print('        Make sure you run this script from the project root or scripts directory.')
    except json.JSONDecodeError as e:
        print(f'\n[ERROR] Invalid JSON in data file: {e}')
    except Exception as e:
        print(f'\n[ERROR] Unexpected error: {e}')
        import traceback
        traceback.print_exc()
