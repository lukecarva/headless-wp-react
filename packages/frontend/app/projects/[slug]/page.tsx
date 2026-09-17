import type { Metadata } from 'next';
import { notFound } from 'next/navigation';

import { StackList } from '@/components/StackList';
import { getAllProjectSlugs, getProjectBySlug } from '@/lib/projects';
import { REVALIDATE_SECONDS } from '@/lib/config';

export const revalidate = REVALIDATE_SECONDS;

interface PageProps {
  params: { slug: string };
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
  const project = await getProjectBySlug(params.slug);
  if (!project) {
    return { title: 'Project not found' };
  }
  return {
    title: project.title,
    description: project.excerpt ? stripHtml(project.excerpt) : undefined,
  };
}

export default async function ProjectPage({ params }: PageProps): Promise<JSX.Element> {
  const project = await getProjectBySlug(params.slug);

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
          // Content is sanitized HTML from WordPress.
          dangerouslySetInnerHTML={{ __html: project.content }}
        />
      ) : null}
    </article>
  );
}

function stripHtml(html: string): string {
  return html.replace(/<[^>]*>/g, '').trim();
}
