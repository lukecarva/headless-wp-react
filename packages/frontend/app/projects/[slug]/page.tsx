import type { Metadata } from 'next';
import { notFound } from 'next/navigation';

import { StackList } from '@/components/StackList';
import { getAllProjectSlugs, getProjectBySlug } from '@/lib/projects';

// Next requires this to be a literal; keep in sync with REVALIDATE_SECONDS.
export const revalidate = 60;

interface PageProps {
  // Next 15+ passes route params as a Promise.
  params: Promise<{ slug: string }>;
}

/**
 * Pre-render a static page per project at build time (SSG). New projects are
 * picked up on the next revalidation via `dynamicParams` (default true).
 */
export async function generateStaticParams(): Promise<Array<{ slug: string }>> {
  const slugs = await getAllProjectSlugs();
  return slugs.map((slug) => ({ slug }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const project = await getProjectBySlug(slug);
  if (!project) {
    return { title: 'Project not found' };
  }
  return {
    title: project.title,
    description: project.excerpt ? stripHtml(project.excerpt) : undefined,
  };
}

export default async function ProjectPage({ params }: PageProps) {
  const { slug } = await params;
  const project = await getProjectBySlug(slug);

  if (!project) {
    notFound();
  }

  return (
    <article>
      <h1>{project.title}</h1>
      {project.role ? <p className="lead">{project.role}</p> : null}
      <StackList stack={project.stack} />

      {project.repoUrl ? (
        <p style={{ marginTop: '1rem' }}>
          <a href={project.repoUrl} target="_blank" rel="noreferrer noopener">
            View repository ↗
          </a>
        </p>
      ) : null}

      {project.content ? (
        <div
          style={{ marginTop: '2rem' }}
          // Content is HTML authored in WordPress by trusted editors; it is not
          // XSS-sanitized here.
          dangerouslySetInnerHTML={{ __html: project.content }}
        />
      ) : null}
    </article>
  );
}

const NAMED_ENTITIES: Record<string, string> = {
  amp: '&',
  lt: '<',
  gt: '>',
  quot: '"',
  apos: "'",
  nbsp: ' ',
  hellip: '…',
};

function decodeHtmlEntities(text: string): string {
  return text.replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (match, entity: string) => {
    try {
      if (entity[0] === '#') {
        const codePoint =
          entity[1]?.toLowerCase() === 'x'
            ? parseInt(entity.slice(2), 16)
            : parseInt(entity.slice(1), 10);
        return String.fromCodePoint(codePoint);
      }
      return NAMED_ENTITIES[entity.toLowerCase()] ?? match;
    } catch {
      return match;
    }
  });
}

// Strips tags and decodes entities so the meta description is plain text.
function stripHtml(html: string): string {
  return decodeHtmlEntities(html.replace(/<[^>]*>/g, '')).trim();
}
