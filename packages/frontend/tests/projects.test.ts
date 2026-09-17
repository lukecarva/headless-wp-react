import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import type { ProjectSummary } from '@/lib/graphql/types';

// Mock the GraphQL client so the data layer can be tested without a server.
const gqlRequestMock = vi.fn();
vi.mock('@/lib/graphql/client', () => ({
  gqlRequest: (...args: unknown[]) => gqlRequestMock(...args),
}));

const sampleNode: ProjectSummary = {
  databaseId: 1,
  title: 'Headless Commerce',
  slug: 'headless-commerce',
  excerpt: null,
  role: null,
  stack: [],
  featured: false,
  featuredImage: null,
};

/** Load a fresh copy of the data layer with NEXT_PHASE set as given. */
async function loadProjects(phase: string | undefined) {
  if (phase === undefined) {
    delete process.env.NEXT_PHASE;
  } else {
    process.env.NEXT_PHASE = phase;
  }
  vi.resetModules();
  return import('@/lib/projects');
}

describe('projects data layer', () => {
  const originalPhase = process.env.NEXT_PHASE;

  beforeEach(() => {
    gqlRequestMock.mockReset();
  });

  afterEach(() => {
    if (originalPhase === undefined) {
      delete process.env.NEXT_PHASE;
    } else {
      process.env.NEXT_PHASE = originalPhase;
    }
    vi.restoreAllMocks();
  });

  it('returns the fetched nodes on success', async () => {
    gqlRequestMock.mockResolvedValue({ projects: { nodes: [sampleNode] } });

    const { getProjects } = await loadProjects(undefined);
    const result = await getProjects();

    expect(result).toEqual([sampleNode]);
  });

  it('falls back to an empty list when a read fails during build', async () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    gqlRequestMock.mockRejectedValue(new Error('CMS unreachable'));

    const { getProjects } = await loadProjects('phase-production-build');

    await expect(getProjects()).resolves.toEqual([]);
    expect(warn).toHaveBeenCalledOnce();
  });

  it('re-throws when a read fails at runtime', async () => {
    const error = vi.spyOn(console, 'error').mockImplementation(() => {});
    gqlRequestMock.mockRejectedValue(new Error('CMS unreachable'));

    const { getProjects } = await loadProjects('phase-production-server');

    await expect(getProjects()).rejects.toThrow('CMS unreachable');
    expect(error).toHaveBeenCalledOnce();
  });
});
