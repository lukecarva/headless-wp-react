# 6. Dedicated capabilities for projects

- Status: Accepted
- Date: 2026-09-16

## Context

By default the `project` CPT reused the built-in `post` capabilities, so anyone
who could edit posts (e.g. an Author) could also manage projects. The client
wants project management restricted to specific roles, and authorization must
be enforced server-side, never inferred from the frontend.

## Decision

Register the CPT with dedicated capabilities and let WordPress map meta caps:

```php
'capability_type' => array( 'project', 'projects' ),
'map_meta_cap'    => true,
```

Custom post-type capabilities are **not** granted to any role automatically,
not even to administrators, so `Hwr\Portfolio\Capabilities` grants the project
primitives (`edit_projects`, `edit_others_projects`, `publish_projects`, and so
on) to `administrator` and `editor` on activation, and removes them on
uninstall.

Because `map_meta_cap` is on, `current_user_can( 'edit_post', $id )` resolves to
these project capabilities. The meta `auth_callback` and the REST write route
(`POST /hwr/v1/projects/{id}/featured`) both use that per-post check, so a
single capability model governs every write path.

## Consequences

- Only administrators and editors manage projects; authors and lower roles are
  denied, enforced by WordPress core and our checks.
- Capabilities are granted on **activation**. After upgrading an already-active
  install, re-activate (or re-run the grant) so roles pick up any new caps.
- REST and GraphQL reads of published projects remain public and unaffected.
