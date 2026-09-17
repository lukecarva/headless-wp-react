# 2. WPGraphQL as the primary read API, REST as a secondary surface

- Status: Accepted
- Date: 2026-09-16

## Context

WordPress ships a REST API; WPGraphQL adds a GraphQL layer. The frontend needs a small, precise slice of data per route and strong typing.

## Decision

Use **WPGraphQL** as the frontend's primary read API and keep a small **custom REST namespace** (`hwr/v1`) for external/webhook consumers.

## Rationale

- GraphQL lets the frontend request exactly the fields it needs, avoiding over-fetching across `_embed` chains.
- With `graphql-codegen`, queries and responses are fully typed; a schema change breaks the build in CI rather than at runtime.
- Keeping a REST surface demonstrates the classic path and serves consumers that cannot speak GraphQL.

## Consequences

- One more plugin dependency (WPGraphQL), pinned in `.wp-env.json` and documented.
- The read model is defined once and exposed through both surfaces (see ADR 0003) to avoid drift.
