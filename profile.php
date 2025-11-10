<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Last War Server 1586</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard-redesign.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: var(--card-bg, rgba(30, 30, 40, 0.95));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            color: white;
        }

        .profile-info h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
            color: var(--text-primary, white);
        }

        .profile-info .alliance-tag {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.4);
            border-radius: 4px;
            font-size: 14px;
            color: #667eea;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary, white);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Rajdhani', sans-serif;
            font-size: 16px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-help {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 6px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }

        .status-verified {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.4);
        }

        .status-unverified {
            background: rgba(255, 159, 67, 0.2);
            color: #ff9f43;
            border: 1px solid rgba(255, 159, 67, 0.4);
        }

        .info-box {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            margin: 0 0 8px 0;
            color: #667eea;
            font-size: 16px;
        }

        .info-box p {
            margin: 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        .search-section {
            margin-bottom: 30px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="profile-container">
        <!-- Search/Claim Profile Section -->
        <div class="profile-card search-section" id="searchSection">
            <h2>Find Your Profile</h2>
            <p class="form-help">Search for your in-game name to claim or update your profile.</p>

            <div class="form-group">
                <label for="searchAlliance">Your Alliance</label>
                <select id="searchAlliance">
                    <option value="">Select your alliance...</option>
                </select>
            </div>

            <div class="form-group">
                <label for="searchName">Your In-Game Name</label>
                <input type="text" id="searchName" placeholder="Enter your exact in-game name">
            </div>

            <button class="btn btn-primary" onclick="searchProfile()">Find My Profile</button>
        </div>

        <!-- Profile Edit Section (hidden initially) -->
        <div class="profile-card" id="profileSection" style="display: none;">
            <div class="profile-header">
                <div class="profile-avatar" id="profileAvatar">?</div>
                <div class="profile-info">
                    <h1 id="profileName">Loading...</h1>
                    <span class="alliance-tag" id="profileAlliance"></span>
                    <span class="status-badge" id="profileStatus"></span>
                </div>
            </div>

            <div class="info-box" id="infoBox">
                <h3>📝 How to Update Your Discord ID</h3>
                <p>1. Open Discord → Settings → Advanced → Enable "Developer Mode"<br>
                   2. Right-click your name anywhere in Discord → Copy User ID<br>
                   3. Paste the ID below and click Save</p>
            </div>

            <form id="profileForm">
                <input type="hidden" id="profileId">

                <div class="form-group">
                    <label for="gameId">Game ID (Optional)</label>
                    <input type="text" id="gameId" placeholder="Your in-game player ID">
                    <p class="form-help">This can help verify your identity</p>
                </div>

                <div class="form-group">
                    <label for="discordId">Discord User ID</label>
                    <input type="text" id="discordId" placeholder="123456789012345678" pattern="[0-9]{17,19}">
                    <p class="form-help">Your 18-digit Discord user ID (not your username)</p>
                </div>

                <div class="form-group">
                    <label for="discordTag">Discord Username</label>
                    <input type="text" id="discordTag" placeholder="username#1234">
                    <p class="form-help">Your Discord username for display purposes</p>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentProfile = null;

        // Load alliances on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadAlliances();
        });

        async function loadAlliances() {
            try {
                const response = await fetch('api/alliances_api.php');
                const alliances = await response.json();

                const select = document.getElementById('searchAlliance');
                alliances.forEach(alliance => {
                    const option = document.createElement('option');
                    option.value = alliance.tag;
                    option.textContent = `[${alliance.tag}] ${alliance.name}`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Failed to load alliances:', error);
            }
        }

        async function searchProfile() {
            const alliance = document.getElementById('searchAlliance').value;
            const name = document.getElementById('searchName').value.trim();

            if (!alliance || !name) {
                alert('Please select your alliance and enter your in-game name');
                return;
            }

            try {
                const response = await fetch(`api/profile_api.php?action=search&alliance=${encodeURIComponent(alliance)}&name=${encodeURIComponent(name)}`);
                const result = await response.json();

                if (result.found) {
                    currentProfile = result.profile;
                    showProfile(result.profile);
                } else {
                    // Create new profile
                    if (confirm('Profile not found. Would you like to create one?')) {
                        createNewProfile(alliance, name);
                    }
                }
            } catch (error) {
                console.error('Search failed:', error);
                alert('Failed to search for profile. Please try again.');
            }
        }

        async function createNewProfile(alliance, name) {
            try {
                const response = await fetch('api/profile_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        alliance_tag: alliance,
                        game_name: name
                    })
                });

                const result = await response.json();
                if (result.success) {
                    currentProfile = result.profile;
                    showProfile(result.profile);
                } else {
                    alert('Failed to create profile: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Create failed:', error);
                alert('Failed to create profile. Please try again.');
            }
        }

        function showProfile(profile) {
            document.getElementById('searchSection').style.display = 'none';
            document.getElementById('profileSection').style.display = 'block';

            // Set avatar (first letter of name)
            document.getElementById('profileAvatar').textContent = profile.game_name.charAt(0).toUpperCase();

            // Set header info
            document.getElementById('profileName').textContent = profile.game_name;
            document.getElementById('profileAlliance').textContent = profile.alliance_tag;

            const statusBadge = document.getElementById('profileStatus');
            if (profile.verified) {
                statusBadge.className = 'status-badge status-verified';
                statusBadge.textContent = '✓ Verified';
            } else {
                statusBadge.className = 'status-badge status-unverified';
                statusBadge.textContent = '⚠ Unverified';
            }

            // Fill form
            document.getElementById('profileId').value = profile.profile_id || '';
            document.getElementById('gameId').value = profile.game_id || '';
            document.getElementById('discordId').value = profile.discord_id || '';
            document.getElementById('discordTag').value = profile.discord_tag || '';
        }

        function cancelEdit() {
            document.getElementById('searchSection').style.display = 'block';
            document.getElementById('profileSection').style.display = 'none';
            currentProfile = null;
        }

        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const data = {
                action: 'update',
                profile_id: document.getElementById('profileId').value,
                alliance_tag: currentProfile.alliance_tag,
                game_name: currentProfile.game_name,
                game_id: document.getElementById('gameId').value,
                discord_id: document.getElementById('discordId').value,
                discord_tag: document.getElementById('discordTag').value
            };

            try {
                const response = await fetch('api/profile_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    alert('✅ Profile updated successfully!');
                    currentProfile = result.profile;
                    showProfile(result.profile);
                } else {
                    alert('Failed to update profile: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Update failed:', error);
                alert('Failed to update profile. Please try again.');
            }
        });
    </script>
</body>
</html>
