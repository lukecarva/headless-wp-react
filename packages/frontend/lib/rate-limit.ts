interface RateWindow {
  count: number;
  resetAt: number;
}

const windows = new Map<string, RateWindow>();

/** Hard cap on tracked keys, so the map cannot grow without bound. */
const MAX_ENTRIES = 10_000;

export interface RateLimitResult {
  ok: boolean;
  /** Seconds until the window resets, when the call is blocked. */
  retryAfter: number;
}

/**
 * In-memory fixed-window rate limiter.
 *
 * Fits a single instance. A multi-instance or serverless deployment would back
 * this with a shared store (Redis, Upstash) behind the same interface. Returns
 * whether the call is allowed and, when not, the seconds until the window
 * resets so the caller can send a Retry-After header.
 */
export function rateLimit(key: string, limit: number, windowMs: number): RateLimitResult {
  const now = Date.now();
  const current = windows.get(key);

  if (!current || current.resetAt <= now) {
    windows.set(key, { count: 1, resetAt: now + windowMs });
    pruneExpired(now);
    return { ok: true, retryAfter: 0 };
  }

  if (current.count >= limit) {
    return { ok: false, retryAfter: Math.ceil((current.resetAt - now) / 1000) };
  }

  current.count += 1;
  return { ok: true, retryAfter: 0 };
}

/** Cleanup so the map cannot grow without bound, even under a flood of keys. */
function pruneExpired(now: number): void {
  if (windows.size < MAX_ENTRIES) {
    return;
  }
  for (const [key, entry] of windows) {
    if (entry.resetAt <= now) {
      windows.delete(key);
    }
  }
  // A flood of distinct (possibly spoofed) keys within one window cannot be
  // reclaimed by the expiry sweep above while the entries are still live, so cap
  // the map hard. Map iteration is insertion order, so this drops the oldest.
  if (windows.size >= MAX_ENTRIES) {
    for (const key of windows.keys()) {
      windows.delete(key);
      if (windows.size < MAX_ENTRIES) {
        break;
      }
    }
  }
}

/**
 * Best-effort client IP for keying the limiter.
 *
 * Reads the leftmost `x-forwarded-for`, then `x-real-ip`. This is trustworthy
 * only behind a proxy that sets these headers; when the server is exposed
 * directly the value is client-controlled. So this limiter is defense in depth,
 * the webhook secret and preview token are the real gate, not a hard control. A
 * multi-instance deployment should key off the platform's connecting IP and use
 * a shared store.
 */
export function clientIp(request: Request): string {
  const forwarded = request.headers.get('x-forwarded-for');
  if (forwarded) {
    return forwarded.split(',')[0]?.trim() || 'unknown';
  }
  return request.headers.get('x-real-ip') ?? 'unknown';
}
