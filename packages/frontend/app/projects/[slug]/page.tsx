import type { Metadata } from 'next';
import { cookies, draftMode } from 'next/headers';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import { PreviewBanner } from '@/components/PreviewBanner';
import { StackList } from '@/components/StackList';
import { env } from '@/lib/env';
import type { ProjectDetail } from '@/lib/graphql/types';
import { getProjectPreviewById, PREVIEW_ID_COOKIE } from '@/lib/preview';
import { verifyPreviewCookie } from '@/lib/preview-token';
import { getAllProjectSlugs, getProjectBySlug } from '@/lib/projects';
import styles from './detail.module.css';

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
  const description = project.excerpt ? stripHtml(project.excerpt) : undefined;
  const url = `/projects/${slug}`;
  return {
    title: project.title,
    description,
    alternates: { canonical: url },
    openGraph: {
      type: 'article',
      title: project.title,
      description,
      url,
    },
    twitter: {
      card: 'summary_large_image',
      title: project.title,
      description,
    },
  };
}

/**
 * Load the project for the page. In draft mode, read the draft by its database
 * id (from the preview cookie) through an authenticated request; otherwise read
 * the published project by slug.
 */
async function loadProject(
  slug: string,
): Promise<{ project: ProjectDetail | null; isDraft: boolean }> {
  const { isEnabled } = await draftMode();

  if (isEnabled) {
    // The cookie is signed by the draft entry point and binds the id to the slug,
    // so a draft is served only on the project the token authorized. Other URLs,
    // a tampered cookie, or a failed draft read fall through to published content.
    const cookieValue = (await cookies()).get(PREVIEW_ID_COOKIE)?.value;
    const preview =
      cookieValue && env.previewSecret ? verifyPreviewCookie(cookieValue, env.previewSecret) : null;

    if (preview && preview.slug === slug) {
      const id = Number(preview.id);
      const draft = Number.isFinite(id) ? await getProjectPreviewById(id) : null;
      if (draft) {
        return { project: draft, isDraft: true };
      }
    }
  }

  return { project: await getProjectBySlug(slug), isDraft: false };
}

export default async function ProjectPage({ params }: PageProps) {
  const { slug } = await params;
  const { project, isDraft } = await loadProject(slug);

  if (!project) {
    notFound();
  }

  return (
    <article>
      <Link href="/" className={styles.back}>
        ← Selected work
      </Link>

      {isDraft ? <PreviewBanner /> : null}

      <header className={styles.header}>
        {project.role ? <p className={styles.role}>{project.role}</p> : null}
        <h1 className={styles.title}>{project.title}</h1>
        <StackList stack={project.stack} />

        {project.repoUrl ? (
          <div className={styles.actions}>
            <a className="button" href={project.repoUrl} target="_blank" rel="noreferrer noopener">
              View repository ↗
            </a>
          </div>
        ) : null}
      </header>

      {project.content ? (
        <div
          className="prose"
          // project.content is WordPress post HTML, filtered through wp_kses on
          // save except for trusted roles with the unfiltered_html capability.
          // It is rendered here as already-trusted markup, not re-sanitized on
          // the client.
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
