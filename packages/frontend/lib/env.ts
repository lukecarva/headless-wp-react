/**
 * Centralized access to environment configuration.
 *
 * The public endpoints default to localhost for local development. Real
 * deployments must set NEXT_PUBLIC_WORDPRESS_URL / NEXT_PUBLIC_GRAPHQL_ENDPOINT.
 * When neither is set we warn (rather than throw, so the WordPress-less CI build
 * still succeeds) instead of silently pointing production at localhost.
 */

import { REVALIDATE_SECONDS } from '@/lib/config';
import { DEFAULT_WORDPRESS_URL } from '@/lib/defaults';

/**
 * Parse a positive-integer env var, falling back when it is missing, empty, or
 * not a finite positive number (so a blank `REVALIDATE_SECONDS=` cannot silently
 * turn the interval into 0 and disable ISR).
 */
function positiveIntEnv(value: string | undefined, fallback: number): number {
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

const wordpressUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL ?? DEFAULT_WORDPRESS_URL;
const graphqlEndpoint = process.env.NEXT_PUBLIC_GRAPHQL_ENDPOINT ?? `${wordpressUrl}/graphql`;

if (!process.env.NEXT_PUBLIC_WORDPRESS_URL && !process.env.NEXT_PUBLIC_GRAPHQL_ENDPOINT) {
  console.warn(
    `[env] NEXT_PUBLIC_WORDPRESS_URL / NEXT_PUBLIC_GRAPHQL_ENDPOINT are not set; using ${graphqlEndpoint}. Set them for any non-local deployment.`,
  );
}

export const env = {
  wordpressUrl,
  graphqlEndpoint,
  /** Seconds between static regenerations (ISR); defaults to REVALIDATE_SECONDS. */
  revalidateSeconds: positiveIntEnv(process.env.REVALIDATE_SECONDS, REVALIDATE_SECONDS),
  /**
   * Shared secret for the on-demand revalidation webhook. When unset, the
   * webhook is disabled (returns 401), so a missing value fails closed.
   */
  revalidateSecret: process.env.REVALIDATE_SECRET,
} as const;
