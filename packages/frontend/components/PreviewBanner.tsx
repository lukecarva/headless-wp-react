import Link from 'next/link';

import styles from './PreviewBanner.module.css';

/**
 * Banner shown while draft mode is active, with a link to exit preview.
 *
 * The exit link disables prefetch on purpose: prefetching /api/draft/disable
 * would turn draft mode off before the editor clicked it.
 */
export function PreviewBanner() {
  return (
    <div className={styles.banner} role="status">
      <span>Preview mode, showing unpublished draft content.</span>
      <Link href="/api/draft/disable" className={styles.exit} prefetch={false}>
        Exit preview
      </Link>
    </div>
  );
}
