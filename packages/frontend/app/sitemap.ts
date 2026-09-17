import type { MetadataRoute } from 'next';

import { env } from '@/lib/env';
import { getAllProjectSlugs } from '@/lib/projects';

/**
 * Sitemap listing the home page and every published project.
 *
 * Generated at build and refreshed on the ISR interval. A WordPress-less build
 * degrades to the home entry alone, because getAllProjectSlugs returns an empty
 * list on a failed read.
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const slugs = await getAllProjectSlugs();
  const lastModified = new Date();

  return [
    { url: `${env.siteUrl}/`, lastModified, changeFrequency: 'daily', priority: 1 },
    ...slugs.map((slug) => ({
      url: `${env.siteUrl}/projects/${slug}`,
      lastModified,
      changeFrequency: 'weekly' as const,
      priority: 0.8,
    })),
  ];
}
