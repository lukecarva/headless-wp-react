'use client';

import { useEffect } from 'react';

interface ErrorProps {
  error: Error & { digest?: string };
  reset: () => void;
}

export default function Error({ error, reset }: ErrorProps) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <section className="hero">
      <span className="hero__eyebrow">Something went wrong</span>
      <h1>
        Could not load <em>projects</em>
      </h1>
      <p className="lead">
        The WordPress backend did not respond as expected. This is usually temporary.
      </p>
      <div style={{ marginTop: '1.5rem' }}>
        <button type="button" className="button" onClick={() => reset()}>
          Try again
        </button>
      </div>
    </section>
  );
}
