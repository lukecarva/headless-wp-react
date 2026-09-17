import { DEFAULT_WORDPRESS_URL } from './lib/defaults.js';

/** @type {import('next').NextConfig} */
const wpHost = (() => {
  try {
    return new URL(process.env.NEXT_PUBLIC_WORDPRESS_URL ?? DEFAULT_WORDPRESS_URL).hostname;
  } catch {
    return 'localhost';
  }
})();

// Baseline security headers applied to every response. A Content-Security-Policy
// is intentionally omitted: a strict CSP needs per-request nonces to work with
// Next's inline scripts, which is out of scope here.
const securityHeaders = [
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'X-Frame-Options', value: 'DENY' },
  { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
  { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=()' },
];

const nextConfig = {
  reactStrictMode: true,
  images: {
    // Allow the WordPress media host so <Image> can optimize uploaded images.
    remotePatterns: [
      { protocol: 'http', hostname: wpHost },
      { protocol: 'https', hostname: wpHost },
    ],
  },
  async headers() {
    return [{ source: '/:path*', headers: securityHeaders }];
  },
};

export default nextConfig;
