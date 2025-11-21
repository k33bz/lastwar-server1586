<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Your Vote - Server 1586</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .vote-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        .vote-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .vote-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .vote-header .vote-id {
            font-size: 0.875rem;
            opacity: 0.9;
            font-family: 'Courier New', monospace;
        }

        .vote-body {
            padding: 2rem;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #667eea;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 8px;
            padding: 1.5rem;
            color: #c33;
            text-align: center;
        }

        .error-message h2 {
            margin-bottom: 0.5rem;
        }

        .vote-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .vote-info h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .vote-info p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .vote-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .vote-meta-item {
            background: white;
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .vote-meta-item label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .vote-meta-item span {
            display: block;
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        .voter-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .voter-info strong {
            color: #1565c0;
        }

        .vote-options {
            display: grid;
            gap: 1rem;
        }

        .vote-option {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .vote-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .vote-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .vote-option-content {
            flex: 1;
        }

        .vote-option-content h3 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .vote-option-content p {
            font-size: 0.875rem;
            color: #666;
        }

        .vote-option.yes h3 { color: #28a745; }
        .vote-option.no h3 { color: #dc3545; }
        .vote-option.abstain h3 { color: #ffc107; }

        .vote-option input[type="radio"]:checked + .vote-option-content {
            font-weight: 600;
        }

        .vote-option:has(input[type="radio"]:checked) {
            border-width: 3px;
        }

        .vote-option.yes:has(input[type="radio"]:checked) {
            border-color: #28a745;
            background: #f0fff0;
        }

        .vote-option.no:has(input[type="radio"]:checked) {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .vote-option.abstain:has(input[type="radio"]:checked) {
            border-color: #ffc107;
            background: #fffef0;
        }

        .submit-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 2rem;
            transition: all 0.2s;
        }

        .submit-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .submit-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .success-message {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
        }

        .success-message h2 {
            color: #28a745;
            margin-bottom: 1rem;
        }

        .success-message p {
            color: #666;
            line-height: 1.6;
        }

        .already-voted {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            color: #856404;
        }

        .already-voted h2 {
            margin-bottom: 0.5rem;
        }

        .countdown {
            font-size: 0.875rem;
            color: #666;
            text-align: center;
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .countdown strong {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="vote-container">
        <div class="vote-header">
            <h1>🗳️ Council Vote</h1>
            <div class="vote-id" id="voteId">Loading...</div>
        </div>

        <div class="vote-body">
            <div id="loadingState" class="loading">
                <div class="spinner"></div>
                <p>Verifying your vote token...</p>
            </div>

            <div id="errorState" style="display: none;">
                <!-- Error message will be inserted here -->
            </div>

            <div id="voteForm" style="display: none;">
                <!-- Vote form will be inserted here -->
            </div>

            <div id="successState" style="display: none;">
                <!-- Success message will be inserted here -->
            </div>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        let voteData = null;
        let selectedChoice = null;

        // Load vote eligibility
        async function loadVote() {
            if (!token) {
                showError('Invalid Link', 'No vote token provided. Please use the link from your email.');
                return;
            }

            try {
                const response = await fetch(`discord_vote_submit_api.php?action=verify_vote_eligibility&token=${encodeURIComponent(token)}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to verify vote token');
                }

                voteData = data;

                if (data.already_voted) {
                    showAlreadyVoted();
                } else if (data.is_expired) {
                    showError('Vote Expired', 'This vote has expired or is no longer active.');
                } else {
                    showVoteForm();
                }

            } catch (error) {
                showError('Error', error.message || 'Failed to load vote information');
            }
        }

        function showError(title, message) {
            document.getElementById('loadingState').style.display = 'none';
            const errorState = document.getElementById('errorState');
            errorState.innerHTML = `
                <div class="error-message">
                    <h2>${title}</h2>
                    <p>${message}</p>
                </div>
            `;
            errorState.style.display = 'block';
        }

        function showAlreadyVoted() {
            document.getElementById('loadingState').style.display = 'none';
            const voteForm = document.getElementById('voteForm');
            voteForm.innerHTML = `
                <div class="already-voted">
                    <h2>✅ Vote Already Submitted</h2>
                    <p>Your alliance has already submitted a vote for this matter.</p>
                </div>
                <div class="vote-info">
                    <h2>${voteData.vote.title}</h2>
                    <p>${voteData.vote.description}</p>
                </div>
            `;
            voteForm.style.display = 'block';
            document.getElementById('voteId').textContent = voteData.vote.vote_id;
        }

        function showVoteForm() {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('voteId').textContent = voteData.vote.vote_id;

            const endTime = new Date(voteData.vote.end_time);
            const formattedEndTime = endTime.toLocaleString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const voteForm = document.getElementById('voteForm');
            voteForm.innerHTML = `
                <div class="vote-info">
                    <h2>${voteData.vote.title}</h2>
                    <p>${voteData.vote.description}</p>
                    <div class="vote-meta">
                        <div class="vote-meta-item">
                            <label>Category</label>
                            <span>${voteData.vote.category.replace('_', ' ').toUpperCase()}</span>
                        </div>
                        <div class="vote-meta-item">
                            <label>Voting Deadline</label>
                            <span id="deadline">${formattedEndTime}</span>
                        </div>
                    </div>
                </div>

                <div class="voter-info">
                    <strong>Voting as:</strong> ${voteData.voter_info.alliance_tag} - ${voteData.voter_info.username}
                </div>

                <form id="submitVoteForm">
                    <div class="vote-options">
                        <label class="vote-option yes">
                            <input type="radio" name="vote_choice" value="yes" onchange="selectChoice('yes')">
                            <div class="vote-option-content">
                                <h3>✅ Yes</h3>
                                <p>Vote in favor of this proposal</p>
                            </div>
                        </label>

                        <label class="vote-option no">
                            <input type="radio" name="vote_choice" value="no" onchange="selectChoice('no')">
                            <div class="vote-option-content">
                                <h3>❌ No</h3>
                                <p>Vote against this proposal</p>
                            </div>
                        </label>

                        <label class="vote-option abstain">
                            <input type="radio" name="vote_choice" value="abstain" onchange="selectChoice('abstain')">
                            <div class="vote-option-content">
                                <h3>⚪ Abstain</h3>
                                <p>Neither for nor against</p>
                            </div>
                        </label>
                    </div>

                    <button type="submit" class="submit-button" id="submitButton" disabled>
                        Submit Your Vote
                    </button>
                </form>

                <div class="countdown" id="countdown"></div>
            `;

            voteForm.style.display = 'block';

            // Start countdown
            updateCountdown();
            setInterval(updateCountdown, 1000);

            // Handle form submission
            document.getElementById('submitVoteForm').addEventListener('submit', submitVote);
        }

        function selectChoice(choice) {
            selectedChoice = choice;
            document.getElementById('submitButton').disabled = false;
        }

        async function submitVote(event) {
            event.preventDefault();

            if (!selectedChoice) {
                alert('Please select a vote option');
                return;
            }

            const submitButton = document.getElementById('submitButton');
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';

            try {
                const response = await fetch('discord_vote_submit_api.php?action=submit_vote', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        vote_id: voteData.vote.vote_id,
                        vote_choice: selectedChoice,
                        submission_method: 'email',
                        token: token
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to submit vote');
                }

                showSuccess(data);

            } catch (error) {
                alert('Error submitting vote: ' + error.message);
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Your Vote';
            }
        }

        function showSuccess(data) {
            document.getElementById('voteForm').style.display = 'none';
            const successState = document.getElementById('successState');

            const choiceEmoji = {
                'yes': '✅',
                'no': '❌',
                'abstain': '⚪'
            }[selectedChoice];

            const choiceText = selectedChoice.charAt(0).toUpperCase() + selectedChoice.slice(1);

            successState.innerHTML = `
                <div class="success-message">
                    <div class="success-icon">${choiceEmoji}</div>
                    <h2>Vote Submitted Successfully!</h2>
                    <p>Your vote of <strong>${choiceText}</strong> has been recorded.</p>
                    <p style="margin-top: 1rem; font-size: 0.875rem;">
                        <strong>${data.vote_status.total_submitted}</strong> of
                        <strong>${data.vote_status.total_eligible}</strong> council members have voted.
                    </p>
                    ${data.vote_status.all_votes_submitted ?
                        '<p style="margin-top: 1rem; color: #28a745; font-weight: 600;">All votes have been submitted. Results will be announced shortly.</p>' :
                        '<p style="margin-top: 1rem; color: #666;">Results will be announced when all votes are in or the deadline is reached.</p>'
                    }
                </div>
            `;
            successState.style.display = 'block';
        }

        function updateCountdown() {
            if (!voteData) return;

            const now = new Date();
            const end = new Date(voteData.vote.end_time);
            const diff = end - now;

            if (diff <= 0) {
                document.getElementById('countdown').innerHTML = '<strong>Voting has ended</strong>';
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            document.getElementById('countdown').innerHTML =
                `Time remaining: <strong>${hours}h ${minutes}m ${seconds}s</strong>`;
        }

        // Initialize
        loadVote();
    </script>
</body>
</html>
