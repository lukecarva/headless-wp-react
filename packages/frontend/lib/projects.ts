import { gqlRequest } from '@/lib/graphql/client';
import { PROJECTS_QUERY, PROJECT_BY_SLUG_QUERY, PROJECT_SLUGS_QUERY } from '@/lib/graphql/queries';
import type {
  ProjectBySlugQueryResult,
  ProjectDetail,
  ProjectSlugsQueryResult,
  ProjectSummary,
  ProjectsQueryResult,
} from '@/lib/graphql/types';

/**
 * Data-access layer for projects. Routes call these functions instead of the
 * GraphQL client directly, so query wiring stays in one place and is easy to
 * mock in tests.
 *
 * Failure handling: during a production build a failed read degrades to a
 * fallback so the static build still succeeds; at runtime it is logged and
 * re-thrown so real outages stay visible.
 */

// During `next build`, Server Components run to prerender pages. WordPress may
// be unreachable then (for example in CI with no live CMS), so builds must not
// fail hard.
const isBuildTime = process.env.NEXT_PHASE === 'phase-production-build';

async function safeQuery<T>(label: string, run: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await run();
  } catch (error) {
    if (isBuildTime) {
      // Degrade to a fallback so the static build succeeds; real data fills in
      // on the next successful revalidation.
      console.warn(`[projects] "${label}" failed during build; using fallback.`, error);
      return fallback;
    }

    // At runtime, surface the failure instead of hiding an outage behind an
    // empty state. Under ISR this keeps serving the last good page
    // (stale-while-revalidate) while the error stays observable.
    console.error(`[projects] "${label}" failed at runtime.`, error);
    throw error;
  }
}

export async function getProjects(first = 12): Promise<ProjectSummary[]> {
  return safeQuery(
    'getProjects',
    async () => {
      const data = await gqlRequest<ProjectsQueryResult, { first: number }>(PROJECTS_QUERY, {
        first,
      });
      return data.projects.nodes;
    },
    [],
  );
}

export async function getProjectBySlug(slug: string): Promise<ProjectDetail | null> {
  return safeQuery(
    `getProjectBySlug(${slug})`,
    async () => {
      const data = await gqlRequest<ProjectBySlugQueryResult, { slug: string }>(
        PROJECT_BY_SLUG_QUERY,
        { slug },
      );
      return data.project;
    },
    null,
  );
}

export async function getAllProjectSlugs(): Promise<string[]> {
  return safeQuery(
    'getAllProjectSlugs',
    async () => {
      const data = await gqlRequest<ProjectSlugsQueryResult, Record<string, never>>(
        PROJECT_SLUGS_QUERY,
      );
      return data.projects.nodes.map((node) => node.slug);
    },
    [],
  );
}
