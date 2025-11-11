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

        // In development, fetch from public/data directory
        // In production, use API endpoints
        const isDevelopment = import.meta.env.DEV;

        let fetchPath: string;
        if (isDevelopment) {
          // Development: fetch directly from public/data
          fetchPath = `/data/${endpoint}`;
        } else {
          // Production: use API endpoints
          // Examples:
          //   alliances.json -> /api/alliances.php
          //   version.json -> /api/version.php
          //   power-history.csv -> /api/power-history.php
          fetchPath = `/api/${endpoint.replace(/\.(json|csv)$/, '.php')}`;
        }

        const response = await fetch(fetchPath);
        if (!response.ok) {
          throw new Error(`Failed to fetch ${endpoint}: ${response.status} ${response.statusText}`);
        }

        // Check content type to determine parsing method
        const contentType = response.headers.get('content-type');

        if (contentType?.includes('text/csv') || endpoint.endsWith('.csv')) {
          // For CSV responses, return raw text
          const text = await response.text();
          setData(text as unknown as T);
        } else {
          // For JSON responses, parse and extract data field
          const json = await response.json();

          // In production, API responses are wrapped in {success, timestamp, data}
          // In development, we get raw JSON from files
          if (!isDevelopment && json.success && json.data !== undefined) {
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
