// Shared configuration defaults, in plain JS so they can be imported by both
// the TypeScript app (lib/env.ts, codegen.ts) and the ESM next.config.mjs
// (which cannot import a .ts module).
//
// These target local development only. Real deployments must set the matching
// NEXT_PUBLIC_* environment variables (see .env.example).

/** Default WordPress base URL for local development. */
export const DEFAULT_WORDPRESS_URL = 'http://localhost:8888';

/** Default WPGraphQL endpoint for local development. */
export const DEFAULT_GRAPHQL_ENDPOINT = `${DEFAULT_WORDPRESS_URL}/graphql`;

/** Default public URL of the frontend itself, for canonical/OG/sitemap URLs. */
export const DEFAULT_SITE_URL = 'http://localhost:3000';
