import { cookies, draftMode } from 'next/headers';
import { redirect } from 'next/navigation';

import { PREVIEW_ID_COOKIE } from '@/lib/preview';

/**
 * Exits draft mode.
 *
 * Clears the Next draft cookie and the preview id cookie, then returns the
 * viewer to the home page rendering published content again.
 */
export async function GET(): Promise<void> {
  (await draftMode()).disable();
  (await cookies()).delete(PREVIEW_ID_COOKIE);
  redirect('/');
}
