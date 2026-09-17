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
      <h1>Selected work</h1>
      <p className="lead">
        Projects managed in WordPress, delivered through a decoupled React frontend.
      </p>

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
