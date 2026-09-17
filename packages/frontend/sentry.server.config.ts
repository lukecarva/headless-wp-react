import * as Sentry from '@sentry/nextjs';

// Inert without SENTRY_DSN, so the app runs unchanged when observability is not
// configured. Set SENTRY_DSN to enable server-side error and performance capture.
if (process.env.SENTRY_DSN) {
  Sentry.init({
    dsn: process.env.SENTRY_DSN,
    tracesSampleRate: 0.1,
    sendDefaultPii: false,
  });
}
