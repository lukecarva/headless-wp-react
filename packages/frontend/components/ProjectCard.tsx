import Image from 'next/image';
import Link from 'next/link';

import type { ProjectSummary } from '@/lib/graphql/types';
import { StackList } from './StackList';
import styles from './ProjectCard.module.css';

interface ProjectCardProps {
  project: ProjectSummary;
}

/**
 * Card for a single project in the listing grid.
 */
export function ProjectCard({ project }: ProjectCardProps) {
  const image = project.featuredImage?.node;
  const imageUrl = image?.sourceUrl;

  return (
    <article className={styles.card}>
      {imageUrl ? (
        // `fill` + a fixed-aspect wrapper keeps cards uniform and never
        // distorts arbitrary source ratios (it crops via object-fit: cover).
        // `sizes` lets Next serve an appropriately scaled image per breakpoint.
        <div className={styles.imageWrap}>
          <Image
            className={styles.image}
            src={imageUrl}
            alt={image?.altText || project.title}
            fill
            sizes="(max-width: 640px) 100vw, (max-width: 1080px) 50vw, 360px"
          />
        </div>
      ) : null}

      <div className={styles.body}>
        {project.featured ? <span className={styles.badge}>Featured</span> : null}
        <h2 className={styles.title}>
          <Link href={`/projects/${project.slug}`}>{project.title}</Link>
        </h2>
        {project.role ? <p className={styles.role}>{project.role}</p> : null}
        {project.excerpt ? (
          <div
            className={styles.excerpt}
            // Excerpt is HTML authored in WordPress by trusted editors; it is
            // not XSS-sanitized here.
            dangerouslySetInnerHTML={{ __html: project.excerpt }}
          />
        ) : null}
        <StackList stack={project.stack} />
      </div>
    </article>
  );
}
