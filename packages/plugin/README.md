# @hwr/plugin (Headless Portfolio for WordPress)

The backend half of the monorepo. A WordPress plugin that defines the content model and exposes it headlessly.

## What it provides

- **`project` custom post type**: REST and GraphQL enabled, block editor ready.
- **Meta model** (`includes/Meta/ProjectMeta.php`): `role`, `stack`, `repoUrl`, `featured`, defined once and used by both APIs.
- **Custom REST routes**: `GET /wp-json/hwr/v1/projects` (`?featured=true&per_page=N`), a capability-guarded `POST .../{id}/featured` write, and a capability-guarded `GET .../{id}/preview` draft read.
- **WPGraphQL fields**: the same fields on the `Project` GraphQL type.
- **Gutenberg block**: `hwr/project-showcase`, authored in TypeScript, rendered dynamically in PHP.
- **React editing panel with optional ACF**: a Gutenberg document-sidebar panel for the project fields (see [ADR 0008](../../docs/decisions/0008-editing-surfaces.md)).
- **Headless glue**: signed draft-preview links, an on-save revalidation ping to the frontend, and a redirect of WordPress's own front-end to the Next.js app.

## Layout

```
plugin/
├── headless-portfolio.php     # bootstrap: autoload, activation, boot
├── includes/                  # PSR-4 (Hwr\Portfolio\)
│   ├── Plugin.php             # wires features to hooks
│   ├── PostType/              # the `project` CPT
│   ├── Meta/                  # single source of truth for fields
│   ├── Rest/                  # hwr/v1 controller (read, write, preview)
│   ├── GraphQL/               # WPGraphQL field registration
│   ├── Blocks/                # block registrar
│   ├── Editor/                # React editing-panel enqueue
│   ├── Acf/                   # optional ACF field group
│   ├── Preview.php            # signed draft-preview links
│   ├── FrontendRedirect.php   # WP front-end to Next
│   ├── Revalidator.php        # on-save revalidation ping
│   ├── Capabilities.php       # dedicated capabilities
│   ├── Cors.php               # restrictive CORS
│   └── I18n.php               # text domain
├── src/blocks/project-showcase/   # TypeScript block source
├── src/editor/                # React editing-panel source
├── build/                     # compiled blocks and panel (generated)
└── tests/                     # PHPUnit
```

## Develop

```bash
composer install          # PHP deps (PHPCS, PHPStan, PHPUnit)
pnpm install              # JS deps for the block build
pnpm --filter @hwr/plugin start   # watch/build blocks
```

## Quality gates

```bash
composer lint      # PHPCS (WordPress Coding Standards)
composer analyse   # PHPStan level 6
composer test      # PHPUnit (needs the WP test suite; see below)
pnpm --filter @hwr/plugin typecheck   # TS on the block source
```

### Running PHPUnit

The suite uses the WordPress test framework. The simplest path is through `wp-env` from the repo root:

```bash
pnpm env:start
wp-env run tests-cli --env-cwd=wp-content/plugins/plugin ./vendor/bin/phpunit
```

## Notes

- The block is **dynamic**: `save` returns `null` and `render.php` produces markup, so the editor and front end never disagree on saved HTML.
- The plugin **degrades gracefully** without WPGraphQL active (GraphQL registration is guarded) and without a build (`BlockRegistrar` skips a missing `build/`).
- **i18n**: the text domain is loaded on `init` (`includes/I18n.php`); add `.po`/`.mo` files under `languages/`.
- **Uninstall** (`uninstall.php`) removes the plugin's option, registered meta, and custom capabilities, but keeps `project` posts, since those are user content.
- **Authorization**: the CPT uses dedicated capabilities (`Capabilities`), granted to administrator/editor on activation. Every write path (meta `auth_callback` and the REST write route) checks `edit_post` per post; reads of published projects are public. See [ADR 0006](../../docs/decisions/0006-dedicated-project-capabilities.md).
