import { describe, expect, it, vi } from 'vitest';

vi.mock('@/lib/projects', () => ({
  getAllProjectSlugs: vi.fn(async () => ['alpha', 'beta']),
}));
vi.mock('@/lib/env', () => ({ env: { siteUrl: 'https://example.test' } }));

import sitemap from '@/app/sitemap';

describe('sitemap', () => {
  it('lists the home page and every project as absolute URLs', async () => {
    const entries = await sitemap();
    const urls = entries.map((entry) => entry.url);

    expect(urls).toEqual([
      'https://example.test/',
      'https://example.test/projects/alpha',
      'https://example.test/projects/beta',
    ]);
  });
});
