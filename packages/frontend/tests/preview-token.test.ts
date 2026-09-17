// @vitest-environment node
import { createHmac } from 'node:crypto';

import { describe, expect, it } from 'vitest';

import { signPreviewCookie, verifyPreviewCookie, verifyPreviewToken } from '@/lib/preview-token';

const SECRET = 'test-preview-secret';

function sign(id: string, slug: string, exp: string, secret = SECRET): string {
  return createHmac('sha256', secret).update(`${id}.${slug}.${exp}`).digest('hex');
}

const inFuture = (): string => String(Math.floor(Date.now() / 1000) + 300);
const inPast = (): string => String(Math.floor(Date.now() / 1000) - 10);

describe('verifyPreviewToken', () => {
  it('accepts a valid, unexpired token', () => {
    const exp = inFuture();
    const token = sign('12', 'my-project', exp);

    expect(verifyPreviewToken({ id: '12', slug: 'my-project', exp, token, secret: SECRET })).toBe(
      true,
    );
  });

  it('rejects an expired token', () => {
    const exp = inPast();
    const token = sign('12', 'my-project', exp);

    expect(verifyPreviewToken({ id: '12', slug: 'my-project', exp, token, secret: SECRET })).toBe(
      false,
    );
  });

  it('rejects a tampered id', () => {
    const exp = inFuture();
    const token = sign('12', 'my-project', exp);

    expect(verifyPreviewToken({ id: '13', slug: 'my-project', exp, token, secret: SECRET })).toBe(
      false,
    );
  });

  it('rejects a token signed with a different secret', () => {
    const exp = inFuture();
    const token = sign('12', 'my-project', exp, 'other-secret');

    expect(verifyPreviewToken({ id: '12', slug: 'my-project', exp, token, secret: SECRET })).toBe(
      false,
    );
  });

  it('rejects a malformed token', () => {
    const exp = inFuture();

    expect(
      verifyPreviewToken({ id: '12', slug: 'my-project', exp, token: 'deadbeef', secret: SECRET }),
    ).toBe(false);
  });
});

describe('preview cookie signing (id + slug binding)', () => {
  it('round-trips a signed id and slug', () => {
    expect(verifyPreviewCookie(signPreviewCookie('42', 'my-project', SECRET), SECRET)).toEqual({
      id: '42',
      slug: 'my-project',
    });
  });

  it('rejects a swapped id', () => {
    const tampered = signPreviewCookie('42', 'my-project', SECRET).replace(/^42/, '99');
    expect(verifyPreviewCookie(tampered, SECRET)).toBeNull();
  });

  it('rejects a swapped slug', () => {
    const tampered = signPreviewCookie('42', 'my-project', SECRET).replace(
      'my-project',
      'other-project',
    );
    expect(verifyPreviewCookie(tampered, SECRET)).toBeNull();
  });

  it('rejects a wrong secret', () => {
    expect(verifyPreviewCookie(signPreviewCookie('42', 'my-project', SECRET), 'other')).toBeNull();
  });

  it('rejects a malformed value', () => {
    expect(verifyPreviewCookie('42:my-project', SECRET)).toBeNull();
    expect(verifyPreviewCookie('', SECRET)).toBeNull();
  });
});
