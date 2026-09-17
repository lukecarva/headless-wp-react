# 1. Headless (decoupled) architecture

- Status: Accepted
- Date: 2026-09-16

## Context

The client edits content in WordPress but wants a modern, fast, React-based frontend with full control over markup, routing, and performance.

## Decision

Decouple the frontend from WordPress. WordPress serves only wp-admin and an API (WPGraphQL plus a small REST namespace); a Next.js app renders the public site and consumes that API. WordPress's own front-end is redirected to the Next app, so the theme is never the presentation layer.

## Consequences

**Positive**

- The frontend uses the full React/Next ecosystem without theme constraints.
- The public site is static/CDN-served, so it is fast and resilient to WordPress load spikes.
- Clear separation of concerns; the two sides can be worked on and deployed independently.

**Negative / trade-offs**

- Two deployables and two runtimes to operate.
- Features that come for free in a coupled theme need explicit wiring, for example SEO and OG metadata are reproduced on the frontend rather than inherited from a theme.

## Draft preview

Draft preview works end to end against the React frontend, so an editor sees unpublished changes on the real site, not through the WordPress theme:

- WordPress rewrites a project's "Preview" link (`preview_post_link`) to the frontend's `/api/draft` entry point, carrying the post id, slug and expiry signed with an HMAC preview secret.
- The frontend verifies the token in constant time, enables Next.js draft mode, and stores the project id in an httpOnly cookie.
- While draft mode is on, the project page reads the draft by database id from a capability-guarded plugin REST route (`hwr/v1/projects/{id}/preview`, which checks `edit_post`), authenticated with a WordPress Application Password held server-side, because the public reads expose only published content. Published pages stay statically generated; only a draft session renders dynamically.

It stays inert until the preview secret and Application Password are configured; previews then fall back to WordPress's own rendering, which the redirect already allows by exempting `is_preview()`.

We accept these trade-offs because the client's priority is a bespoke, high-performance React frontend, which a coupled theme cannot deliver as cleanly.
