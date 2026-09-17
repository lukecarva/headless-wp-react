/**
 * Returns the value only when it is an absolute http(s) URL, otherwise null.
 *
 * The meta's sanitize_callback (esc_url_raw) already drops disallowed schemes
 * on every write path, so this is defense in depth: it guarantees the rendered
 * href is http(s) regardless of how the value reached the database (legacy data,
 * an import, a direct write), keeping a `javascript:` or `data:` URL out of the
 * DOM.
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
