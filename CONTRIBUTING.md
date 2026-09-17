# Contributing

## Prerequisites

- Node 20+ and pnpm 9+
- PHP 8.1+ and Composer
- Docker (for `wp-env`)

## Setup

```bash
pnpm install
pnpm --filter @hwr/plugin composer:install
pnpm env:start
pnpm codegen
pnpm dev
```

## Before opening a PR

Run the same checks CI runs:

```bash
# JS / TS
pnpm format:check
pnpm lint
pnpm typecheck
pnpm test
pnpm build

# PHP (from packages/plugin)
composer lint
composer analyse
composer test
```

## Conventions

- Commits follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `docs:`, `chore:` …).
- Architectural decisions are recorded as ADRs in `docs/decisions`. Add one when a decision changes.
- The content model lives in one place (`ProjectMeta`); add fields there, not per API surface.
