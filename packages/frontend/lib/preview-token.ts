import { createHmac, timingSafeEqual } from 'node:crypto';

interface PreviewToken {
  id: string;
  slug: string;
  exp: string;
  token: string;
  secret: string;
}

/**
 * Verifies a preview token minted by the WordPress plugin.
 *
 * The plugin signs `{id}.{slug}.{exp}` with HMAC-SHA256 using the shared preview
 * secret. This recomputes the signature and compares it in constant time, and
 * rejects an expired token, so a preview link cannot be forged or replayed
 * indefinitely.
 */
export function verifyPreviewToken({ id, slug, exp, token, secret }: PreviewToken): boolean {
  const expiry = Number(exp);
  if (!Number.isFinite(expiry) || expiry * 1000 < Date.now()) {
    return false;
  }

  const expected = createHmac('sha256', secret).update(`${id}.${slug}.${exp}`).digest('hex');
  const provided = Buffer.from(token);
  const wanted = Buffer.from(expected);
  if (provided.length !== wanted.length) {
    return false;
  }

  return timingSafeEqual(provided, wanted);
}

function cookieSignature(payload: string, secret: string): string {
  return createHmac('sha256', secret).update(`preview:${payload}`).digest('hex');
}

/**
 * Signs the previewed project's id and slug for the draft cookie, so a draft
 * session can only read the project the (verified) token authorized, and only on
 * that project's own URL. A swapped id or slug fails verification.
 */
export function signPreviewCookie(id: string, slug: string, secret: string): string {
  const payload = `${id}:${slug}`;
  return `${payload}.${cookieSignature(payload, secret)}`;
}

/**
 * Verifies the signed draft cookie, returning the bound id and slug, or null if
 * it was tampered with.
 */
export function verifyPreviewCookie(
  value: string,
  secret: string,
): { id: string; slug: string } | null {
  const separator = value.lastIndexOf('.');
  if (separator <= 0) {
    return null;
  }

  const payload = value.slice(0, separator);
  const provided = Buffer.from(value.slice(separator + 1));
  const wanted = Buffer.from(cookieSignature(payload, secret));
  if (provided.length !== wanted.length || !timingSafeEqual(provided, wanted)) {
    return null;
  }

  const colon = payload.indexOf(':');
  if (colon <= 0) {
    return null;
  }

  return { id: payload.slice(0, colon), slug: payload.slice(colon + 1) };
}
