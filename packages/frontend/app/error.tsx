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
    <section>
      <h1>Something went wrong</h1>
      <p className="lead">The projects could not be loaded. Please try again.</p>
      <button type="button" className="button" onClick={() => reset()}>
        Try again
      </button>
    </section>
  );
}
