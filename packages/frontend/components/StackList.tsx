import styles from './StackList.module.css';

interface StackListProps {
  stack: string[];
}

/**
 * Renders a project's technology stack as a row of pills.
 */
export function StackList({ stack }: StackListProps) {
  // Deduplicate so a repeated technology cannot produce duplicate React keys.
  const items = Array.from(new Set(stack));

  if (items.length === 0) {
    return null;
  }

  return (
    <ul className={styles.list} aria-label="Technology stack">
      {items.map((tech) => (
        <li key={tech} className={styles.pill}>
          {tech}
        </li>
      ))}
    </ul>
  );
}
