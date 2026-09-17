import { ProjectCard } from '@/components/ProjectCard';
import { getProjects } from '@/lib/projects';
import { env } from '@/lib/env';

// Statically generated, revalidated on an interval (ISR). Next requires this to
// be a literal; keep it in sync with REVALIDATE_SECONDS in lib/config.ts.
export const revalidate = 60;

export default async function HomePage() {
  const projects = await getProjects();

  return (
    <>
      <section className="hero">
        <h1>
          Selected <em>work</em>
        </h1>
        <p className="lead">
          Projects managed in WordPress and delivered through a decoupled React frontend. Typed end
          to end with WPGraphQL, statically rendered and revalidated on demand.
        </p>
      </section>

      <div className="section-head">
        <h2>Projects</h2>
        {projects.length > 0 ? (
          <span className="section-head__count">
            {projects.length} {projects.length === 1 ? 'project' : 'projects'}
          </span>
        ) : null}
      </div>

      {projects.length === 0 ? (
        <p>
          No projects yet. Add a few in{' '}
          <a href={`${env.wordpressUrl}/wp-admin/edit.php?post_type=project`}>wp-admin</a>.
        </p>
      ) : (
        <section className="grid">
          {projects.map((project) => (
            <ProjectCard key={project.databaseId} project={project} />
          ))}
        </section>
      )}
    </>
  );
}
