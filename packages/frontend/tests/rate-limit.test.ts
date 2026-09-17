import { describe, expect, it } from 'vitest';

import { rateLimit } from '@/lib/rate-limit';

describe('rateLimit', () => {
  it('allows up to the limit, then blocks with a retry hint', () => {
    const key = `allow-${Math.random()}`;

    for (let i = 0; i < 3; i += 1) {
      expect(rateLimit(key, 3, 1000).ok).toBe(true);
    }

    const blocked = rateLimit(key, 3, 1000);
    expect(blocked.ok).toBe(false);
    expect(blocked.retryAfter).toBeGreaterThan(0);
  });

  it('resets after the window elapses', async () => {
    const key = `reset-${Math.random()}`;

    expect(rateLimit(key, 1, 30).ok).toBe(true);
    expect(rateLimit(key, 1, 30).ok).toBe(false);

    await new Promise((resolve) => setTimeout(resolve, 45));

    expect(rateLimit(key, 1, 30).ok).toBe(true);
  });

  it('keys are independent', () => {
    expect(rateLimit(`a-${Math.random()}`, 1, 1000).ok).toBe(true);
    expect(rateLimit(`b-${Math.random()}`, 1, 1000).ok).toBe(true);
  });
});
