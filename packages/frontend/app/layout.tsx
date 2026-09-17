import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import Link from 'next/link';
import type { ReactNode } from 'react';

import { env } from '@/lib/env';
import './globals.css';

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-sans',
});

const REPO_URL = 'https://github.com/naynieb/headless-wp-react';

const SITE_NAME = 'Math Rosa';
const SITE_TITLE = 'Math Rosa, Headless WordPress + React';
const SITE_DESCRIPTION =
  'Selected projects, served from a headless WordPress backend through a Next.js frontend.';

export const metadata: Metadata = {
  // Resolves relative canonical/Open Graph URLs to absolute ones.
  metadataBase: new URL(env.siteUrl),
  title: {
    default: SITE_TITLE,
    template: '%s, Math Rosa',
  },
  description: SITE_DESCRIPTION,
  alternates: { canonical: '/' },
  openGraph: {
    type: 'website',
    siteName: SITE_NAME,
    title: SITE_TITLE,
    description: SITE_DESCRIPTION,
    url: '/',
  },
  twitter: {
    card: 'summary_large_image',
    title: SITE_TITLE,
    description: SITE_DESCRIPTION,
  },
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="en" className={inter.variable}>
      <body>
        <header className="site-header">
          <div className="container site-header__inner">
            <Link href="/" className="brand">
              Math<span>Rosa</span>
            </Link>
            <nav className="site-nav" aria-label="Primary">
              <Link href="/" className="site-nav__link">
                Work
              </Link>
              <a
                href={REPO_URL}
                className="site-nav__link"
                target="_blank"
                rel="noreferrer noopener"
              >
                Source ↗
              </a>
            </nav>
          </div>
        </header>
        <main className="container site-main">{children}</main>
        <footer className="site-footer">
          <div className="container site-footer__inner">
            <p className="site-footer__note">
              Headless WordPress + React reference. Built by Math Rosa.
            </p>
          </div>
        </footer>
      </body>
    </html>
  );
}
