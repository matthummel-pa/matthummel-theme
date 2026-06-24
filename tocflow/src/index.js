import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import './editor.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	// Dynamic block: the front-end markup is produced by render.php on the server.
	save: () => null,
} );
