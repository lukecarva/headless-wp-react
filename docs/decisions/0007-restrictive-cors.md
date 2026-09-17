# 7. Restrictive CORS for the REST API

- Status: Accepted
- Date: 2026-09-16

## Context

WordPress core's `rest_send_cors_headers()` reflects any request `Origin` back
in `Access-Control-Allow-Origin` and always sends
`Access-Control-Allow-Credentials: true`. That makes the REST API callable,
with credentials, from any website's frontend.

In this headless setup the Next.js frontend fetches server-side (build/SSR/ISR),
so those requests carry no browser `Origin` and are never subject to CORS. CORS
only governs browser cross-origin calls, which we want to restrict.

## Decision

`Hwr\Portfolio\Cors` removes core's handler and sends CORS headers only for an
allowlisted origin:

- `Access-Control-Allow-Origin` is set **only** when the request `Origin`
  matches the allowlist (the site itself and the configured `HWR_FRONTEND_URL`,
  extendable via the `hwr_allowed_cors_origins` filter). It is never reflected
  blindly.
- `Access-Control-Allow-Credentials` is **not** sent unless explicitly enabled
  via `hwr_cors_allow_credentials` (default off). The public read API needs no
  credentials, and writes are same-origin (wp-admin) or server-to-server.
- Methods/headers are limited to what the API uses (`GET, POST, OPTIONS`).

WPGraphQL does not enable public CORS by default; leave it that way (the
frontend reads it server-side).

## Consequences

- The API cannot be called cross-origin from an arbitrary external frontend in a
  browser; only allowlisted origins get the CORS grant.
- Same-origin wp-admin/block-editor requests are unaffected (same origin needs
  no CORS).
- Adding a trusted browser origin is a one-line filter, not a code change.
