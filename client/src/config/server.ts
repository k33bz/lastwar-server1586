/**
 * Server Configuration
 *
 * Multi-server support (v3.8.0+)
 * Each deployment can specify which Last War server it represents
 *
 * Environment Variables:
 * - VITE_SERVER_ID: Server identifier (e.g., '1586', '9999')
 * - VITE_SERVER_NAME: Human-readable server name (e.g., 'Server 1586')
 * - VITE_API_BASE_URL: Base URL for API endpoints (optional, defaults to relative paths)
 *
 * Usage:
 * ```typescript
 * import { SERVER_CONFIG } from '@/config/server';
 *
 * const response = await fetch(`/api/alliances.php?server=${SERVER_CONFIG.id}`);
 * ```
 */

export interface ServerConfig {
  /** Server identifier (e.g., '1586', '9999') */
  id: string;
  /** Human-readable server name for display */
  name: string;
  /** Base URL for API calls (optional, defaults to relative paths) */
  apiBaseUrl: string;
}

/**
 * Current server configuration
 *
 * Reads from Vite environment variables at build time
 * Defaults to Server 1586 for backwards compatibility
 */
export const SERVER_CONFIG: ServerConfig = {
  id: import.meta.env.VITE_SERVER_ID || '1586',
  name: import.meta.env.VITE_SERVER_NAME || 'Server 1586',
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL || '',
};

/**
 * Build API URL with server parameter
 *
 * Automatically appends server ID to API endpoints
 * Handles both relative and absolute URLs
 *
 * @param endpoint API endpoint path (e.g., '/api/alliances.php')
 * @param params Optional additional query parameters
 * @returns Full URL with server parameter
 *
 * @example
 * ```typescript
 * const url = buildApiUrl('/api/alliances.php');
 * // Result: '/api/alliances.php?server=1586'
 *
 * const url2 = buildApiUrl('/api/council.php', { weeks: '10' });
 * // Result: '/api/council.php?server=1586&weeks=10'
 * ```
 */
export function buildApiUrl(
  endpoint: string,
  params?: Record<string, string>
): string {
  // Start with base URL + endpoint
  const base = SERVER_CONFIG.apiBaseUrl + endpoint;

  // Build query string with server parameter
  const queryParams = new URLSearchParams({
    server: SERVER_CONFIG.id,
    ...params,
  });

  // Combine base and query string
  return `${base}?${queryParams.toString()}`;
}

/**
 * Check if current deployment is for the default server (1586)
 */
export function isDefaultServer(): boolean {
  return SERVER_CONFIG.id === '1586';
}

/**
 * Get server display name with ID
 * @returns Formatted string like "Server 1586" or "Server 9999"
 */
export function getServerDisplayName(): string {
  return SERVER_CONFIG.name;
}
