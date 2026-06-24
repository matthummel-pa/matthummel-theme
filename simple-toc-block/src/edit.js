import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { title, showH2, showH3, showH4, ordered } = attributes;
	const blockProps = useBlockProps( { className: 'simple-toc' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'simple-toc-block' ) }>
					<TextControl
						label={ __( 'Heading text', 'simple-toc-block' ) }
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
					/>
					<ToggleControl
						label={ __( 'Include H2 headings', 'simple-toc-block' ) }
						checked={ showH2 }
						onChange={ ( value ) => setAttributes( { showH2: value } ) }
					/>
					<ToggleControl
						label={ __( 'Include H3 headings', 'simple-toc-block' ) }
						checked={ showH3 }
						onChange={ ( value ) => setAttributes( { showH3: value } ) }
					/>
					<ToggleControl
						label={ __( 'Include H4 headings', 'simple-toc-block' ) }
						checked={ showH4 }
						onChange={ ( value ) => setAttributes( { showH4: value } ) }
					/>
					<ToggleControl
						label={ __( 'Use a numbered list', 'simple-toc-block' ) }
						checked={ ordered }
						onChange={ ( value ) => setAttributes( { ordered: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ title && <p className="simple-toc__title">{ title }</p> }
				<p className="simple-toc__placeholder">
					{ __(
						'The list of links is generated automatically from this post\u2019s headings when the page is viewed.',
						'simple-toc-block'
					) }
				</p>
			</div>
		</>
	);
}
