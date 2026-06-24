import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { title, showH2, showH3, showH4, ordered } = attributes;
	const blockProps = useBlockProps( { className: 'tocflow' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'tocflow' ) }>
					<TextControl
						label={ __( 'Heading text', 'tocflow' ) }
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
					/>
					<ToggleControl
						label={ __( 'Include H2 headings', 'tocflow' ) }
						checked={ showH2 }
						onChange={ ( value ) => setAttributes( { showH2: value } ) }
					/>
					<ToggleControl
						label={ __( 'Include H3 headings', 'tocflow' ) }
						checked={ showH3 }
						onChange={ ( value ) => setAttributes( { showH3: value } ) }
					/>
					<ToggleControl
						label={ __( 'Include H4 headings', 'tocflow' ) }
						checked={ showH4 }
						onChange={ ( value ) => setAttributes( { showH4: value } ) }
					/>
					<ToggleControl
						label={ __( 'Use a numbered list', 'tocflow' ) }
						checked={ ordered }
						onChange={ ( value ) => setAttributes( { ordered: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ title && <p className="tocflow__title">{ title }</p> }
				<p className="tocflow__placeholder">
					{ __(
						'The list of links is generated automatically from this post\u2019s headings when the page is viewed.',
						'tocflow'
					) }
				</p>
			</div>
		</>
	);
}
