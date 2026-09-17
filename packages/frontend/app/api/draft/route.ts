import { cookies, draftMode } from 'next/headers';
import { redirect } from 'next/navigation';
import { NextResponse } from 'next/server';

import { env } from '@/lib/env';
import { PREVIEW_ID_COOKIE } from '@/lib/preview';
import { signPreviewCookie, verifyPreviewToken } from '@/lib/preview-token';
import { clientIp, rateLimit } from '@/lib/rate-limit';

/**
 * Draft mode entry point.
 *
 * WordPress's "Preview" link for a project points here with a signed token. On a
 * valid token this enables Next draft mode, records the project's database id in
 * an httpOnly cookie, and redirects to the project page, which then reads the
 * draft from an authenticated request. Fails closed with 401 on a missing,
 * expired or forged token.
 */
export async function GET(request: Request): Promise<Response> {
  const limit = rateLimit(`draft:${clientIp(request)}`, 20, 60_000);
  if (!limit.ok) {
    return NextResponse.json(
      { message: 'Too many requests.' },
      { status: 429, headers: { 'Retry-After': String(limit.retryAfter) } },
    );
  }

  const { searchParams } = new URL(request.url);
  const id = searchParams.get('id');
  const slug = searchParams.get('slug');
  const exp = searchParams.get('exp');
  const token = searchParams.get('token');

  const secret = env.previewSecret;
  if (
    !secret ||
    !id ||
    !slug ||
    !exp ||
    !token ||
    !verifyPreviewToken({ id, slug, exp, token, secret })
  ) {
    return NextResponse.json({ message: 'Invalid preview token.' }, { status: 401 });
  }

  (await draftMode()).enable();
  // Store the id and slug signed with the preview secret, so a draft session can
  // only read the project the token authorized, and only on that project's URL (a
  // swapped id or slug fails verification on the page). Secure in production, and
  // bounded to an hour so a preview session does not last the whole browser.
  (await cookies()).set(PREVIEW_ID_COOKIE, signPreviewCookie(id, slug, secret), {
    httpOnly: true,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    maxAge: 60 * 60,
    path: '/',
  });

  redirect(`/projects/${slug}`);
}
