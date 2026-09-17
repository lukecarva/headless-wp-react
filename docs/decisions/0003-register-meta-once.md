# 3. Define the content model once, expose it to both APIs

- Status: Accepted
- Date: 2026-09-16

## Context

Project fields (role, stack, repo URL, featured) must appear in the block editor, in REST, and in GraphQL. Duplicating field definitions per surface causes drift.

## Decision

Register each meta field once with `register_post_meta( ..., [ 'show_in_rest' => true, ... ] )`, then mirror the same fields into WPGraphQL with `register_graphql_field` from a single source of truth (the `Meta` definition array).

## Consequences

- A field is added or changed in exactly one place.
- Sanitization/validation lives with the definition and applies uniformly.
- Slight indirection: both API registrations read from the shared definition rather than hardcoding keys.
