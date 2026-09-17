/**
 * Build-time constants shared across the app.
 *
 * REVALIDATE_SECONDS is the single source of truth for the ISR interval. It
 * must be a literal so Next can statically analyze the `export const revalidate`
 * in each route segment. The GraphQL client uses it as the default fetch
 * revalidation, overridable at runtime via the REVALIDATE_SECONDS env var (see
 * lib/env.ts).
 */
export const REVALIDATE_SECONDS = 60;
