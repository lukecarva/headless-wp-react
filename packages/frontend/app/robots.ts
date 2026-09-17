import type { MetadataRoute } from 'next';

import { env } from '@/lib/env';

/**
 * robots.txt: allow all crawlers and point them at the sitemap.
 */
export default function robots(): MetadataRoute.Robots {
  return {
    rules: { userAgent: '*', allow: '/' },
    sitemap: `${env.siteUrl}/sitemap.xml`,
  };
}
