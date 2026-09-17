export default function Loading() {
  return (
    <>
      <section className="hero">
        <h1>
          Selected <em>work</em>
        </h1>
        <p className="lead">Loading projects…</p>
      </section>

      <section className="grid" aria-hidden="true">
        {['a', 'b', 'c', 'd'].map((key) => (
          <div key={key} className="skeleton-card" />
        ))}
      </section>
    </>
  );
}
