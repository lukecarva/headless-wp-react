// @vitest-environment node
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { mockEnv } = vi.hoisted(() => ({
  mockEnv: { previewSecret: 'preview-secret' as string | undefined },
}));

vi.mock('@/lib/env', () => ({ env: mockEnv }));

import { GET } from '@/app/api/draft/route';

function draftRequest(query: string, ip: string): Request {
  return new Request(`http://localhost/api/draft?${query}`, {
    headers: { 'x-forwarded-for': ip },
  });
}

beforeEach(() => {
  mockEnv.previewSecret = 'preview-secret';
});

describe('GET /api/draft', () => {
  it('rejects a forged token with 401', async () => {
    const response = await GET(
      draftRequest('id=1&slug=x&exp=9999999999&token=deadbeef', '203.0.113.21'),
    );
    expect(response.status).toBe(401);
  });

  it('fails closed when no preview secret is configured', async () => {
    mockEnv.previewSecret = undefined;
    const response = await GET(
      draftRequest('id=1&slug=x&exp=9999999999&token=deadbeef', '203.0.113.22'),
    );
    expect(response.status).toBe(401);
  });

  it('rate limits repeated requests and sends Retry-After', async () => {
    const ip = '203.0.113.23';
    for (let i = 0; i < 20; i += 1) {
      expect((await GET(draftRequest('id=1&slug=x&exp=1&token=bad', ip))).status).toBe(401);
    }

    const limited = await GET(draftRequest('id=1&slug=x&exp=1&token=bad', ip));
    expect(limited.status).toBe(429);
    expect(limited.headers.get('Retry-After')).toBeTruthy();
  });
});
