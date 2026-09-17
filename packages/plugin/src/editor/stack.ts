/**
 * Splits the stored comma separated stack string into trimmed, non-empty tokens,
 * mirroring the split the PHP layer performs. Shared by the editor panel and its
 * unit test.
 *
 * @param value Stored comma separated stack string.
 * @return Trimmed, non-empty stack tokens.
 */
export function splitStack( value: string ): string[] {
  return value
    .split( ',' )
    .map( ( item ) => item.trim() )
    .filter( ( item ) => item.length > 0 );
}
