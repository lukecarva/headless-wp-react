# 4. Author Gutenberg blocks in TypeScript with block.json (API v3)

- Status: Accepted
- Date: 2026-09-16

## Context

Blocks can be registered imperatively in JS or declaratively via `block.json`. The editor supports the Block API v3 with automatic asset and metadata handling.

## Decision

Author blocks in **TypeScript**, describe them with **`block.json` (`apiVersion: 3`)**, and build with **`@wordpress/scripts`**. PHP registers the block from its `block.json` via `register_block_type`.

## Consequences

- Metadata (attributes, supports, textdomain, asset handles) lives in one declarative file; PHP and JS stay in sync.
- `@wordpress/scripts` provides a zero-config, WP-aligned build (externalizes `@wordpress/*`, generates the asset PHP file with dependencies + version).
- TypeScript catches attribute/prop mismatches at build time.
- Dynamic (server-rendered) output is produced by a PHP `render_callback`, keeping the saved markup minimal and avoiding block-validation churn on markup changes.
