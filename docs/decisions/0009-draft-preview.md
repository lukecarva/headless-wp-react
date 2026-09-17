# 9. Draft preview via signed links and an authenticated read

- Status: Accepted
- Date: 2026-09-17

## Context

Editors need to preview an unpublished project on the real React frontend before publishing (ADR 0001 records that this used to be an accepted gap). The presentation layer is a separate Next.js app that reads only published content over anonymous WPGraphQL, so a preview needs three things: a trusted way to open the draft, a way to fetch unpublished content, and no exposure of drafts to the public.

## Decision

- **WordPress signs the preview link.** The plugin filters `preview_post_link` for projects to `{frontend}/api/draft` with the post id, slug and a short expiry, signed with HMAC-SHA256 over `{id}.{slug}.{exp}` using `HWR_PREVIEW_SECRET`.
- **The frontend verifies and enables draft mode.** `/api/draft` verifies the token in constant time, rejects an expired or forged token (401), enables Next.js draft mode, and stores the id and slug in an httpOnly cookie signed with the same secret. The exit route clears both cookies.
- **The draft is read authenticated, over REST, not GraphQL.** While draft mode is on, the project page reads the draft by database id from a capability-guarded plugin route, `GET /hwr/v1/projects/{id}/preview`, authenticated with a WordPress Application Password (`WP_APP_USER` / `WP_APP_PASSWORD`) held server-side.

## Consequences

**Positive**

- Preview works end to end against the real templates; published pages stay statically generated and only a draft session renders dynamically.
- The read is authorized twice: the signed token gates entry, and the REST route checks `edit_post` for the project. The draft id is bound to the slug in the signed cookie, so a valid session cannot enumerate other projects' drafts.
- It stays inert until the secret and Application Password are configured, and then falls back to WordPress's own preview.

**Trade-offs**

- WPGraphQL was not used for the authenticated read. It resets the current user during HTTP request processing, so Basic-auth Application Passwords do not authenticate GraphQL requests reliably; REST authenticates them natively, so the preview read uses the existing `hwr/v1` namespace while the public reads stay on GraphQL.
- In production, Next.js draft mode uses `Secure` cookies, so a production frontend must be served over HTTPS for a browser to carry them; local development over HTTP works.
