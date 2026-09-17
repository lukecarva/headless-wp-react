/**
 * Splits the stored comma separated stack string into trimmed, non-empty, unique
 * tokens, mirroring the split the PHP layer performs (ProjectMeta::split_stack).
 * Shared by the editor panel and its unit test.
 *
 * @param value Stored comma separated stack string.
 * @return Trimmed, non-empty, de-duplicated stack tokens.
 */
export function splitStack( value: string ): string[] {
  const tokens = value
    .split( ',' )
    .map( ( item ) => item.trim() )
    .filter( ( item ) => item.length > 0 );

  return [ ...new Set( tokens ) ];
}
