/**
 * Default ISR / fetch revalidation interval, in seconds. lib/env.ts uses it as
 * the fetch-level default. The route segments (`export const revalidate`) must
 * hardcode the same literal (60), since Next requires a statically analyzable
 * value there, so keep them in sync.
 *
 * The REVALIDATE_SECONDS env var overrides only the fetch value; Next applies
 * the minimum of the segment literal and the fetch value.
 */
export const REVALIDATE_SECONDS = 60;
