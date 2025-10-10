/**
 * Server 1586 - Main Application Script
 *
 * Handles all dynamic rendering and user interactions for the homepage.
 *
 * CHANGELOG:
 * v1.7.0 - 2025-10-08
 * - Added cache-busting to JSON data fetches
 * - Version query parameters force browser to reload updated data
 * - No more stale cached data on updates
 *
 * v1.6.0 - 2025-10-07
 * - Changed rotation schedule to use alliance tags instead of ranks
 * - Schedule now stable when alliance rankings change
 * - Historical accuracy preserved (shows which alliances actually rotated)
 *
 * v1.5.0 - 2025-10-07
 * - Migrated rotation schedule to pre-generated JSON file
 * - Removed dynamic rotation calculation logic
 * - Added countdown timer with real-time updates
 * - Schedule now shows previous, current, and next 4 weeks only
 * - Rotation schedule can be edited externally without code changes
 *
 * v1.4.0 - 2025-10-06
 * - Migrated from JavaScript data files to JSON format
 * - Added async data loading with fetch API
 * - Added error handling for missing web server
 * - Data files now separate: alliances.json, rules.json, amendments.json
 * - Improved separation of data and code
 *
 * v1.3.2 - 2025-10-06
 * - Fixed bug where clicking amendment expand arrows triggered wrong sections
 * - Updated amendment ID generation to include title for uniqueness
 * - Changed council layout from 3-2-2 to 5-2 for better visual balance
 * - Added previous week to rotation schedule (greyed out) instead of separate section
 * - Updated grid to show all 5 permanent members in first row, 2 rotating in second row
 * 
 * v1.3.1 - 2025-10-06
 * - Increased logo placeholder size to 70x70 to show full 4-letter alliance tags
 * - Updated logo to display all 4 letters instead of just 2
 * 
 * v1.3.0 - 2025-10-06
 * - Added createMemberCard() helper for council member rendering
 * - Updated renderCouncil() with 3-2-2 grid layout
 * - Added alliance logo placeholder support (initially 50x50, updated to 70x70)
 * - Implemented gold theme for permanent members, bronze for rotating
 * - Added timezone tooltip generation for schedule items
 * - Improved schedule display with hover states
 * 
 * v1.2.0 - 2025-10-05
 * - Added renderCouncil() for dynamic council member display
 * - Implemented weekly rotation calculations
 * - Added timezone display functionality
 * 
 * v1.1.0 - 2025-10-05
 * - Added renderAmendments() with collapsible versions
 * - Implemented toggleAmendments() and toggleAmendmentVersion()
 * - Added amendment change tracking (show/hide markers)
 * 
 * v1.0.0 - 2025-10-05
 * - Initial release with core rendering functions
 * - Alliance rankings display
 * - Rules display with version control
 * 
 * DEPENDENCIES:
 * - data/alliances.json (loaded via fetch)
 * - data/rules.json (loaded via fetch)
 * - data/amendments.json (loaded via fetch)
 * - data/rotation-schedule.json (loaded via fetch)
 * - data/council.js (loaded as script for utility functions)
 */

