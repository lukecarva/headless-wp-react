/**
 * Returns the value only when it is an absolute http(s) URL, otherwise null.
 *
 * `repoUrl` is sanitized with esc_url_raw on the REST write path, but the ACF
 * field does not re-sanitize, so this is a defense-in-depth check before the
 * value is used as an href: it keeps a `javascript:` or `data:` URL out of the
 * DOM regardless of how it was stored.
 */
export function httpUrl(value: string | null | undefined): string | null {
  if (!value) {
    return null;
  }

  try {
    const { protocol } = new URL(value);
    return protocol === 'http:' || protocol === 'https:' ? value : null;
  } catch {
    return null;
  }
}
