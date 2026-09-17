import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

import metadata from './block.json';
import Edit, { type ProjectShowcaseAttributes } from './edit';
import './style.scss';
import './editor.scss';

// The block.json import is typed by TS as a plain JSON object; cast it to the
// block configuration shape (title/category/attributes all come from the file).
const blockMetadata = metadata as unknown as BlockConfiguration< ProjectShowcaseAttributes >;

/**
 * The block is dynamic: markup is produced server-side by `render.php`, so
 * `save` returns `null` and only the attributes are persisted. This avoids
 * block-validation errors when the rendered markup evolves.
 */
registerBlockType( blockMetadata, {
  edit: Edit,
  save: () => null,
} );
