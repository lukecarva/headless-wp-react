# 10. Redirect the WordPress front-end to the React app

- Status: Accepted
- Date: 2026-09-17

## Context

In a headless setup the WordPress theme is never the presentation layer, but by default WordPress still renders a front-end (the site root, pages, archives, single posts) with the active theme. A visitor who reaches a WordPress URL directly would see the default theme instead of the React app, which is confusing and duplicates content for SEO.

ADR 0005 already redirects a single `project` view to the frontend. This generalizes that to the whole front-end.

## Decision

`FrontendRedirect` hooks `template_redirect` and sends every WordPress front-end request to the Next.js app (`HWR_FRONTEND_URL`) with a 302, except:

- single `project` views, which keep their dedicated mapping to `/projects/{slug}` in `ProjectPostType`;
- editor previews (`is_preview()`), so draft preview and WordPress's own preview still work;
- feeds and `robots.txt`, plus a defensive guard for the GraphQL endpoint.

Admin, REST and GraphQL requests never reach `template_redirect`, so they are unaffected. It is inert until a distinct `HWR_FRONTEND_URL` is configured, so it will not redirect the site to itself.

## Consequences

**Positive**

- The WordPress theme is never shown; there is one canonical presentation layer, so no duplicate content.
- A 302 is used because the frontend URL comes from configuration and can change, so clients must not cache the mapping permanently.

**Trade-offs**

- The frontend renders only the home page and project pages, so every other WordPress front-end URL maps to the frontend root rather than a matching page. That is acceptable for this reference; a larger site would map more paths.
