import { useState, useEffect } from 'react';

/**
 * Custom hook for fetching data from API endpoints
 *
 * SECURITY NOTE: All data is fetched from /api/ endpoints which:
 * - Strip PII (Discord IDs, email addresses, etc.)
 * - Provide only public-safe information
 * - Block direct access to /data/ directory
 */
export function useApi<T>(endpoint: string) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);

        // Convert endpoint to API path
        // Examples:
        //   alliances.json -> /api/alliances.php
        //   version.json -> /api/version.php
        //   power-history.csv -> /api/power-history.php
        const apiPath = `/api/${endpoint.replace(/\.(json|csv)$/, '.php')}`;

        const response = await fetch(apiPath);
        if (!response.ok) {
          throw new Error(`Failed to fetch ${endpoint}: ${response.status} ${response.statusText}`);
        }

        // Check content type to determine parsing method
        const contentType = response.headers.get('content-type');

        if (contentType?.includes('text/csv')) {
          // For CSV responses, return raw text
          const text = await response.text();
          setData(text as unknown as T);
        } else {
          // For JSON responses, parse and extract data field
          const json = await response.json();

          // API responses are wrapped in {success, timestamp, data}
          // Extract the data field
          if (json.success && json.data !== undefined) {
            setData(json.data);
          } else {
            setData(json);
          }
        }
      } catch (err) {
        setError(err instanceof Error ? err : new Error('Unknown error'));
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [endpoint]);

  return { data, loading, error };
}
