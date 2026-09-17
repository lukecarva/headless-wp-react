# 5. The project CPT redirects to the frontend instead of being rendered

- Status: Accepted
- Date: 2026-09-16

## Context

By default a public custom post type is served by the active WordPress theme at
its own permalink (for example `/projects/my-project`). In a headless setup the
Next.js frontend already renders that page, so serving it from WordPress too
produces **two live URLs for the same content**: duplicate content, split SEO
signals, and a themeless or broken page on the WordPress domain.

A first attempt made the post type non-public (`public => false`,
`publicly_queryable => false`). That removed the theme page but also made
WPGraphQL and REST treat projects as private, so **unauthenticated public
queries returned nothing** and the decoupled frontend could not read them.

## Decision

Keep the post type **public** so the APIs expose it, and prevent the duplicate
page with a redirect instead:

- `public => true`, `publicly_queryable => true`, `show_in_rest => true`,
  `show_in_graphql => true`, so unauthenticated REST and WPGraphQL reads work.
- `has_archive => false`, `exclude_from_search => true`, so there is no archive
  page and projects stay out of the WordPress site search.
- A `template_redirect` handler 301-redirects any front-end project request to
  `{frontend}/projects/{slug}`, and `post_type_link` rewrites permalinks to the
  same place. The frontend host is added to the `allowed_redirect_hosts`
  allowlist so `wp_safe_redirect()` permits it. The frontend base URL comes from
  the `HWR_FRONTEND_URL` constant or the `hwr_frontend_url` filter.

## Consequences

- One canonical URL per project, on the frontend. WordPress URLs 301-redirect
  there, so there is no duplicate content.
- REST and WPGraphQL expose published projects to the public, which the headless
  frontend depends on.
- The frontend URL should be configured in non-local environments; it defaults
  to the WordPress home URL otherwise.
