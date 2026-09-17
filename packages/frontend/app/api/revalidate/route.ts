import { timingSafeEqual } from 'node:crypto';
import { revalidateTag } from 'next/cache';
import { NextResponse } from 'next/server';

import { env } from '@/lib/env';
import { clientIp, rateLimit } from '@/lib/rate-limit';

/**
 * On-demand revalidation webhook.
 *
 * Point a WordPress publish/update hook at `POST /api/revalidate` with the
 * shared secret in the `x-revalidate-secret` header. It busts the `wpgraphql`
 * cache tag so the next request re-fetches from WordPress immediately, instead
 * of waiting out the ISR interval.
 *
 * Rate limited per client IP, and fails closed: if no secret is configured, or
 * it does not match, the request is rejected. The rate limit also blunts any
 * attempt to brute-force the secret.
 */
export async function POST(request: Request): Promise<Response> {
  const limit = rateLimit(`revalidate:${clientIp(request)}`, 10, 60_000);
  if (!limit.ok) {
    return NextResponse.json(
      { revalidated: false, message: 'Too many requests.' },
      { status: 429, headers: { 'Retry-After': String(limit.retryAfter) } },
    );
  }

  const provided = request.headers.get('x-revalidate-secret');

  if (!env.revalidateSecret || !provided || !safeEqual(provided, env.revalidateSecret)) {
    return NextResponse.json({ revalidated: false, message: 'Invalid secret.' }, { status: 401 });
  }

  revalidateTag('wpgraphql');

  return NextResponse.json({ revalidated: true, now: Date.now() });
}

/** Constant-time string comparison to avoid leaking the secret via timing. */
function safeEqual(a: string, b: string): boolean {
  const bufferA = Buffer.from(a);
  const bufferB = Buffer.from(b);
  if (bufferA.length !== bufferB.length) {
    return false;
  }
  return timingSafeEqual(bufferA, bufferB);
}
