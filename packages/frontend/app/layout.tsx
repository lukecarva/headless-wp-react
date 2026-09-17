import type { Metadata } from 'next';
import type { ReactNode } from 'react';

import './globals.css';

export const metadata: Metadata = {
  title: {
    default: 'Portfolio, Headless WordPress + React',
    template: '%s, Portfolio',
  },
  description:
    'Selected projects, served from a headless WordPress backend through a Next.js frontend.',
};

export default function RootLayout({ children }: { children: ReactNode }): JSX.Element {
  return (
    <html lang="en">
      <body>
        <header className="site-header">
          <div className="container">
            <a href="/" className="brand">
              matheusdev<span>.work</span>
            </a>
          </div>
        </header>
        <main className="container">{children}</main>
        <footer className="site-footer">
          <div className="container">
            <p>Headless WordPress + React reference. Built by Math Rosa.</p>
          </div>
        </footer>
      </body>
    </html>
  );
}
