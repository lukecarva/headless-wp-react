// @vitest-environment node
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Hoisted so the vi.mock factories below can reference them.
const { revalidateTag, mockEnv } = vi.hoisted(() => ({
  revalidateTag: vi.fn(),
  mockEnv: { revalidateSecret: 'top-secret' as string | undefined },
}));

// revalidateTag is a Next server function; capture calls without running it.
vi.mock('next/cache', () => ({ revalidateTag }));
// Control the configured shared secret per test.
vi.mock('@/lib/env', () => ({ env: mockEnv }));

import { POST } from '@/app/api/revalidate/route';

function postWith(secret?: string): Request {
  const headers = new Headers();
  if (secret !== undefined) {
    headers.set('x-revalidate-secret', secret);
  }
  return new Request('http://localhost/api/revalidate', { method: 'POST', headers });
}

beforeEach(() => {
  mockEnv.revalidateSecret = 'top-secret';
  revalidateTag.mockClear();
});

describe('POST /api/revalidate', () => {
  it('busts the wpgraphql cache tag when the secret matches', async () => {
    const response = await POST(postWith('top-secret'));

    expect(response.status).toBe(200);
    await expect(response.json()).resolves.toMatchObject({ revalidated: true });
    expect(revalidateTag).toHaveBeenCalledTimes(1);
    expect(revalidateTag).toHaveBeenCalledWith('wpgraphql');
  });

  it('rejects a wrong secret and does not revalidate', async () => {
    const response = await POST(postWith('wrong-secret'));

    expect(response.status).toBe(401);
    await expect(response.json()).resolves.toMatchObject({ revalidated: false });
    expect(revalidateTag).not.toHaveBeenCalled();
  });

  it('rejects a request with no secret header', async () => {
    const response = await POST(postWith());

    expect(response.status).toBe(401);
    expect(revalidateTag).not.toHaveBeenCalled();
  });

  it('fails closed when no shared secret is configured', async () => {
    mockEnv.revalidateSecret = undefined;

    const response = await POST(postWith('top-secret'));

    expect(response.status).toBe(401);
    expect(revalidateTag).not.toHaveBeenCalled();
  });
});
