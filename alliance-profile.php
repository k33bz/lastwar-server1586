<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alliance Profile - Last War Server 1586</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard-redesign.css">
    <style>
        .profile-container {
            max-width: 900px;
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
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        .info-box {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        .info-box h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 18px;
        }

        .info-box p {
            margin: 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .info-box ol {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: rgba(255, 255, 255, 0.8);
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
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .alliance-badge {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.4);
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
        }

        .role-badge.r5 {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.4);
        }

        .role-badge.r4 {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            border: 1px solid rgba(102, 126, 234, 0.4);
        }

        .current-value {
            background: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
        }

        .current-value strong {
            color: #667eea;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.4);
        }

        .alert-error {
            background: rgba(235, 87, 87, 0.2);
            color: #eb5757;
            border: 1px solid rgba(235, 87, 87, 0.4);
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="profile-container">
        <!-- Search Section -->
        <div class="profile-card" id="searchSection">
            <div class="profile-header">
                <h1>🛡️ Alliance Profile</h1>
                <p>Link your Discord account to participate in council voting</p>
            </div>

            <div class="info-box">
                <h3>📋 For R5s and R4s</h3>
                <p>
                    This page allows you to link your Discord account to your alliance role.
                    Once linked, you'll be able to vote on council proposals via Discord DMs.
                </p>
            </div>

            <div class="form-group">
                <label for="searchAlliance">Select Your Alliance</label>
                <select id="searchAlliance">
                    <option value="">Choose your alliance...</option>
                </select>
            </div>

            <div class="form-group">
                <label for="searchRole">Your Role</label>
                <select id="searchRole">
                    <option value="">Select your role...</option>
                    <option value="r5">R5 (Leader)</option>
                    <option value="r4">R4 (Officer)</option>
                </select>
            </div>

            <div class="form-group" id="nameGroup" style="display: none;">
                <label for="searchName">Your In-Game Name</label>
                <input type="text" id="searchName" placeholder="Enter your exact in-game name">
                <p class="form-help">For R4s: Enter your name exactly as it appears in the alliance roster</p>
            </div>

            <button class="btn btn-primary" onclick="searchProfile()">Find My Profile</button>
        </div>

        <!-- Edit Section -->
        <div class="profile-card hidden" id="editSection">
            <div class="profile-header">
                <div class="alliance-badge" id="profileAllianceTag"></div>
                <span class="role-badge" id="profileRoleBadge"></span>
                <h2 id="profileName" style="margin: 10px 0 0 0; color: white;"></h2>
            </div>

            <div id="alertContainer"></div>

            <div class="info-box">
                <h3>🔗 How to Get Your Discord User ID</h3>
                <ol>
                    <li>Open Discord → Settings → Advanced</li>
                    <li>Enable "Developer Mode"</li>
                    <li>Right-click your name anywhere in Discord</li>
                    <li>Click "Copy User ID"</li>
                    <li>Paste the 18-digit number below</li>
                </ol>
            </div>

            <form id="profileForm" onsubmit="saveProfile(event)">
                <div class="form-group">
                    <label for="discordId">Discord User ID *</label>
                    <input
                        type="text"
                        id="discordId"
                        placeholder="199257650154831872"
                        pattern="[0-9]{17,19}"
                        required
                    >
                    <p class="form-help">Your 17-19 digit Discord user ID (not your username)</p>
                    <div class="current-value" id="currentDiscordId"></div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Save Discord ID</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentAlliance = null;
        let currentRole = null;
        let currentMemberData = null;

        // Load alliances on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadAlliances();
        });

        // Show name field when R4 is selected
        document.getElementById('searchRole').addEventListener('change', function(e) {
            const nameGroup = document.getElementById('nameGroup');
            if (e.target.value === 'r4') {
                nameGroup.style.display = 'block';
            } else {
                nameGroup.style.display = 'none';
            }
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
                showAlert('Failed to load alliances. Please refresh the page.', 'error');
            }
        }

        async function searchProfile() {
            const allianceTag = document.getElementById('searchAlliance').value;
            const role = document.getElementById('searchRole').value;
            const name = document.getElementById('searchName').value.trim();

            if (!allianceTag) {
                showAlert('Please select your alliance', 'error');
                return;
            }

            if (!role) {
                showAlert('Please select your role', 'error');
                return;
            }

            if (role === 'r4' && !name) {
                showAlert('Please enter your in-game name', 'error');
                return;
            }

            try {
                const response = await fetch('api/alliances_api.php');
                const alliances = await response.json();

                const alliance = alliances.find(a => a.tag === allianceTag);

                if (!alliance) {
                    showAlert('Alliance not found', 'error');
                    return;
                }

                if (role === 'r5') {
                    if (!alliance.r5) {
                        showAlert('No R5 configured for this alliance', 'error');
                        return;
                    }

                    currentAlliance = alliance;
                    currentRole = 'r5';
                    currentMemberData = alliance.r5;
                    showEditForm(alliance, 'R5', alliance.r5.name, alliance.r5.discordId);

                } else if (role === 'r4') {
                    if (!alliance.r4s || alliance.r4s.length === 0) {
                        showAlert('No R4s configured for this alliance', 'error');
                        return;
                    }

                    const r4 = alliance.r4s.find(r => r.name.toLowerCase() === name.toLowerCase());

                    if (!r4) {
                        showAlert(`R4 named "${name}" not found in ${allianceTag}. Please check the spelling.`, 'error');
                        return;
                    }

                    currentAlliance = alliance;
                    currentRole = 'r4';
                    currentMemberData = r4;
                    showEditForm(alliance, 'R4', r4.name, r4.discordId);
                }

            } catch (error) {
                console.error('Search failed:', error);
                showAlert('Failed to search. Please try again.', 'error');
            }
        }

        function showEditForm(alliance, roleLabel, memberName, currentDiscordId) {
            document.getElementById('searchSection').classList.add('hidden');
            document.getElementById('editSection').classList.remove('hidden');

            document.getElementById('profileAllianceTag').textContent = `[${alliance.tag}]`;
            document.getElementById('profileRoleBadge').textContent = roleLabel;
            document.getElementById('profileRoleBadge').className = `role-badge ${currentRole}`;
            document.getElementById('profileName').textContent = memberName;

            document.getElementById('discordId').value = currentDiscordId || '';

            const currentValueDiv = document.getElementById('currentDiscordId');
            if (currentDiscordId) {
                currentValueDiv.innerHTML = `<strong>Current:</strong> ${currentDiscordId}`;
            } else {
                currentValueDiv.innerHTML = `<strong>Current:</strong> <em style="color: rgba(255,255,255,0.5);">Not set</em>`;
            }

            // Clear alerts
            document.getElementById('alertContainer').innerHTML = '';
        }

        function cancelEdit() {
            document.getElementById('editSection').classList.add('hidden');
            document.getElementById('searchSection').classList.remove('hidden');
            currentAlliance = null;
            currentRole = null;
            currentMemberData = null;
        }

        async function saveProfile(event) {
            event.preventDefault();

            const discordId = document.getElementById('discordId').value.trim();

            if (!discordId) {
                showAlert('Please enter your Discord ID', 'error');
                return;
            }

            if (!/^[0-9]{17,19}$/.test(discordId)) {
                showAlert('Invalid Discord ID format. Must be 17-19 digits.', 'error');
                return;
            }

            try {
                const endpoint = currentRole === 'r5'
                    ? 'api/alliance_r5_profile_api.php'
                    : 'api/alliance_r4_profile_api.php';

                const payload = {
                    alliance_tag: currentAlliance.tag,
                    discord_id: discordId
                };

                if (currentRole === 'r4') {
                    payload.name = currentMemberData.name;
                }

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('✅ Discord ID updated successfully! You can now vote via Discord DMs.', 'success');

                    // Update current value display
                    document.getElementById('currentDiscordId').innerHTML = `<strong>Current:</strong> ${discordId}`;
                    currentMemberData.discordId = discordId;
                } else {
                    showAlert('Failed to update: ' + (result.error || 'Unknown error'), 'error');
                }

            } catch (error) {
                console.error('Save failed:', error);
                showAlert('Failed to save. Please try again.', 'error');
            }
        }

        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.innerHTML = '';
            container.appendChild(alert);

            // Scroll to alert
            alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Auto-remove success alerts after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            }
        }
    </script>
</body>
</html>
