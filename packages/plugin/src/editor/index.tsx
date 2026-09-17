/**
 * Native Block Editor panel for editing Project meta, built in React.
 *
 * Registers a document sidebar panel that reads and writes the same project
 * meta keys the REST and GraphQL layers expose, through core-data
 * (useEntityProp). No third-party field framework is involved: this is the
 * React editing UI for the headless content model, so a change here flows
 * straight to the same sanitized meta the frontend already consumes.
 */

import { FormTokenField, TextControl, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel, store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const POST_TYPE = 'project';

type ProjectMeta = {
  hwr_role?: string;
  hwr_stack?: string;
  hwr_repo_url?: string;
  hwr_featured?: boolean;
};

/**
 * Splits the stored comma separated stack string into trimmed tokens for the
 * FormTokenField, mirroring the split the PHP layer performs.
 *
 * @param value Stored comma separated stack string.
 * @return Trimmed, non-empty stack tokens.
 */
function splitStack( value: string ): string[] {
  return value
    .split( ',' )
    .map( ( item ) => item.trim() )
    .filter( ( item ) => item.length > 0 );
}

/**
 * Document sidebar panel with the project fields. Rendered only while editing a
 * project, and reads and writes post meta by key.
 */
function ProjectDetailsPanel() {
  const postType = useSelect( ( select ) => select( editorStore ).getCurrentPostType(), [] );

  const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );
  const projectMeta: ProjectMeta = meta ?? {};

  if ( postType !== POST_TYPE ) {
    return null;
  }

  // Merge onto the existing meta so editing one field never drops the others.
  const update = ( next: Partial< ProjectMeta > ): void => {
    setMeta( { ...projectMeta, ...next } );
  };

  return (
    <PluginDocumentSettingPanel
      name="hwr-project-details"
      title={ __( 'Project details', 'headless-portfolio' ) }
    >
      <div style={ { display: 'grid', gap: '16px' } }>
        <TextControl
          label={ __( 'Role', 'headless-portfolio' ) }
          value={ projectMeta.hwr_role ?? '' }
          onChange={ ( value ) => update( { hwr_role: value } ) }
          __nextHasNoMarginBottom
        />
        <FormTokenField
          label={ __( 'Stack', 'headless-portfolio' ) }
          value={ splitStack( projectMeta.hwr_stack ?? '' ) }
          onChange={ ( tokens ) => update( { hwr_stack: tokens.map( String ).join( ', ' ) } ) }
          __nextHasNoMarginBottom
        />
        <TextControl
          label={ __( 'Repository URL', 'headless-portfolio' ) }
          type="url"
          value={ projectMeta.hwr_repo_url ?? '' }
          onChange={ ( value ) => update( { hwr_repo_url: value } ) }
          __nextHasNoMarginBottom
        />
        <ToggleControl
          label={ __( 'Featured', 'headless-portfolio' ) }
          checked={ Boolean( projectMeta.hwr_featured ) }
          onChange={ ( value ) => update( { hwr_featured: value } ) }
          __nextHasNoMarginBottom
        />
      </div>
    </PluginDocumentSettingPanel>
  );
}

registerPlugin( 'hwr-project-details', { render: ProjectDetailsPanel } );
