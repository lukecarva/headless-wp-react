import styles from './StackList.module.css';

interface StackListProps {
  stack: string[];
}

/**
 * Renders a project's technology stack as a row of pills.
 */
export function StackList({ stack }: StackListProps): JSX.Element | null {
  if (stack.length === 0) {
    return null;
  }

  return (
    <ul className={styles.list} aria-label="Technology stack">
      {stack.map((tech) => (
        <li key={tech} className={styles.pill}>
          {tech}
        </li>
      ))}
    </ul>
  );
}
