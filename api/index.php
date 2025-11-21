<?php
/**
 * Public API Documentation Index
 *
 * Interactive API documentation and testing interface
 *
 * @version 1.1.0
 * @date 2025-11-20
 *
 * Changelog:
 *   1.1.0 - Added missing public endpoints, documentation links
 *   1.0.0 - Initial version
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server 1586 Public API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        .subtitle { color: #7f8c8d; margin-bottom: 30px; }
        h2 { color: #34495e; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .endpoint {
            background: #ecf0f1;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .endpoint-header { font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .method {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        .path {
            font-family: 'Courier New', monospace;
            background: #34495e;
            color: #ecf0f1;
            padding: 4px 8px;
            border-radius: 3px;
        }
        .description { margin: 10px 0; color: #555; }
        .cache-info {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 8px;
        }
        .test-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .test-button:hover { background: #2980b9; }
        .response {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            display: none;
            max-height: 400px;
            overflow-y: auto;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .feature-title { font-weight: bold; color: #2c3e50; margin-bottom: 8px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Server 1586 Public API</h1>
        <p class="subtitle">Read-only REST API for Last War Server 1586 data</p>

        <h2>Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-title">✅ CORS Enabled</div>
                <p>Access from any domain</p>
            </div>
            <div class="feature-card">
                <div class="feature-title">⚡ Cached Responses</div>
                <p>Optimized with HTTP caching</p>
            </div>
            <div class="feature-card">
                <div class="feature-title">🔒 ETag Support</div>
                <p>Efficient conditional requests</p>
            </div>
            <div class="feature-card">
                <div class="feature-title">📦 JSON Format</div>
                <p>Standard REST responses</p>
            </div>
        </div>

        <h2>Endpoints</h2>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/alliances.php</span>
            </div>
            <div class="description">Returns current top 15 alliance rankings with power, R5, and signature status. Ranks calculated dynamically.</div>
            <div class="cache-info">⏱️ Cache: 60 seconds</div>
            <button class="test-button" onclick="testEndpoint('alliances.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/rules.php</span>
            </div>
            <div class="description">Returns server rules and NAP15 agreements.</div>
            <div class="cache-info">⏱️ Cache: 300 seconds (5 minutes)</div>
            <button class="test-button" onclick="testEndpoint('rules.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/amendments.php</span>
            </div>
            <div class="description">Returns history of rule changes and amendments.</div>
            <div class="cache-info">⏱️ Cache: 300 seconds (5 minutes)</div>
            <button class="test-button" onclick="testEndpoint('amendments.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/council.php</span>
            </div>
            <div class="description">Returns current week's voting council members (permanent + rotating).</div>
            <div class="cache-info">⏱️ Cache: 60 seconds</div>
            <button class="test-button" onclick="testEndpoint('council.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/council/schedule.php</span>
            </div>
            <div class="description">Returns council rotation schedule. Optional query param: <code>weeks=N</code> (default: 5, max: 52)</div>
            <div class="cache-info">⏱️ Cache: 300 seconds (5 minutes)</div>
            <button class="test-button" onclick="testEndpoint('council/schedule.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/version.php</span>
            </div>
            <div class="description">Returns current version, release date, and component versions.</div>
            <div class="cache-info">⏱️ Cache: 300 seconds (5 minutes)</div>
            <button class="test-button" onclick="testEndpoint('version.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/server-info.php</span>
            </div>
            <div class="description">Returns server metadata, Discord info, and NAP15 details.</div>
            <div class="cache-info">⏱️ Cache: 3600 seconds (1 hour)</div>
            <button class="test-button" onclick="testEndpoint('server-info.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/power-history.php</span>
            </div>
            <div class="description">Returns historical power data in CSV format for alliance trend analysis.</div>
            <div class="cache-info">⏱️ Cache: 300 seconds (5 minutes) | Format: CSV</div>
            <button class="test-button" onclick="testEndpoint('power-history.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/signature-history.php</span>
            </div>
            <div class="description">Returns R5 signature change history for server rules tracking.</div>
            <div class="cache-info">⏱️ Cache: 60 seconds</div>
            <button class="test-button" onclick="testEndpoint('signature-history.php', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method">GET</span>
                <span class="path">/api/profile_api.php?action=search&alliance=UvvU&name=PlayerName</span>
            </div>
            <div class="description">Search for user profile by alliance tag and in-game name (public self-service).</div>
            <div class="cache-info">⚡ No caching (real-time lookup)</div>
            <button class="test-button" onclick="testEndpoint('profile_api.php?action=search&alliance=UvvU&name=TestPlayer', this)">Test Endpoint</button>
            <div class="response"></div>
        </div>

        <h2>Additional Public Endpoints</h2>
        <p style="margin: 20px 0; color: #555;">
            The API also includes endpoints for self-service profile management:
        </p>
        <ul style="margin-left: 40px; color: #555;">
            <li><code>POST /api/profile_api.php</code> - Create or update user profiles</li>
            <li><code>POST /api/alliance_r5_profile_api.php</code> - R5 Discord ID updates</li>
            <li><code>POST /api/alliance_r4_profile_api.php</code> - R4 Discord ID updates</li>
        </ul>

        <h2>Complete Documentation</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-title">📖 Public API Documentation</div>
                <p>Complete REST API reference for all public endpoints</p>
                <a href="../docs/PUBLIC_API.md" style="color: #3498db; text-decoration: none; font-weight: bold;">View PUBLIC_API.md →</a>
            </div>
            <div class="feature-card">
                <div class="feature-title">🔐 Admin API Documentation</div>
                <p>Authenticated endpoints with JWT & CSRF protection</p>
                <a href="../docs/ADMIN_API.md" style="color: #3498db; text-decoration: none; font-weight: bold;">View ADMIN_API.md →</a>
            </div>
        </div>

        <h2>Response Format</h2>
        <div class="endpoint">
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto;">
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": { ... }
}</pre>
        </div>

        <h2>Usage Example</h2>
        <div class="endpoint">
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto;">
// JavaScript/Fetch
fetch('/api/alliances.php')
  .then(response => response.json())
  .then(data => console.log(data.data));

// cURL
curl https://www.example.com/api/alliances.php</pre>
        </div>

        <h2>Architecture</h2>
        <p>This API implements control/data plane separation:</p>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li><strong>Control Plane:</strong> Admin panel (authenticated) writes to data/*.json files</li>
            <li><strong>Data Plane:</strong> Public API (unauthenticated) reads from data/*.json files</li>
            <li><strong>Storage:</strong> JSON files with file locking for consistency</li>
            <li><strong>Caching:</strong> HTTP caching headers + ETag support</li>
        </ul>
    </div>

    <script>
        async function testEndpoint(path, button) {
            const responseDiv = button.nextElementSibling;
            responseDiv.style.display = 'block';
            responseDiv.textContent = 'Loading...';

            try {
                const response = await fetch(path);
                const data = await response.json();
                responseDiv.textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>
