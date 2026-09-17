/**
 * Domain types for the data the frontend reads from WPGraphQL.
 *
 * These are hand-authored so the app type-checks and builds without a live
 * WordPress instance (e.g. in CI). `pnpm codegen` generates the full,
 * schema-derived operation types into `generated.ts` for local development;
 * the shapes here match the queries in `queries.ts`.
 */

export interface ProjectSummary {
  databaseId: number;
  title: string;
  slug: string;
  excerpt: string | null;
  role: string | null;
  stack: string[];
  featured: boolean;
  featuredImage: {
    node: {
      sourceUrl: string | null;
      altText: string;
    };
  } | null;
}

// Detail view: only the fields PROJECT_BY_SLUG_QUERY selects (no summary extras).
export interface ProjectDetail {
  title: string;
  excerpt: string | null;
  role: string | null;
  stack: string[];
  repoUrl: string | null;
  content: string | null;
}

export interface ProjectsQueryResult {
  projects: {
    nodes: ProjectSummary[];
  };
}

export interface ProjectBySlugQueryResult {
  project: ProjectDetail | null;
}

export interface ProjectSlugsQueryResult {
  projects: {
    nodes: Array<Pick<ProjectSummary, 'slug'>>;
  };
}
