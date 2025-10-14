#!/usr/bin/env python3
"""
Update alliance data from 2025-10-12 ranking screenshot
"""
import json
import csv
import os
from pathlib import Path

# Data extracted from 2025-10-12 ranking image
NEW_DATE = "2025-10-12"

# Alliance data: [rank, tag, name, power]
ALLIANCE_DATA = [
    [1, "UvvU", "veni vidi vici", 7804360932],
    [2, "ORCE", "Omega Force", 6652835243],
    [3, "nkot", "korea one team", 5653417793],
    [4, "STR8", "STR8 SAVAGE", 4398383458],
    [5, "EPIC", "KarmaKings", 4387455480],
    [6, "LGbt", "Legion d-Aba", 4388688458],
    [7, "404a", "Not Found", 4382528509],
    [8, "NYPR", "NY Bats", 3785873485],
    [9, "SWBA", "SinnersWillBeAshed", 3681539337],
    [10, "86KO", "무적까까아니", 2633268782],
    [11, "FNXS", "F é n i x", 2638735693],
    [12, "UUSN", "Kantinny", 911282454],
    [13, "NiKi", "NIKII", 1677883859],
    [14, "L4TM", "La Última Orden", 1827891706],
    [15, "MTOP", "MonteOlimpo", 1835412238],
    [16, "Zvrl", "Zvrl", 1835354254],
    [17, "LSKJ", "The Lasts", 568731838],
    [18, "FRHD", "United Fight", 543481248],
    [19, "z5fa", "z5fa", 544297186],
    [20, "LeSo", "loving tstore", 417254829],
    [21, "w1l0", "MountainHeroes", 438512191],
    [22, "BrzS", "Boys of sin", 324686789],
    [23, "SXRS", "Sanguinário451", 321887989],
    [24, "AMhE", "Shuarma", 285526323],
    [25, "STNK", "LuckyBastard777", 273337549],
    [26, "SOUL", "Blowhard", 48828129],
    [27, "TSTG", "Boom de guerre", 43964927],
    [28, "BSoG", "BloodStone", 43763816],
    [29, "TmpL", "TEMPLAP", 43558988],
    [30, "LHPS", "love peace", 37518173],
    [31, "joumH", "joumH", 35821783],
    [32, "LGLW", "Lostwaffenkrieger", 61317088],
    [33, "SWGE", "Evil Walkin Loudly", 54735436],
    [34, "CHVL", "Chosen", 50877549],
    [35, "RNWF", "Reinforced", 48628329],
    [36, "ArtW", "de guerre", 43964927],
    [37, "EvLB", "Evarlasting", 43763816],
    [38, "SLMA", "astanhão", 129478918],
    [39, "SLTR", "Slither de Caos", 188438787],
    [40, "MbSt", "AmericanSlice", 181957917],
    [41, "LsBJ", "I mala talLike", 161576468],
    [42, "Lbdu", "= s =gd.d =", 61317088],
    [43, "Bbr1", "Kali Walks Loudly", 54735436],
    [44, "LsN0", "Chaos", 50877549],
    [45, "SHDW", "Reinforced", 48628329],
    [46, "MAnD", "Boom de guerre", 43964927],
    [47, "MssH", "Evarlasting", 43763816],
    [48, "FYPE", "Fertile Magik A", 62361062],
    [49, "SWSL", "Laëla", 140078397],
    [50, "BOS7", "LostwaffenSolo", 113428691],
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

    # Update existing alliances and collect new ones
    new_alliances = []
    updated_count = 0
    added_count = 0

    for rank, tag, name, power in ALLIANCE_DATA:
        if tag in alliance_map:
            # Update existing alliance
            alliance_map[tag]['rank'] = rank
            alliance_map[tag]['power'] = power
            # Update name if it's different (in case of name changes)
            if alliance_map[tag]['name'] != name:
                print(f"Name change detected for {tag}: '{alliance_map[tag]['name']}' -> '{name}'")
                alliance_map[tag]['name'] = name
            updated_count += 1
        else:
            # New alliance - create minimal entry
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

    print(f"\n[OK] Updated alliances.json:")
    print(f"  - Updated {updated_count} existing alliances")
    print(f"  - Added {added_count} new alliances: {', '.join(new_alliances)}")

    # Update CSV file
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        headers = reader.fieldnames
        rows = list(reader)

    # Check if date already exists
    date_exists = any(row['date'] == NEW_DATE for row in rows)

    if date_exists:
        print(f"\n[WARNING] Date {NEW_DATE} already exists in CSV. Skipping CSV update.")
    else:
        # Build new row
        new_row = {'date': NEW_DATE}

        # Get all alliance tags from CSV headers
        csv_tags = [h for h in headers if h != 'date']

        # Create power map from our data
        power_map = {tag: power for rank, tag, name, power in ALLIANCE_DATA}

        # Fill in power values for each alliance in CSV headers
        for tag in csv_tags:
            new_row[tag] = power_map.get(tag, '')  # Empty string if no data

        # Add new alliances to headers if needed
        new_csv_tags = [tag for rank, tag, name, power in ALLIANCE_DATA if tag not in csv_tags]

        if new_csv_tags:
            # Add new columns to headers
            headers = list(headers) + new_csv_tags
            # Fill in empty values for new columns in old rows
            for row in rows:
                for tag in new_csv_tags:
                    row[tag] = ''
            # Add values for new columns in new row
            for tag in new_csv_tags:
                new_row[tag] = power_map[tag]

        # Append new row
        rows.append(new_row)

        # Write updated CSV
        with open(csv_file, 'w', encoding='utf-8', newline='') as f:
            writer = csv.DictWriter(f, fieldnames=headers)
            writer.writeheader()
            writer.writerows(rows)

        print(f"\n[OK] Updated power-history.csv:")
        print(f"  - Added new row for {NEW_DATE}")
        if new_csv_tags:
            print(f"  - Added new columns: {', '.join(new_csv_tags)}")

    print("\n[OK] All updates complete!")

if __name__ == '__main__':
    main()
