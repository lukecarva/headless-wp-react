# 1. Headless (decoupled) architecture

- Status: Accepted
- Date: 2026-09-16

## Context

The client edits content in WordPress but wants a modern, fast, React-based frontend with full control over markup, routing, and performance.

## Decision

Decouple the frontend from WordPress. WordPress serves only wp-admin and an API; a Next.js app renders the public site and consumes that API.

## Consequences

**Positive**

- Frontend uses the full React/Next ecosystem without theme constraints.
- Public site is static/CDN-served → fast, and resilient to WP load spikes.
- Clear separation of concerns; the two sides can be worked on and deployed independently.

**Negative / trade-offs**

- Two deployables and two runtimes to operate.
- Features that "come for free" in a coupled theme (preview, some plugins that print markup) need explicit wiring. Preview is handled via draft-aware queries and Next draft mode.
- SEO/OG metadata must be reproduced on the frontend rather than inherited from a theme.

We accept these because the client's priority is a bespoke, high-performance React frontend, which a coupled theme cannot deliver as cleanly.
