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
    * Render top 3 alliances podium with trophies
    */
   function renderPodium() {
       var podium = document.getElementById('podium');
       var top3 = alliances.slice(0, 3);
       var trophies = ['🏆', '🥈', '🥉'];
       var classes = ['first-place', 'second-place', 'third-place'];
       
       var html = '';
       for (var i = 0; i < top3.length; i++) {
           var alliance = top3[i];
           html += '<div class="podium-place ' + classes[i] + '">';
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
           html += '<div class="alliance-card">';
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
           var statusClass = alliance.signed ? 'signed' : 'pending';
           var statusText = alliance.signed ? '✓ Signed' : '⏳ Pending';
           
           html += '<div class="signatory-card ' + statusClass + '">';
           html += '<div class="signatory-rank">' + alliance.rank + '</div>';
           html += '<div class="signatory-info">';
           html += '<div class="signatory-alliance">' + alliance.tag + '</div>';
           html += '<div class="signatory-r5">' + alliance.r5 + '</div>';
           html += '<div class="signatory-status">' + statusText + '</div>';
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
           const [alliancesData, rulesData, amendmentsData] = await Promise.all([
               fetch('data/alliances.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/rules.json?v=' + APP_VERSION).then(r => r.json()),
               fetch('data/amendments.json?v=' + APP_VERSION).then(r => r.json())
           ]);

           alliances = alliancesData;
           serverRules = rulesData;
           amendments = amendmentsData;

           // Rotation schedule is loaded via script tag (rotation-schedule.js defines rotationScheduleData)
           rotationSchedule = typeof rotationScheduleData !== 'undefined' ? rotationScheduleData : {};

           currentRules = JSON.parse(JSON.stringify(serverRules));

           console.log('Data loaded successfully');
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
       renderPodium();
       renderAllianceGrid();
       renderSignatories();
       renderRules();
       renderCouncil();
       renderAmendments();
       updateVersionInfo();

       console.log('Server 1586 Homepage initialized');
       console.log('Current version:', getCurrentVersion());
   });