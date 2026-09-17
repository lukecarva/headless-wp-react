import { splitStack } from './stack';

describe( 'splitStack', () => {
  it( 'splits a comma separated string into trimmed tokens', () => {
    expect( splitStack( 'React, WordPress ,TypeScript' ) ).toEqual( [
      'React',
      'WordPress',
      'TypeScript',
    ] );
  } );

  it( 'drops empty entries and surrounding whitespace', () => {
    expect( splitStack( 'React,,  , WordPress' ) ).toEqual( [ 'React', 'WordPress' ] );
  } );

  it( 'returns an empty array for an empty string', () => {
    expect( splitStack( '' ) ).toEqual( [] );
    expect( splitStack( '   ' ) ).toEqual( [] );
  } );

  it( 'de-duplicates repeated tokens, mirroring the PHP split', () => {
    expect( splitStack( 'React, WordPress, React' ) ).toEqual( [ 'React', 'WordPress' ] );
  } );
} );
