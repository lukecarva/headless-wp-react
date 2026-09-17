import { env } from '@/lib/env';
import type { ProjectDetail } from '@/lib/graphql/types';

/**
 * httpOnly cookie carrying the previewed project's database id, set by the draft
 * entry point and read by the project page while draft mode is on.
 */
export const PREVIEW_ID_COOKIE = 'hwr-preview-id';

/**
 * Fetch a project draft by database id from the plugin's capability-guarded
 * preview endpoint, authenticated with a WordPress Application Password.
 *
 * The public GraphQL and REST reads return only published content, so a draft is
 * read through the dedicated `hwr/v1/projects/{id}/preview` route, which checks
 * `edit_post` for the project. The request never caches, so an editor always
 * sees the latest draft, and it returns null when the credentials are not
 * configured, so the caller can fall back to the published read.
 */
export async function getProjectPreviewById(id: number): Promise<ProjectDetail | null> {
  if (!env.wpAppUser || !env.wpAppPassword) {
    console.warn('[preview] WP_APP_USER / WP_APP_PASSWORD are not set; cannot read drafts.');
    return null;
  }

  const auth = Buffer.from(`${env.wpAppUser}:${env.wpAppPassword}`).toString('base64');
  const response = await fetch(`${env.wordpressUrl}/wp-json/hwr/v1/projects/${id}/preview`, {
    headers: { Authorization: `Basic ${auth}` },
    cache: 'no-store',
  });

  if (!response.ok) {
    console.error(`[preview] draft read failed (${response.status}).`);
    return null;
  }

  return (await response.json()) as ProjectDetail;
}
