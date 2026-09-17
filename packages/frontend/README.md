# @hwr/frontend (Next.js frontend)

The presentation half of the monorepo. A Next.js (App Router) app that reads projects from WPGraphQL and renders them statically with ISR.

## Highlights

- **App Router**, React Server Components, static generation with incremental revalidation.
- **Typed GraphQL**: queries in `lib/graphql`, a thin typed client, and `graphql-codegen` wired for schema-derived types.
- **Data-access layer** (`lib/projects.ts`) keeps routes free of query wiring and easy to test.
- **Draft preview**: `app/api/draft` verifies a signed token, enables Next.js draft mode, and renders the draft from an authenticated read.
- **SEO**: `sitemap.ts`, `robots.ts`, Open Graph, and `metadataBase` for absolute canonical URLs.
- **On-demand revalidation** webhook (`app/api/revalidate`), rate limited per IP; optional Sentry error monitoring.
- **Tests**: Vitest and Testing Library for components, Playwright for end to end.

## Layout

```
frontend/
├── app/
│   ├── layout.tsx                 # shell, metadata, Open Graph
│   ├── page.tsx                   # project grid (ISR)
│   ├── projects/[slug]/page.tsx   # detail page (SSG), draft-aware
│   ├── api/revalidate/route.ts    # on-demand revalidation webhook
│   ├── api/draft/                 # draft-mode entry and exit
│   ├── sitemap.ts, robots.ts      # SEO
│   └── error.tsx, loading.tsx     # error and loading states
├── components/                    # ProjectCard, StackList, PreviewBanner (+ CSS modules)
├── lib/
│   ├── env.ts                     # validated env access
│   ├── projects.ts                # data-access layer
│   ├── preview.ts, preview-token.ts   # authenticated draft read + token
│   ├── rate-limit.ts              # per-IP webhook rate limiter
│   └── graphql/                   # client, queries, types (+ generated)
├── instrumentation.ts             # Sentry (opt-in)
└── tests/                         # Vitest units + Playwright e2e
```

## Develop

```bash
cp .env.example .env.local        # point at your WordPress
pnpm --filter @hwr/frontend dev   # http://localhost:3000
```

Configuration is read from the environment in one place (`lib/env.ts`); the
local `http://localhost:8888` defaults live in `lib/defaults.js`. **Real
deployments must set `NEXT_PUBLIC_WORDPRESS_URL` and
`NEXT_PUBLIC_GRAPHQL_ENDPOINT`**. The defaults are a local convenience only.

With WordPress running, regenerate typed GraphQL (writes `lib/graphql/generated.ts` and a `schema.graphql` snapshot):

```bash
pnpm --filter @hwr/frontend codegen
```

CI runs the same command against a live WordPress in the **`graphql-contract`** job: it validates every query/fragment against the real WPGraphQL schema and fails if any drifts, so the typed contract cannot go stale.

## Test

```bash
pnpm --filter @hwr/frontend test        # Vitest
pnpm --filter @hwr/frontend test:e2e    # Playwright (needs WP + a build)
```

## Data flow

Routes call `lib/projects.ts`, which calls `gqlRequest` (the typed client), which queries WPGraphQL. Responses are cached by Next's Data Cache and revalidated every `REVALIDATE_SECONDS`. See [../../docs/ARCHITECTURE.md](../../docs/ARCHITECTURE.md).
