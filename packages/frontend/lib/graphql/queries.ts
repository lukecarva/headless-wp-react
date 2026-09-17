import { gql } from 'graphql-request';

/**
 * Shared field selection for a project summary (list views).
 */
const PROJECT_SUMMARY_FIELDS = gql`
  fragment ProjectSummaryFields on Project {
    databaseId
    title
    slug
    excerpt
    role
    stack
    featured
    featuredImage {
      node {
        sourceUrl
        altText
      }
    }
  }
`;

export const PROJECTS_QUERY = gql`
  ${PROJECT_SUMMARY_FIELDS}
  query Projects($first: Int = 12) {
    projects(first: $first, where: { orderby: { field: DATE, order: DESC } }) {
      nodes {
        ...ProjectSummaryFields
      }
    }
  }
`;

// Detail view selects only the fields the page renders (title, excerpt for
// metadata, role, stack, repoUrl, content), avoiding the summary fragment's
// extra fields.
export const PROJECT_BY_SLUG_QUERY = gql`
  query ProjectBySlug($slug: ID!) {
    project(id: $slug, idType: SLUG) {
      title
      excerpt
      role
      stack
      repoUrl
      content
    }
  }
`;

export const PROJECT_SLUGS_QUERY = gql`
  query ProjectSlugs {
    projects(first: 100) {
      nodes {
        slug
      }
    }
  }
`;
