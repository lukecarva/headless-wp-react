import { describe, expect, it } from 'vitest';

import { httpUrl } from '@/lib/url';

describe('httpUrl', () => {
  it('passes through absolute http and https URLs', () => {
    expect(httpUrl('https://github.com/user/repo')).toBe('https://github.com/user/repo');
    expect(httpUrl('http://example.com')).toBe('http://example.com');
  });

  it('rejects dangerous or non-http schemes', () => {
    expect(httpUrl('javascript:alert(1)')).toBeNull();
    expect(httpUrl('data:text/html,<script>alert(1)</script>')).toBeNull();
    expect(httpUrl('mailto:a@b.com')).toBeNull();
  });

  it('rejects relative or malformed values and empties', () => {
    expect(httpUrl('/repo')).toBeNull();
    expect(httpUrl('not a url')).toBeNull();
    expect(httpUrl('')).toBeNull();
    expect(httpUrl(null)).toBeNull();
    expect(httpUrl(undefined)).toBeNull();
  });
});
