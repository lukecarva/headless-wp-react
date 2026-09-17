import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';

import metadata from './block.json';

export type ProjectShowcaseAttributes = {
  featuredOnly: boolean;
  count: number;
  heading: string;
};

// Editor UI for the Project Showcase block. Controls live in the Inspector
// sidebar; the block body is a live server-side preview (ServerSideRender), so
// the editor shows what render.php will output.
export default function Edit( {
  attributes,
  setAttributes,
}: BlockEditProps< ProjectShowcaseAttributes > ) {
  const { featuredOnly, count, heading } = attributes;
  const blockProps = useBlockProps();

  return (
    <div { ...blockProps }>
      <InspectorControls>
        <PanelBody title={ __( 'Showcase settings', 'headless-portfolio' ) }>
          <TextControl
            label={ __( 'Heading', 'headless-portfolio' ) }
            value={ heading }
            onChange={ ( value ) => setAttributes( { heading: value } ) }
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={ __( 'Featured projects only', 'headless-portfolio' ) }
            checked={ featuredOnly }
            onChange={ ( value ) => setAttributes( { featuredOnly: value } ) }
            __nextHasNoMarginBottom
          />
          <RangeControl
            label={ __( 'Number of projects', 'headless-portfolio' ) }
            value={ count }
            min={ 1 }
            max={ 12 }
            onChange={ ( value ) => setAttributes( { count: value ?? 1 } ) }
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>

      <ServerSideRender block={ metadata.name } attributes={ attributes } />
    </div>
  );
}
