/**
 * Centralized, validated access to environment configuration.
 *
 * Fails fast with a clear message rather than letting `undefined` propagate
 * into fetch calls.
 */

import { REVALIDATE_SECONDS } from '@/lib/config';
import { DEFAULT_WORDPRESS_URL } from '@/lib/defaults';

function required(name: string, value: string | undefined): string {
  if (!value) {
    throw new Error(
      `Missing environment variable: ${name}. Copy .env.example to .env.local and set it.`,
    );
  }
  return value;
}

const wordpressUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL ?? DEFAULT_WORDPRESS_URL;

export const env = {
  wordpressUrl,
  graphqlEndpoint: required(
    'NEXT_PUBLIC_GRAPHQL_ENDPOINT',
    process.env.NEXT_PUBLIC_GRAPHQL_ENDPOINT ?? `${wordpressUrl}/graphql`,
  ),
  /** Seconds between static regenerations (ISR); defaults to REVALIDATE_SECONDS. */
  revalidateSeconds: Number(process.env.REVALIDATE_SECONDS ?? String(REVALIDATE_SECONDS)),
  /**
   * Shared secret for the on-demand revalidation webhook. When unset, the
   * webhook is disabled (returns 401), so a missing value fails closed.
   */
  revalidateSecret: process.env.REVALIDATE_SECRET,
} as const;
