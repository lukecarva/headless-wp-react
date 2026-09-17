import * as Sentry from '@sentry/nextjs';

/**
 * Next.js instrumentation hook. Loads the Sentry runtime config for the active
 * runtime and forwards request errors to Sentry. Everything stays inert until
 * SENTRY_DSN is set (see the config files), so the app runs unchanged without a
 * Sentry account.
 */
export async function register(): Promise<void> {
  if (process.env.NEXT_RUNTIME === 'nodejs') {
    await import('./sentry.server.config');
  }
  if (process.env.NEXT_RUNTIME === 'edge') {
    await import('./sentry.edge.config');
  }
}

export const onRequestError = Sentry.captureRequestError;
