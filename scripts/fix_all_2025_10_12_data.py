#!/usr/bin/env python3
"""
Complete re-extraction of 2025-10-12 data from correct image file
All power values were incorrectly read from initial processing
"""
import json
import csv
from pathlib import Path

# Correctly extracted data from 2025-10-12_16-20-15.png
NEW_DATE = "2025-10-12"

# Alliance data: [rank, tag, name, power]
ALLIANCE_DATA = [
    [1, "ORCE", "Omega Force", 7044519755],
    [2, "UvvU", "veni vidi vici", 6467618745],
    [3, "nkot", "korea one team", 5053417714],
    [4, "STR8", "STR8 SAVAGE", 4998393046],
    [5, "EPIC", "KarmaKings", 4997451656],
    [6, "LE4L", "LE4L", 4988688656],
    [7, "404a", "Not Found", 4982528656],
    [8, "FLM", "Fertile Magik A", 4703741062],
    [9, "NYPR", "NY Bats", 3785873485],
    [10, "SWBA", "SinnersWillBeAshed", 3681539337],
    [11, "86KO", "무적까까아니", 2633268782],
    [12, "FNXS", "F é n i x", 2638735693],
    [13, "NiKi", "NIKII", 1677883859],
    [14, "L4TM", "La Última Orden", 1827891706],
    [15, "MTOP", "MonteOlimpo", 1835412238],
    [16, "Zvrl", "Zvrl", 1835354254],
    [17, "UUSN", "Kantinny", 911282454],
    [18, "LSKJ", "The Lasts", 568731838],
    [19, "FRHD", "United Fight", 543481248],
    [20, "z5fa", "z5fa", 544297186],
    [21, "LeSo", "loving tstore", 417254829],
    [22, "w1l0", "MountainHeroes", 438512191],
    [23, "BrzS", "Boys of sin", 324686789],
    [24, "SXRS", "Sanguinário451", 321887989],
    [25, "AMhE", "Shuarma", 285526323],
    [26, "SLTR", "Slither de Caos", 273175316],
    [27, "SLMA", "astanhão", 252890829],
    [28, "SWSL", "The Nerds", 206108439],
    [29, "CHVL", "Elites de Caos", 188438787],
    [30, "LsBJ", "astanhão", 129478918],
    [31, "Lbdu", "Laëla", 140078397],
    [32, "Bbr1", "Lascanonn", 113428691],
    [33, "LsN0", "Bur", 101914788],
    [34, "SHDW", "La Última Orden", 94539668],
    [35, "SWGE", "Nooooooooooooo", 92451015],
    [36, "MAnD", "Elon dono", 72528458],
    [37, "MbSt", "NATIONS 999", 71317272],
    [38, "FYPE", "Gods low", 67234832],
    [39, "TSTG", "peahakBurr", 62152832],
    [40, "BSoG", "peahakBurr", 62379252],
    [41, "TmpL", "무적길드군", 61317088],
    [42, "LHPS", "Evil Walks Loudly", 54735436],
    [43, "LGLW", "Sanguinário451", 54458458],
    [44, "RNWF", "Chaos", 50877549],
    [45, "ArtW", "Reinforced", 48628329],
    [46, "EvLB", "Boom de guerre", 43964927],
    [47, "SOUL", "BloodStone", 43763816],
    [48, "STNK", "TEMPLAP", 43558988],
    [49, "joumH", "love peace", 37518173],
    [50, "BOS7", "joumH", 35821783],
]

def main():
    repo_root = Path(__file__).parent.parent
    alliances_file = repo_root / "data" / "alliances.json"
    csv_file = repo_root / "data" / "power-history.csv"

    # Read current alliances.json
    with open(alliances_file, 'r', encoding='utf-8') as f:
        alliances = json.load(f)

    # Create a mapping of tag -> alliance data
    alliance_map = {a['tag']: a for a in alliances}

    # Update existing alliances and track new ones
    updated_count = 0
    added_count = 0
    new_alliances = []

    for rank, tag, name, power in ALLIANCE_DATA:
        if tag in alliance_map:
            # Update existing alliance
            alliance_map[tag]['rank'] = rank
            alliance_map[tag]['power'] = power
            # Update name if it's different
            if alliance_map[tag]['name'] != name:
                try:
                    print(f"Name change detected for {tag}: '{alliance_map[tag]['name']}' -> '{name}'")
                except:
                    print(f"Name change detected for {tag}")
                alliance_map[tag]['name'] = name
            updated_count += 1
        else:
            # New alliance
            new_alliance = {
                "rank": rank,
                "tag": tag,
                "name": name,
                "r5": f"R5 of {tag}",
                "signed": False,
                "power": power,
                "r5History": [
                    {
                        "r5Name": f"R5 of {tag}",
                        "gameId": None,
                        "discordId": None,
                        "startDate": "2025-10-12T00:00:00Z",
                        "endDate": None,
                        "current": True,
                        "signatures": []
                    }
                ]
            }
            alliance_map[tag] = new_alliance
            new_alliances.append(tag)
            added_count += 1

    # Rebuild alliances list sorted by rank
    updated_alliances = sorted(alliance_map.values(), key=lambda x: x['rank'])

    # Write updated alliances.json
    with open(alliances_file, 'w', encoding='utf-8') as f:
        json.dump(updated_alliances, f, indent=4, ensure_ascii=False)

    print(f"[OK] Updated alliances.json:")
    print(f"  - Updated {updated_count} existing alliances")
    print(f"  - Added {added_count} new alliances: {', '.join(new_alliances)}")

    # Update CSV file
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        headers = list(reader.fieldnames)
        rows = list(reader)

    # Find and update the 2025-10-12 row
    date_found = False
    for row in rows:
        if row['date'] == NEW_DATE:
            date_found = True
            # Create power map
            power_map = {tag: power for rank, tag, name, power in ALLIANCE_DATA}

            # Update all power values
            for tag in headers:
                if tag != 'date':
                    row[tag] = power_map.get(tag, row.get(tag, ''))

            print(f"\n[OK] Updated existing row for {NEW_DATE} in CSV")
            break

    if not date_found:
        print(f"\n[WARNING] Date {NEW_DATE} not found in CSV - this shouldn't happen!")

    # Write updated CSV
    with open(csv_file, 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        writer.writerows(rows)

    print("\n[OK] All corrections complete!")
    print("\nCorrected Top 10:")
    for rank, tag, name, power in ALLIANCE_DATA[:10]:
        print(f"{rank:2d}. [{tag:4s}] {name:25s} - {power:,}")

if __name__ == '__main__':
    main()
