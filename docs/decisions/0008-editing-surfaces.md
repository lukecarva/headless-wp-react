# 8. Project editing: a React panel by default, ACF optional

- Status: Accepted
- Date: 2026-09-17

## Context

A project has four meta fields (role, stack, repoUrl, featured). The block editor can edit them through more than one surface, and offering several at once reads as indecision rather than judgement.

Three surfaces are technically possible, all writing to the same meta keys:

1. The core Custom Fields panel, enabled by `custom-fields` post type support.
2. A React panel registered in the Gutenberg document sidebar (`Editor\ProjectEditorPanel` plus `src/editor`).
3. An ACF field group registered in code (`Acf\ProjectFieldGroup`), shown when the ACF plugin is installed.

## Decision

The **React panel is the default** editing surface. It is authored in React against the Block Editor's own data APIs, which is exactly the skill this reference exists to show, and it needs no third-party plugin.

**ACF is an optional integration.** When the ACF (or Secure Custom Fields) plugin is active, the React panel stands aside (`ProjectEditorPanel` checks `function_exists('acf_add_local_field_group')`) and ACF renders the same fields, so a team that already lives in ACF keeps the fields in its usual flow. The two are mutually exclusive; an editor never sees both.

**The raw Custom Fields UI is off.** The classic-editor metabox is removed for the post type, and the Gutenberg Custom Fields panel is a per-user preference that is off by default, so it is not a visible surface.

`custom-fields` support is kept in the post type on purpose: the React panel reads and writes meta through the block editor's entity (`useEntityProp('postType', 'project', 'meta')`), which requires it. Removing the flag would break the default editing surface, so it stays while the raw panel is suppressed.

## Consequences

**Positive**

- One editing surface at a time, each with a documented reason, instead of three overlapping panels.
- The default path demonstrates React inside Gutenberg with no dependency; the optional path meets ACF-first teams where they are.

**Trade-offs**

- Keeping `custom-fields` support means a user who deliberately enables the Gutenberg Custom Fields panel can still see the raw fields. That is an opt-in developer action, not the default experience.
- The ACF integration is extra code that stays dormant unless the plugin is present. It is guarded so it never runs, or duplicates the React panel, without ACF.
