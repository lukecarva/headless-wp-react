/**
 * Centralized access to environment configuration.
 *
 * The public endpoints default to localhost for local development. Real
 * deployments must set NEXT_PUBLIC_WORDPRESS_URL / NEXT_PUBLIC_GRAPHQL_ENDPOINT.
 * When neither is set we warn (rather than throw, so the WordPress-less CI build
 * still succeeds) instead of silently pointing production at localhost.
 */

import { REVALIDATE_SECONDS } from '@/lib/config';
import { DEFAULT_SITE_URL, DEFAULT_WORDPRESS_URL } from '@/lib/defaults';

/**
 * Parse a positive-integer env var, falling back when it is missing, empty, or
 * not a positive integer (so a blank `REVALIDATE_SECONDS=` cannot silently turn
 * the interval into 0 and disable ISR, and a fractional value is rejected).
 */
function positiveIntEnv(value: string | undefined, fallback: number): number {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

const wordpressUrl = (process.env.NEXT_PUBLIC_WORDPRESS_URL ?? DEFAULT_WORDPRESS_URL).replace(
  /\/$/,
  '',
);
const graphqlEndpoint = process.env.NEXT_PUBLIC_GRAPHQL_ENDPOINT ?? `${wordpressUrl}/graphql`;
const siteUrl = (process.env.NEXT_PUBLIC_SITE_URL ?? DEFAULT_SITE_URL).replace(/\/$/, '');

if (!process.env.NEXT_PUBLIC_WORDPRESS_URL && !process.env.NEXT_PUBLIC_GRAPHQL_ENDPOINT) {
  console.warn(
    `[env] NEXT_PUBLIC_WORDPRESS_URL / NEXT_PUBLIC_GRAPHQL_ENDPOINT are not set; using ${graphqlEndpoint}. Set them for any non-local deployment.`,
  );
}

if (!process.env.NEXT_PUBLIC_SITE_URL) {
  console.warn(
    `[env] NEXT_PUBLIC_SITE_URL is not set; canonical, Open Graph and sitemap URLs use ${siteUrl}. Set it for any non-local deployment.`,
  );
}

export const env = {
  wordpressUrl,
  graphqlEndpoint,
  /** Public URL of the frontend, used for canonical, Open Graph and sitemap URLs. */
  siteUrl,
  /** Seconds between static regenerations (ISR); defaults to REVALIDATE_SECONDS. */
  revalidateSeconds: positiveIntEnv(process.env.REVALIDATE_SECONDS, REVALIDATE_SECONDS),
  /**
   * Shared secret for the on-demand revalidation webhook. When unset, the
   * webhook is disabled (returns 401), so a missing value fails closed.
   */
  revalidateSecret: process.env.REVALIDATE_SECRET,
  /**
   * Shared secret used to verify preview tokens from WordPress. Must match the
   * plugin's HWR_PREVIEW_SECRET. When unset, the draft entry point returns 401.
   */
  previewSecret: process.env.WP_PREVIEW_SECRET,
  /**
   * WordPress Application Password credentials for authenticated draft reads.
   * Server-side only (never NEXT_PUBLIC), so they are not exposed to the client.
   */
  wpAppUser: process.env.WP_APP_USER,
  wpAppPassword: process.env.WP_APP_PASSWORD,
} as const;