/* ============================================
   GLOBAL STATE
   ============================================ */
   let showChangesEnabled = false;
   let currentRules = [];
   let alliances = [];
   let serverRules = [];
   let amendments = [];
   let rotationSchedule = null;
   let countdownInterval = null;
   let powerChart = null;
   let powerHistory = null;
   let serverInfo = null;
   let signatureHistory = null;

   /* ============================================
      UTILITY FUNCTIONS
      ============================================ */
   
   /**
    * Get current version from amendments array
    * @returns {string} Current version number (e.g., '1.0' or '1.2')
    */
   function getCurrentVersion() {
       if (!amendments || amendments.length === 0) {
           return '1.0';
       }
       return amendments[amendments.length - 1].version;
   }
   
   /**
    * Create deep copy of object
    * @param {Object} obj - Object to copy
    * @returns {Object} Deep copy
    */
   function deepCopy(obj) {
       return JSON.parse(JSON.stringify(obj));
   }

   /**
    * Get signature status for an alliance
    * @param {string} allianceTag - Alliance tag
    * @returns {Object} Status object with signed, status, message, etc.
    */
   function getSignatureStatus(allianceTag) {
       if (!signatureHistory || !signatureHistory.alliances) {
           return { signed: false, status: 'no_data', message: 'Signature data not loaded', statusClass: 'pending' };
       }

       var alliance = signatureHistory.alliances.find(function(a) { return a.tag === allianceTag; });
       if (!alliance || !alliance.r5History || alliance.r5History.length === 0) {
           return { signed: false, status: 'no_r5', message: 'No R5 assigned', r5Name: 'No R5', statusClass: 'no-r5' };
       }

       var currentR5 = alliance.r5History.find(function(r5) { return r5.current === true; });
       if (!currentR5) {
           // Fallback to most recent (last in array)
           currentR5 = alliance.r5History[alliance.r5History.length - 1];
       }

       var currentVersion = signatureHistory.currentRulesVersion || '1.0';
       var hasSignedCurrent = currentR5.signatures && currentR5.signatures.some(function(sig) {
           return sig.version === currentVersion;
       });

       if (hasSignedCurrent) {
           var signature = currentR5.signatures.find(function(sig) { return sig.version === currentVersion; });
           return {
               signed: true,
               status: 'signed',
               message: '✓ Signed',
               r5Name: currentR5.r5Name,
               signedAt: signature.signedAt,
               version: currentVersion,
               statusClass: 'signed'
           };
       }

       // Check grace period (7 days from R5 start date)
       var startDate = new Date(currentR5.startDate);
       var now = new Date();
       var daysSinceStart = (now - startDate) / (1000 * 60 * 60 * 24);

       if (daysSinceStart <= 7) {
           var daysRemaining = Math.ceil(7 - daysSinceStart);
           return {
               signed: false,
               status: 'grace_period',
               message: '⏳ ' + daysRemaining + ' day' + (daysRemaining !== 1 ? 's' : '') + ' left',
               r5Name: currentR5.r5Name,
               daysRemaining: daysRemaining,
               statusClass: 'pending'
           };
       }

       // Overdue
       var daysOverdue = Math.floor(daysSinceStart - 7);
       return {
           signed: false,
           status: 'overdue',
           message: '⚠️ ' + daysOverdue + ' day' + (daysOverdue !== 1 ? 's' : '') + ' overdue',
           r5Name: currentR5.r5Name,
           daysOverdue: daysOverdue,
           statusClass: 'overdue'
       };
   }

   /**
    * Get current R5 for an alliance
    * @param {string} allianceTag - Alliance tag
    * @returns {Object|null} Current R5 object or null
    */
   function getCurrentR5(allianceTag) {
       if (!signatureHistory || !signatureHistory.alliances) return null;

       var alliance = signatureHistory.alliances.find(function(a) { return a.tag === allianceTag; });
       if (!alliance || !alliance.r5History || alliance.r5History.length === 0) return null;

       var currentR5 = alliance.r5History.find(function(r5) { return r5.current === true; });
       if (!currentR5) {
           currentR5 = alliance.r5History[alliance.r5History.length - 1];
       }

       return currentR5;
   }
   
   /* ============================================
      AMENDMENT PROCESSING
      ============================================ */
   
   /**
    * Apply all amendments to current rules
    * Handles both "show changes" mode (with markers) and clean mode
    */
   function applyAmendments() {
       if (!amendments || amendments.length === 0) return;
       
       // Group amendments by rule title
       var amendmentMap = {};
       for (var i = 0; i < amendments.length; i++) {
           var amendment = amendments[i];
           if (!amendmentMap[amendment.title]) {
               amendmentMap[amendment.title] = [];
           }
           amendmentMap[amendment.title].push(amendment);
       }
   
       // Process each rule that has amendments
       for (var j = 0; j < currentRules.length; j++) {
           var rule = currentRules[j];
           
           if (amendmentMap[rule.title]) {
               var ruleAmendments = amendmentMap[rule.title];
               
               for (var k = 0; k < ruleAmendments.length; k++) {
                   var amendment = ruleAmendments[k];
                   if (!amendment.changes) continue;
                   
                   for (var m = 0; m < amendment.changes.length; m++) {
                       var change = amendment.changes[m];
                       
                       if (change.type === 'remove' && rule.items) {
                           // Handle removals
                           var cleanText = change.text.replace(/<[^>]*>/g, '');
                           
                           for (var n = 0; n < rule.items.length; n++) {
                               var cleanItem = rule.items[n].replace(/<[^>]*>/g, '');
                               
                               if (cleanItem === cleanText) {
                                   if (showChangesEnabled) {
                                       // Show with strikethrough
                                       var indicator = '<span class="amendment-indicator removed">−</span>';
                                       var wrapped = '<span class="amendment-removed">' + rule.items[n] + '</span>';
                                       rule.items[n] = indicator + wrapped;
                                   } else {
                                       // Remove completely
                                       rule.items.splice(n, 1);
                                       n--;
                                   }
                                   break;
                               }
                           }
                       } else if (change.type === 'add') {
                           // Handle additions
                           if (!rule.items) rule.items = [];
                           
                           if (showChangesEnabled) {
                               // Show with green highlight
                               var indicator = '<span class="amendment-indicator added">+</span>';
                               var wrapped = '<span class="amendment-added">' + change.text + '</span>';
                               rule.items.push(indicator + wrapped);
                           } else {
                               // Add cleanly
                               rule.items.push(change.text);
                           }
                       }
                   }
               }
           }
       }
   }
   
   /* ============================================
      RENDER FUNCTIONS
      ============================================ */
   
   /**
    * Normalize alliance data to handle both v1.0 and v2.0 formats
    * @param {Object} alliance - Alliance data
    * @returns {Object} Normalized alliance data
    */
   function normalizeAlliance(alliance) {
       // Handle old format where r5 is just a string
       if (typeof alliance.r5 === 'string') {
           alliance.r5 = {
               name: alliance.r5,
               gameId: null,
               discordId: null
           };
       }

       // Set defaults for optional fields
       if (!alliance.discord) alliance.discord = {};
       if (!alliance.crossServer) alliance.crossServer = { hasPartner: false };
       if (!alliance.info) alliance.info = {};
       if (!alliance.contact) alliance.contact = {};
       if (!alliance.achievements) alliance.achievements = {};
       if (!alliance.metadata) alliance.metadata = {};

       return alliance;
   }

   /**
    * Render server Discord banner
    */
   function renderServerDiscord() {
       if (!serverInfo || !serverInfo.discord) return;

       var discord = serverInfo.discord;

       // Update server name
       if (discord.serverName) {
           document.getElementById('discordServerName').textContent = discord.serverName;
       }

       // Update description
       if (discord.description) {
           document.getElementById('discordDescription').textContent = discord.description;
       }

       // Update join button URL
       if (discord.inviteUrl) {
           document.getElementById('discordJoinButton').href = discord.inviteUrl;
       }

       // Update logo (hide if not available)
       var logoImg = document.getElementById('serverDiscordLogo');
       if (discord.logoUrl) {
           logoImg.src = discord.logoUrl;
           logoImg.style.display = 'block';
           logoImg.onerror = function() {
               // If image fails to load, hide it
               this.style.display = 'none';
           };
       } else {
           logoImg.style.display = 'none';
       }

       // Render features
       if (discord.features && discord.features.length > 0) {
           var featuresHtml = '';
           for (var i = 0; i < discord.features.length; i++) {
               featuresHtml += '<span class="discord-feature-tag">' + discord.features[i] + '</span>';
           }
           document.getElementById('discordFeatures').innerHTML = featuresHtml;
       }

       // Show member count if available
       if (discord.memberCount) {
           document.getElementById('memberCountValue').textContent = discord.memberCount.toLocaleString();
           document.getElementById('memberCount').style.display = 'block';
       }
   }

   /**
    * Render top 3 alliances podium with trophies
    */
   function renderPodium() {
       var podium = document.getElementById('podium');
       var top3 = alliances.slice(0, 3);
       var trophies = ['🏆', '🥈', '🥉'];
       var classes = ['first-place', 'second-place', 'third-place'];

       var html = '';
       for (var i = 0; i < top3.length; i++) {
           var alliance = normalizeAlliance(top3[i]);
           html += '<div class="podium-place ' + classes[i] + '" onclick="openAllianceModal(\'' + alliance.tag + '\')">';
           html += '<div class="trophy">' + trophies[i] + '</div>';
           html += '<div class="rank-number">#' + alliance.rank + '</div>';
           html += '<div class="alliance-tag">' + alliance.tag + '</div>';
           html += '<div class="alliance-name">' + alliance.name + '</div>';
           html += '</div>';
       }
       podium.innerHTML = html;
   }
   
   /**
    * Render alliances 4-15 in grid layout
    */
   function renderAllianceGrid() {
       var grid = document.getElementById('allianceGrid');
       var remaining = alliances.slice(3);
       
       var html = '';
       for (var i = 0; i < remaining.length; i++) {
           var alliance = remaining[i];
           html += '<div class="alliance-card" onclick="openAllianceModal(\'' + alliance.tag + '\')">';
           html += '<div class="alliance-rank">' + alliance.rank + '</div>';
           html += '<div class="alliance-info">';
           html += '<div class="alliance-tag">' + alliance.tag + '</div>';
           html += '<div class="alliance-name">' + alliance.name + '</div>';
           html += '</div></div>';
       }
       grid.innerHTML = html;
   }
   
   /**
    * Render R5 signatories with signature status
    */
   function renderSignatories() {
       var grid = document.getElementById('signatoriesGrid');

       var html = '';
       for (var i = 0; i < alliances.length; i++) {
           var alliance = alliances[i];
           var status = getSignatureStatus(alliance.tag);

           html += '<div class="signatory-card ' + status.statusClass + '" onclick="openAllianceModal(\'' + alliance.tag + '\')">';
           html += '<div class="signatory-rank">' + alliance.rank + '</div>';
           html += '<div class="signatory-info">';
           html += '<div class="signatory-alliance">' + alliance.tag + '</div>';
           html += '<div class="signatory-r5">' + status.r5Name + '</div>';
           html += '<div class="signatory-status">' + status.message + '</div>';
           html += '</div></div>';
       }
       grid.innerHTML = html;
   }
   
   /**
    * Render server rules with amendments applied
    */
   function renderRules() {
       var rulesInner = document.querySelector('.rules-inner');
       
       var html = '';
       for (var i = 0; i < currentRules.length; i++) {
           var rule = currentRules[i];
           var content = '';
           
           if (rule.content) {
               // Paragraph content
               for (var j = 0; j < rule.content.length; j++) {
                   content += '<p>' + rule.content[j] + '</p>';
               }
           } else if (rule.items) {
               // List content
               var listType = rule.type === 'ordered' ? 'ol' : 'ul';
               var listItems = '';
               for (var k = 0; k < rule.items.length; k++) {
                   listItems += '<li>' + rule.items[k] + '</li>';
               }
               content = '<' + listType + '>' + listItems + '</' + listType + '>';
           }
           
           html += '<div class="rule-category">';
           html += '<h3>' + rule.title + '</h3>';
           html += content;
           html += '</div>';
       }
       rulesInner.innerHTML = html;
   }
   
   /**
    * Create a single council member card
    * @param {Object} member - Alliance object
    * @param {string} type - 'permanent' or 'rotating'
    * @returns {string} HTML for member card
    */
   function createMemberCard(member, type) {
       var html = '<div class="council-member-card ' + type + '">';
       
       // Logo placeholder - shows all 4 letters of alliance tag
       // TO USE ACTUAL LOGOS: Replace with <img src="images/logos/[tag].png">
       html += '<div class="council-member-logo">';
       html += member.tag;
       html += '</div>';
       
       html += '<div class="council-member-rank-badge">Rank #' + member.rank + '</div>';
       html += '<div class="council-member-alliance">' + member.tag + '</div>';
       html += '<div class="council-member-name">' + member.tag + ' R5 - ' + member.r5 + '</div>';
       html += '<div class="council-member-status">';
       html += type === 'permanent' ? 'Permanent' : 'This Week';
       html += '</div>';
       html += '</div>';
       
       return html;
   }
   
   /**
    * Render council voting members with 5-2 grid layout
    * Reads from pre-generated rotation-schedule.json
    * Top row: 5 permanent members (ranks 1-5)
    * Bottom row: 2 rotating members from schedule
    */
   function renderCouncil() {
       if (!rotationSchedule || !rotationSchedule.schedule) {
           console.error('Rotation schedule not loaded');
           return;
       }

       var weekNumber = getCurrentWeekNumber();
       var permanentMembers = alliances.slice(0, 5);
       var nextReset = getNextWeekReset();

       // Find current week in schedule
       var currentWeekSchedule = rotationSchedule.schedule.find(function(w) {
           return w.weekNumber === weekNumber;
       });

       if (!currentWeekSchedule) {
           console.error('Current week not found in schedule');
           return;
       }

       // Get rotating members by tag from schedule
       var rotatingMembers = currentWeekSchedule.rotatingMembers.map(function(tag) {
           return alliances.find(function(a) { return a.tag === tag; });
       }).filter(function(a) { return a !== undefined; });

       // Update week display
       document.getElementById('currentWeekDisplay').textContent = 'Week ' + weekNumber;

       // Render council members in 5-2 grid
       var membersHtml = '';

       // Top row - All 5 permanent members (Gold)
       for (var i = 0; i < 5; i++) {
           membersHtml += createMemberCard(permanentMembers[i], 'permanent');
       }

       // Bottom row - 2 rotating members (Bronze)
       for (var k = 0; k < rotatingMembers.length; k++) {
           membersHtml += createMemberCard(rotatingMembers[k], 'rotating');
       }

       document.getElementById('councilMembersDisplay').innerHTML = membersHtml;

       // Start countdown timer
       startCountdownTimer(nextReset);

       // Filter and render schedule (previous, current, next 4 weeks = 6 total)
       var filteredSchedule = rotationSchedule.schedule.filter(function(week) {
           return week.weekNumber >= (weekNumber - 1) && week.weekNumber <= (weekNumber + 4);
       });

       var scheduleHtml = '<div class="schedule-grid">';

       for (var m = 0; m < filteredSchedule.length; m++) {
           var item = filteredSchedule[m];
           var isCurrent = item.weekNumber === weekNumber;
           var isPrevious = item.weekNumber === (weekNumber - 1);
           var weekDate = new Date(item.startDate);
           var gmtDate = formatGMT(weekDate);
           var allTimezones = formatAllTimezones(weekDate);

           // Get member details by tag
           var weekMembers = item.rotatingMembers.map(function(tag) {
               return alliances.find(function(a) { return a.tag === tag; });
           }).filter(function(a) { return a !== undefined; });

           // Add appropriate class based on week status
           var itemClass = 'schedule-item';
           if (isCurrent) {
               itemClass += ' current-week';
           } else if (isPrevious) {
               itemClass += ' previous-week';
           }

           scheduleHtml += '<div class="' + itemClass + '">';
           scheduleHtml += '<div class="schedule-week">Week ' + item.weekNumber;
           if (isCurrent) scheduleHtml += ' (Current)';
           if (isPrevious) scheduleHtml += ' (Previous)';
           scheduleHtml += '</div>';
           scheduleHtml += '<div class="schedule-date">' + gmtDate + '</div>';
           scheduleHtml += '<div class="schedule-members">Rotating: ';

           // Build member tags
           var memberTags = [];
           for (var n = 0; n < weekMembers.length; n++) {
               memberTags.push('<span>' + weekMembers[n].tag + '</span>');
           }
           scheduleHtml += memberTags.join(', ');
           scheduleHtml += '</div>';

           // Timezone tooltip (appears on hover)
           scheduleHtml += '<div class="timezone-tooltip">';
           scheduleHtml += '<div class="tooltip-title">All Timezones</div>';
           for (var p = 0; p < allTimezones.length; p++) {
               scheduleHtml += '<div class="tooltip-time">' + allTimezones[p] + '</div>';
           }
           scheduleHtml += '</div>';

           scheduleHtml += '</div>';
       }

       scheduleHtml += '</div>';
       document.getElementById('rotationScheduleDisplay').innerHTML = scheduleHtml;
   }

   /**
    * Start countdown timer that updates every second
    * @param {Date} targetDate - Target rotation date
    */
   function startCountdownTimer(targetDate) {
       // Clear any existing interval
       if (countdownInterval) {
           clearInterval(countdownInterval);
       }

       // Update immediately
       updateCountdown(targetDate);

       // Then update every second
       countdownInterval = setInterval(function() {
           updateCountdown(targetDate);
       }, 1000);
   }

   /**
    * Update countdown display
    * @param {Date} targetDate - Target rotation date
    */
   function updateCountdown(targetDate) {
       var countdown = formatCountdown(targetDate);
       document.getElementById('nextResetDisplay').textContent = countdown;
   }
   
   /**
    * Render amendment history with collapsible versions
    */
   function renderAmendments() {
       if (!amendments || amendments.length === 0) {
           document.getElementById('amendmentsSection').style.display = 'none';
           return;
       }
   
       document.getElementById('amendmentsSection').style.display = 'block';
       var amendmentsList = document.getElementById('amendmentsList');
       
       var html = '';
       for (var i = 0; i < amendments.length; i++) {
           var amendment = amendments[i];
           if (!amendment.changes) continue;
           
           // Create unique ID combining version and title to avoid conflicts
           var versionId = amendment.version.replace(/\./g, '-') + '-' + amendment.title.replace(/\s+/g, '-').toLowerCase();
           
           // Build changes HTML
           var changesHTML = '';
           for (var j = 0; j < amendment.changes.length; j++) {
               var change = amendment.changes[j];
               var indicator = change.type === 'add' ? '+' : '−';
               var className = change.type === 'add' ? 'amendment-added' : 'amendment-removed';
               var indicatorClass = change.type === 'add' ? 'added' : 'removed';
               
               changesHTML += '<div style="margin-bottom: 8px;">';
               changesHTML += '<span class="amendment-indicator ' + indicatorClass + '">' + indicator + '</span>';
               changesHTML += '<span class="' + className + '">' + change.text + '</span>';
               changesHTML += '</div>';
           }
   
           // Build collapsible amendment entry
           html += '<div class="amendment-entry">';
           html += '<div class="amendment-entry-header" onclick="toggleAmendmentVersion(\'' + versionId + '\')">';
           html += '<div>';
           html += '<div class="amendment-date">📅 ' + amendment.date + ' | Version ' + amendment.version + '</div>';
           html += '<div class="amendment-title">Section: ' + amendment.title + '</div>';
           html += '</div>';
           html += '<span class="amendment-toggle" id="toggle-' + versionId + '">▼</span>';
           html += '</div>';
           html += '<div class="amendment-changes" id="amendment-' + versionId + '">';
           html += changesHTML;
           html += '</div>';
           html += '</div>';
       }
       
       amendmentsList.innerHTML = html;
   }
   
   /**
    * Update version info badge
    */
   function updateVersionInfo() {
       var versionInfo = document.getElementById('versionInfo');
       versionInfo.textContent = 'Version ' + getCurrentVersion();
   }
   
   /* ============================================
      USER INTERACTION HANDLERS
      ============================================ */
   
   /**
    * Toggle rules section open/closed
    */
   function toggleRules() {
       var content = document.getElementById('rulesContent');
       var icon = document.getElementById('toggleIcon');
       
       content.classList.toggle('active');
       icon.classList.toggle('active');
   }
   
   /**
    * Toggle entire amendments section open/closed
    */
   function toggleAmendments() {
       var content = document.getElementById('amendmentsContent');
       var icon = document.getElementById('amendmentsToggleIcon');
       
       content.classList.toggle('active');
       icon.classList.toggle('active');
   }
   
   /**
    * Toggle individual amendment version open/closed
    * @param {string} versionId - Version identifier (e.g., '1-1' for v1.1)
    */
   function toggleAmendmentVersion(versionId) {
       var content = document.getElementById('amendment-' + versionId);
       var icon = document.getElementById('toggle-' + versionId);
       
       content.classList.toggle('active');
       icon.classList.toggle('active');
   }
   
   /**
    * Toggle between showing amendment markers (+/-) and clean view
    */
   function toggleChanges() {
       showChangesEnabled = document.getElementById('showChanges').checked;

       // Reset to original rules
       currentRules = deepCopy(serverRules);

       // Reapply amendments with new display mode
       applyAmendments();

       // Re-render rules
       renderRules();
   }

   /**
    * Toggle power trends section open/closed
    */
   function togglePowerTrends() {
       var content = document.getElementById('powerTrendsContent');
       var icon = document.getElementById('powerTrendsToggleIcon');

       content.classList.toggle('active');
       icon.classList.toggle('active');
   }

   /* ============================================
      ALLIANCE DETAIL MODAL
      ============================================ */

   /**
    * Open alliance detail modal
    * @param {string} allianceTag - Alliance tag to display
    */
   function openAllianceModal(allianceTag) {
       var alliance = alliances.find(function(a) { return a.tag === allianceTag; });
       if (!alliance) {
           console.error('Alliance not found:', allianceTag);
           return;
       }

       alliance = normalizeAlliance(alliance);

       // Update modal header
       document.getElementById('modalAllianceName').textContent = alliance.name;
       document.getElementById('modalAllianceTag').textContent = '#' + alliance.rank + ' • ' + alliance.tag;
       document.getElementById('modalAllianceLogo').textContent = alliance.tag;

       // Build modal body content
       var bodyHTML = '';

       // Basic Information Section
       bodyHTML += '<div class="modal-section">';
       bodyHTML += '<h3 class="modal-section-title">Basic Information</h3>';
       bodyHTML += '<div class="modal-info-grid">';

       bodyHTML += '<div class="modal-info-item">';
       bodyHTML += '<div class="modal-info-label">Rank</div>';
       bodyHTML += '<div class="modal-info-value highlight">#' + alliance.rank + '</div>';
       bodyHTML += '</div>';

       if (alliance.power) {
           bodyHTML += '<div class="modal-info-item">';
           bodyHTML += '<div class="modal-info-label">Power</div>';
           bodyHTML += '<div class="modal-info-value highlight">' + alliance.power.toLocaleString() + '</div>';
           bodyHTML += '</div>';
       }

       bodyHTML += '<div class="modal-info-item">';
       bodyHTML += '<div class="modal-info-label">R5 Leader</div>';
       bodyHTML += '<div class="modal-info-value">' + alliance.r5.name + '</div>';
       bodyHTML += '</div>';

       bodyHTML += '<div class="modal-info-item">';
       bodyHTML += '<div class="modal-info-label">NAP15 Status</div>';
       bodyHTML += '<div class="modal-info-value">' + (alliance.signed ? '✓ Signed' : '⏳ Pending') + '</div>';
       bodyHTML += '</div>';

       bodyHTML += '</div></div>';

       // Description Section
       if (alliance.info.description) {
           bodyHTML += '<div class="modal-section">';
           bodyHTML += '<h3 class="modal-section-title">About</h3>';
           bodyHTML += '<p class="modal-description">' + alliance.info.description + '</p>';
           bodyHTML += '</div>';
       }

       // Discord & Contact Section
       if (alliance.discord.serverName || alliance.discord.inviteUrl) {
           bodyHTML += '<div class="modal-section">';
           bodyHTML += '<h3 class="modal-section-title">Discord Server</h3>';

           if (alliance.discord.serverName) {
               bodyHTML += '<div class="modal-info-item" style="margin-bottom: 15px;">';
               bodyHTML += '<div class="modal-info-label">Server Name</div>';
               bodyHTML += '<div class="modal-info-value">' + alliance.discord.serverName + '</div>';
               bodyHTML += '</div>';
           }

           if (alliance.discord.inviteUrl) {
               bodyHTML += '<a href="' + alliance.discord.inviteUrl + '" target="_blank" class="modal-discord-button">';
               bodyHTML += '<svg width="20" height="20" viewBox="0 0 71 55" fill="none" xmlns="http://www.w3.org/2000/svg">';
               bodyHTML += '<path d="M60.1045 4.8978C55.5792 2.8214 50.7265 1.2916 45.6527 0.41542C45.5603 0.39851 45.468 0.440769 45.4204 0.525289C44.7963 1.6353 44.105 3.0834 43.6209 4.2216C38.1637 3.4046 32.7345 3.4046 27.3892 4.2216C26.905 3.0581 26.1886 1.6353 25.5617 0.525289C25.5141 0.443589 25.4218 0.40133 25.3294 0.41542C20.2584 1.2888 15.4057 2.8186 10.8776 4.8978C10.8384 4.9147 10.8048 4.9429 10.7825 4.9795C1.57795 18.7309 -0.943561 32.1443 0.293408 45.3914C0.299005 45.4562 0.335386 45.5182 0.385761 45.5576C6.45866 50.0174 12.3413 52.7249 18.1147 54.5195C18.2071 54.5477 18.305 54.5139 18.3638 54.4378C19.7295 52.5728 20.9469 50.6063 21.9907 48.5383C22.0523 48.4172 21.9935 48.2735 21.8676 48.2256C19.9366 47.4931 18.0979 46.6 16.3292 45.5858C16.1893 45.5041 16.1781 45.304 16.3068 45.2082C16.679 44.9293 17.0513 44.6391 17.4067 44.3461C17.471 44.2926 17.5606 44.2813 17.6362 44.3151C29.2558 49.6202 41.8354 49.6202 53.3179 44.3151C53.3935 44.2785 53.4831 44.2898 53.5502 44.3433C53.9057 44.6363 54.2779 44.9293 54.6529 45.2082C54.7816 45.304 54.7732 45.5041 54.6333 45.5858C52.8646 46.6197 51.0259 47.4931 49.0921 48.2228C48.9662 48.2707 48.9102 48.4172 48.9718 48.5383C50.038 50.6034 51.2554 52.5699 52.5959 54.435C52.6519 54.5139 52.7526 54.5477 52.845 54.5195C58.6464 52.7249 64.529 50.0174 70.6019 45.5576C70.6551 45.5182 70.6887 45.459 70.6943 45.3942C72.1747 30.0791 68.2147 16.7757 60.1968 4.9823C60.1772 4.9429 60.1437 4.9147 60.1045 4.8978ZM23.7259 37.3253C20.2276 37.3253 17.3451 34.1136 17.3451 30.1693C17.3451 26.225 20.1717 23.0133 23.7259 23.0133C27.308 23.0133 30.1626 26.2532 30.1066 30.1693C30.1066 34.1136 27.28 37.3253 23.7259 37.3253ZM47.3178 37.3253C43.8196 37.3253 40.9371 34.1136 40.9371 30.1693C40.9371 26.225 43.7636 23.0133 47.3178 23.0133C50.9 23.0133 53.7545 26.2532 53.6986 30.1693C53.6986 34.1136 50.9 37.3253 47.3178 37.3253Z" fill="currentColor"/>';
               bodyHTML += '</svg>';
               bodyHTML += 'Join Alliance Discord';
               bodyHTML += '</a>';
           }

           bodyHTML += '</div>';
       }

       // Recruiting Section
       bodyHTML += '<div class="modal-section">';
       bodyHTML += '<h3 class="modal-section-title">Recruiting Status</h3>';

       var recruitingStatus = alliance.info.recruiting ? 'recruiting' : 'not-recruiting';
       var recruitingText = alliance.info.recruiting ? '✓ Recruiting' : '✗ Not Recruiting';
       bodyHTML += '<div style="margin-bottom: 15px;"><span class="modal-status-badge ' + recruitingStatus + '">' + recruitingText + '</span></div>';

       if (alliance.info.recruiting && alliance.info.requirements) {
           var req = alliance.info.requirements;
           bodyHTML += '<div class="modal-requirements">';
           bodyHTML += '<h4 style="color: #ffd700; margin-bottom: 10px;">Requirements</h4>';

           if (req.minPower) {
               bodyHTML += '<p><strong>Min Power:</strong> ' + req.minPower.toLocaleString() + '</p>';
           }
           if (req.minLevel) {
               bodyHTML += '<p><strong>Min HQ Level:</strong> ' + req.minLevel + '</p>';
           }
           if (req.activity) {
               bodyHTML += '<p><strong>Activity:</strong> ' + req.activity + '</p>';
           }
           if (req.notes) {
               bodyHTML += '<p><strong>Notes:</strong> ' + req.notes + '</p>';
           }

           bodyHTML += '</div>';
       }

       if (!alliance.info.recruiting) {
           bodyHTML += '<p class="modal-description">This alliance is not currently accepting new members.</p>';
       }

       bodyHTML += '</div>';

       // Alliance Details Section
       if (alliance.info.languages || alliance.info.timezone || alliance.achievements.specialties) {
           bodyHTML += '<div class="modal-section">';
           bodyHTML += '<h3 class="modal-section-title">Alliance Details</h3>';

           if (alliance.info.timezone) {
               bodyHTML += '<div class="modal-info-item" style="margin-bottom: 15px;">';
               bodyHTML += '<div class="modal-info-label">Timezone</div>';
               bodyHTML += '<div class="modal-info-value">' + alliance.info.timezone + '</div>';
               bodyHTML += '</div>';
           }

           if (alliance.info.languages && alliance.info.languages.length > 0) {
               bodyHTML += '<div class="modal-info-item" style="margin-bottom: 15px;">';
               bodyHTML += '<div class="modal-info-label">Languages</div>';
               bodyHTML += '<div class="modal-tag-list">';
               for (var i = 0; i < alliance.info.languages.length; i++) {
                   bodyHTML += '<span class="modal-tag language">' + alliance.info.languages[i] + '</span>';
               }
               bodyHTML += '</div></div>';
           }

           if (alliance.achievements.specialties && alliance.achievements.specialties.length > 0) {
               bodyHTML += '<div class="modal-info-item">';
               bodyHTML += '<div class="modal-info-label">Specialties</div>';
               bodyHTML += '<div class="modal-tag-list">';
               for (var j = 0; j < alliance.achievements.specialties.length; j++) {
                   bodyHTML += '<span class="modal-tag specialty">' + alliance.achievements.specialties[j] + '</span>';
               }
               bodyHTML += '</div></div>';
           }

           bodyHTML += '</div>';
       }

       // Cross-Server Alliance Section
       if (alliance.crossServer.hasPartner) {
           bodyHTML += '<div class="modal-section">';
           bodyHTML += '<h3 class="modal-section-title">Cross-Server Alliance</h3>';

           if (alliance.crossServer.network) {
               bodyHTML += '<div class="modal-info-item" style="margin-bottom: 15px;">';
               bodyHTML += '<div class="modal-info-label">Network Name</div>';
               bodyHTML += '<div class="modal-info-value highlight">' + alliance.crossServer.network + '</div>';
               bodyHTML += '</div>';
           }

           if (alliance.crossServer.servers && alliance.crossServer.servers.length > 0) {
               bodyHTML += '<div class="modal-info-item" style="margin-bottom: 15px;">';
               bodyHTML += '<div class="modal-info-label">Partner Servers</div>';
               bodyHTML += '<div class="modal-server-list">';
               for (var k = 0; k < alliance.crossServer.servers.length; k++) {
                   bodyHTML += '<span class="modal-server-tag">Server ' + alliance.crossServer.servers[k] + '</span>';
               }
               bodyHTML += '</div></div>';
           }

           if (alliance.crossServer.partnerTags && alliance.crossServer.partnerTags.length > 0) {
               bodyHTML += '<div class="modal-info-item">';
               bodyHTML += '<div class="modal-info-label">Partner Alliance Tags</div>';
               bodyHTML += '<div class="modal-tag-list">';
               for (var m = 0; m < alliance.crossServer.partnerTags.length; m++) {
                   bodyHTML += '<span class="modal-tag">' + alliance.crossServer.partnerTags[m] + '</span>';
               }
               bodyHTML += '</div></div>';
           }

           bodyHTML += '</div>';
       }

       // Achievements Section
       if (alliance.achievements.peakPower || alliance.achievements.peakRank) {
           bodyHTML += '<div class="modal-section">';
           bodyHTML += '<h3 class="modal-section-title">Achievements</h3>';
           bodyHTML += '<div class="modal-info-grid">';

           if (alliance.achievements.peakRank) {
               bodyHTML += '<div class="modal-info-item">';
               bodyHTML += '<div class="modal-info-label">Peak Rank</div>';
               bodyHTML += '<div class="modal-info-value highlight">#' + alliance.achievements.peakRank + '</div>';
               bodyHTML += '</div>';
           }

           if (alliance.achievements.peakPower) {
               bodyHTML += '<div class="modal-info-item">';
               bodyHTML += '<div class="modal-info-label">Peak Power</div>';
               bodyHTML += '<div class="modal-info-value highlight">' + alliance.achievements.peakPower.toLocaleString() + '</div>';
               bodyHTML += '</div>';
           }

           bodyHTML += '</div></div>';
       }

       // R5 Leadership History Section
       if (signatureHistory && signatureHistory.alliances) {
           var allianceHistory = signatureHistory.alliances.find(function(a) { return a.tag === alliance.tag; });
           if (allianceHistory && allianceHistory.r5History && allianceHistory.r5History.length > 0) {
               bodyHTML += '<div class="modal-section">';
               bodyHTML += '<h3 class="modal-section-title">R5 Leadership History</h3>';

               // Sort R5s by startDate (most recent first)
               var sortedR5s = allianceHistory.r5History.slice().reverse();

               for (var r = 0; r < sortedR5s.length; r++) {
                   var r5 = sortedR5s[r];
                   var isCurrent = r5.current === true;

                   bodyHTML += '<div class="r5-history-entry' + (isCurrent ? ' current-r5' : '') + '">';

                   // R5 Header
                   bodyHTML += '<div class="r5-history-header">';
                   bodyHTML += '<div class="r5-history-name">' + r5.r5Name;
                   if (isCurrent) bodyHTML += ' <span class="current-badge">Current</span>';
                   bodyHTML += '</div>';

                   // Tenure dates
                   var startDateStr = new Date(r5.startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                   var tenureStr = 'Started: ' + startDateStr;
                   if (r5.endDate) {
                       var endDateStr = new Date(r5.endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                       var days = Math.floor((new Date(r5.endDate) - new Date(r5.startDate)) / (1000 * 60 * 60 * 24));
                       tenureStr = startDateStr + ' - ' + endDateStr + ' (' + days + ' days)';
                   }
                   bodyHTML += '<div class="r5-history-tenure">' + tenureStr + '</div>';
                   bodyHTML += '</div>';

                   // Signatures
                   if (r5.signatures && r5.signatures.length > 0) {
                       bodyHTML += '<div class="r5-signatures">';
                       bodyHTML += '<div class="r5-signatures-label">Signatures:</div>';

                       for (var s = 0; s < r5.signatures.length; s++) {
                           var sig = r5.signatures[s];
                           var sigDate = new Date(sig.signedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                           bodyHTML += '<div class="signature-item">';
                           bodyHTML += '<span class="signature-check">✓</span>';
                           bodyHTML += '<span class="signature-version">Version ' + sig.version + '</span>';
                           bodyHTML += '<span class="signature-date">' + sigDate + '</span>';
                           if (sig.notes) {
                               bodyHTML += '<span class="signature-notes">' + sig.notes + '</span>';
                           }
                           bodyHTML += '</div>';
                       }

                       bodyHTML += '</div>';
                   } else {
                       bodyHTML += '<div class="r5-no-signatures">⚠️ No signatures on record</div>';
                   }

                   bodyHTML += '</div>';
               }

               bodyHTML += '</div>';
           }
       }

       // Set modal body HTML
       document.getElementById('modalBody').innerHTML = bodyHTML;

       // Show modal
       document.getElementById('allianceModal').classList.add('active');
       document.body.style.overflow = 'hidden'; // Prevent background scrolling
   }

   /**
    * Close alliance detail modal
    */
   function closeAllianceModal() {
       document.getElementById('allianceModal').classList.remove('active');
       document.body.style.overflow = ''; // Restore scrolling
   }

   // Close modal when clicking outside
   document.addEventListener('click', function(e) {
       var modal = document.getElementById('allianceModal');
       if (e.target === modal) {
           closeAllianceModal();
       }
   });

   // Close modal on Escape key
   document.addEventListener('keydown', function(e) {
       if (e.key === 'Escape') {
           closeAllianceModal();
       }
   });

   /* ============================================
      POWER TRENDS CHART
      ============================================ */

   /**
    * Parse CSV data into chart-ready format
    * @param {string} csvText - Raw CSV text
    * @returns {Object} Parsed data with dates and alliance power values
    */
   function parsePowerHistoryCSV(csvText) {
       var lines = csvText.trim().split('\n');
       if (lines.length < 2) return null;

       // Parse header row (alliance tags)
       var headers = lines[0].split(',').map(function(h) { return h.trim(); });
       var dateIndex = 0;
       var allianceTags = headers.slice(1); // Skip 'date' column

       // Parse data rows
       var dates = [];
       var datasets = {};

       // Initialize datasets for each alliance
       for (var i = 0; i < allianceTags.length; i++) {
           datasets[allianceTags[i]] = [];
       }

       // Parse each data row
       for (var j = 1; j < lines.length; j++) {
           var values = lines[j].split(',').map(function(v) { return v.trim(); });
           if (values.length !== headers.length) continue;

           dates.push(values[0]);

           // Add power value for each alliance
           for (var k = 1; k < values.length; k++) {
               var tag = headers[k];
               var power = parseInt(values[k]) || 0;
               datasets[tag].push(power);
           }
       }

       return {
           dates: dates,
           datasets: datasets,
           alliances: allianceTags
       };
   }

   /**
    * Generate distinct colors for alliances
    * @param {number} count - Number of colors needed
    * @returns {Array} Array of color strings
    */
   function generateChartColors(count) {
       var colors = [
           '#FFD700', // Gold (Rank 1)
           '#C0C0C0', // Silver (Rank 2)
           '#CD7F32', // Bronze (Rank 3)
           '#4169E1', // Royal Blue
           '#DC143C', // Crimson
           '#32CD32', // Lime Green
           '#FF8C00', // Dark Orange
           '#9370DB', // Medium Purple
           '#20B2AA', // Light Sea Green
           '#FF1493', // Deep Pink
           '#FFD700', // Gold
           '#00CED1', // Dark Turquoise
           '#FF6347', // Tomato
           '#9400D3', // Dark Violet
           '#00FA9A'  // Medium Spring Green
       ];

       return colors.slice(0, count);
   }

   /**
    * Render power trends chart using Chart.js
    */
   function renderPowerChart() {
       if (!powerHistory) {
           console.log('No power history data loaded');
           return;
       }

       var ctx = document.getElementById('powerChart');
       if (!ctx) {
           console.error('Power chart canvas not found');
           return;
       }

       // Destroy existing chart if it exists
       if (powerChart) {
           powerChart.destroy();
       }

       // Only display top 15 alliances (even if CSV has more)
       var alliancesToShow = Math.min(15, powerHistory.alliances.length);
       var colors = generateChartColors(alliancesToShow);

       // Build datasets for Chart.js (top 15 only)
       var chartDatasets = [];
       for (var i = 0; i < alliancesToShow; i++) {
           var tag = powerHistory.alliances[i];
           var data = powerHistory.datasets[tag];
           var color = colors[i];

           chartDatasets.push({
               label: tag,
               data: data,
               borderColor: color,
               backgroundColor: color,
               borderWidth: 2,
               pointRadius: 4,
               pointHoverRadius: 6,
               tension: 0.2,
               fill: false
           });
       }

       // Create chart
       powerChart = new Chart(ctx, {
           type: 'line',
           data: {
               labels: powerHistory.dates,
               datasets: chartDatasets
           },
           options: {
               responsive: true,
               maintainAspectRatio: true,
               aspectRatio: 2,
               plugins: {
                   title: {
                       display: true,
                       text: 'Alliance Power Over Time',
                       color: '#FFD700',
                       font: {
                           size: 18,
                           weight: 'bold'
                       }
                   },
                   legend: {
                       display: true,
                       position: 'bottom',
                       labels: {
                           color: '#E0E0E0',
                           padding: 15,
                           font: {
                               size: 12
                           },
                           usePointStyle: true
                       }
                   },
                   tooltip: {
                       mode: 'index',
                       intersect: false,
                       backgroundColor: 'rgba(0, 0, 0, 0.8)',
                       titleColor: '#FFD700',
                       bodyColor: '#E0E0E0',
                       borderColor: '#FFD700',
                       borderWidth: 1,
                       padding: 12,
                       displayColors: true,
                       callbacks: {
                           label: function(context) {
                               var label = context.dataset.label || '';
                               if (label) {
                                   label += ': ';
                               }
                               label += context.parsed.y.toLocaleString();
                               return label;
                           }
                       }
                   }
               },
               scales: {
                   x: {
                       display: true,
                       title: {
                           display: true,
                           text: 'Date',
                           color: '#FFD700',
                           font: {
                               size: 14,
                               weight: 'bold'
                           }
                       },
                       ticks: {
                           color: '#A0A0A0',
                           maxRotation: 45,
                           minRotation: 45
                       },
                       grid: {
                           color: 'rgba(255, 255, 255, 0.1)'
                       }
                   },
                   y: {
                       display: true,
                       title: {
                           display: true,
                           text: 'Alliance Power',
                           color: '#FFD700',
                           font: {
                               size: 14,
                               weight: 'bold'
                           }
                       },
                       ticks: {
                           color: '#A0A0A0',
                           callback: function(value) {
                               return value.toLocaleString();
                           }
                       },
                       grid: {
                           color: 'rgba(255, 255, 255, 0.1)'
                       }
                   }
               },
               interaction: {
                   mode: 'nearest',
                   axis: 'x',
                   intersect: false
               }
           }
       });

       // Update chart info
       document.getElementById('lastDataUpdate').textContent = powerHistory.dates[powerHistory.dates.length - 1];
       document.getElementById('dataPointCount').textContent = powerHistory.dates.length + ' weeks';
   }

   /* ============================================
      DATA LOADING
      ============================================ */

   // Version for cache-busting (update this when deploying changes)
   var APP_VERSION = '1.4.2';

   /**
    * Load all data from JSON files with cache-busting
    * @returns {Promise<void>}
    */
   async function loadData() {
       try {
           const [alliancesData, rulesData, amendmentsData, rotationScheduleData, powerHistoryCSV, serverInfoData, signatureHistoryData] = await Promise.all([
               fetch('data/alliances.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/rules.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/amendments.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/rotation-schedule.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/power-history.csv?v=' + APP_VERSION).then(r => r.text()),
               fetch('data/server-info.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/signature-history.json?v=' + APP_VERSION).then(r => r.json())
           ]);

           alliances = alliancesData;
           serverRules = rulesData;
           amendments = amendmentsData;
           rotationSchedule = rotationScheduleData;
           serverInfo = serverInfoData;
           signatureHistory = signatureHistoryData;

           // Parse power history CSV
           powerHistory = parsePowerHistoryCSV(powerHistoryCSV);

           currentRules = JSON.parse(JSON.stringify(serverRules));

           console.log('Data loaded successfully');
           console.log('Server info:', serverInfo);
           console.log('Signature history:', signatureHistory);
           return true;
       } catch (error) {
           console.error('Error loading data:', error);
           document.body.innerHTML = '<div style="text-align:center;padding:50px;color:#ff6b6b;"><h1>Error Loading Data</h1><p>Unable to load server data. Please ensure you are running from a web server.</p><p style="color:#aaa;font-size:0.9em;">Try: <code>python -m http.server 8000</code> or use Live Server extension</p></div>';
           return false;
       }
   }

   /* ============================================
      INITIALIZATION
      ============================================ */

   /**
    * Initialize application on DOM ready
    * Loads data, then renders all sections
    */
   document.addEventListener('DOMContentLoaded', async function() {
       // Load data from JSON files
       const loaded = await loadData();
       if (!loaded) return;

       // Apply amendments to rules
       applyAmendments();

       // Render all sections
       renderServerDiscord();
       renderPodium();
       renderAllianceGrid();
       renderSignatories();
       renderRules();
       renderCouncil();
       renderAmendments();
       renderPowerChart();
       updateVersionInfo();

       console.log('Server 1586 Homepage initialized');
       console.log('Current version:', getCurrentVersion());
   });