import * as Sentry from '@sentry/nextjs';

// Inert without SENTRY_DSN. Mirrors the server config for the edge runtime
// (middleware and edge route handlers), enabling capture only when configured.
if (process.env.SENTRY_DSN) {
  Sentry.init({
    dsn: process.env.SENTRY_DSN,
    tracesSampleRate: 0.1,
    sendDefaultPii: false,
  });
}
