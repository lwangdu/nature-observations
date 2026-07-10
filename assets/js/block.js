( function ( blocks, element, components, blockEditor, serverSideRender, i18n ) {
	var el = element.createElement;
	var useEffect = element.useEffect;
	var useRef = element.useRef;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var Placeholder = components.Placeholder;
	var Spinner = components.Spinner;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;
	var __ = i18n.__;
	var settings = window.fieldObservationShowcase || {};
	var defaultProjectId = settings.defaultProjectId || 0;
	var defaultProjectSlug = settings.defaultProjectSlug || '';
	var defaultPerPage = settings.defaultPerPage || 100;
	var maxPerPage = settings.maxPerPage || 200;
	var openLinksInNewTab = settings.openLinksInNewTab !== false;

	function initPreviewMaps( root ) {
		if ( window.fieldObservationShowcaseInitMaps ) {
			window.fieldObservationShowcaseInitMaps( root );
		}
	}

	function MapServerSidePreview( props ) {
		var ref = useRef();

		useEffect( function () {
			var observer;
			var root = ref.current;

			if ( ! root ) {
				return;
			}

			initPreviewMaps( root );
			setTimeout( function () {
				initPreviewMaps( root );
			}, 250 );

			if ( window.MutationObserver ) {
				observer = new window.MutationObserver( function () {
					initPreviewMaps( root );
				} );
				observer.observe( root, {
					childList: true,
					subtree: true
				} );
			}

			return function () {
				if ( observer ) {
					observer.disconnect();
				}
			};
		}, [ JSON.stringify( props.attributes ) ] );

		return el(
			'div',
			{ ref: ref },
			el( ServerSideRender, props )
		);
	}

	blocks.registerBlockType( 'field-observation-showcase/observations', {
		title: __( 'iNaturalist Observations', 'field-observation-showcase' ),
		icon: 'visibility',
		category: 'widgets',
		attributes: {
			projectId: {
				type: 'number',
				default: defaultProjectId
			},
			projectSlug: {
				type: 'string',
				default: defaultProjectSlug
			},
			placeId: {
				type: 'number',
				default: 0
			},
			userId: {
				type: 'string',
				default: ''
			},
			perPage: {
				type: 'number',
				default: defaultPerPage
			},
			openLinksInNewTab: {
				type: 'boolean',
				default: openLinksInNewTab
			}
		},
		edit: function ( props ) {
			var attributes = props.attributes;

			return el(
				'div',
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'iNaturalist Source', 'field-observation-showcase' ) },
						el( TextControl, {
							label: __( 'Project slug', 'field-observation-showcase' ),
							value: attributes.projectSlug,
							onChange: function ( value ) {
								props.setAttributes( { projectSlug: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Project ID fallback', 'field-observation-showcase' ),
							type: 'number',
							value: attributes.projectId,
							onChange: function ( value ) {
								props.setAttributes( { projectId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: __( 'Place ID', 'field-observation-showcase' ),
							type: 'number',
							value: attributes.placeId,
							onChange: function ( value ) {
								props.setAttributes( { placeId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: __( 'User ID or login', 'field-observation-showcase' ),
							value: attributes.userId,
							onChange: function ( value ) {
								props.setAttributes( { userId: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Observations per page', 'field-observation-showcase' ),
							type: 'number',
							value: attributes.perPage,
							onChange: function ( value ) {
								var count = parseInt( value, 10 ) || defaultPerPage;
								props.setAttributes( { perPage: Math.min( Math.max( count, 1 ), maxPerPage ) } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Open iNaturalist links in a new tab', 'field-observation-showcase' ),
							checked: attributes.openLinksInNewTab,
							onChange: function ( value ) {
								props.setAttributes( { openLinksInNewTab: value } );
							}
						} )
					)
				),
				el( ServerSideRender, {
					block: 'field-observation-showcase/observations',
					attributes: attributes,
					LoadingResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: __( 'iNaturalist Observations', 'field-observation-showcase' ) },
							el( Spinner ),
							el( 'span', {}, __( 'Loading observations...', 'field-observation-showcase' ) )
						);
					},
					ErrorResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: __( 'iNaturalist Observations', 'field-observation-showcase' ) },
							el( 'span', {}, __( 'Unable to preview observations. Check the source settings and try again.', 'field-observation-showcase' ) )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );

	blocks.registerBlockType( 'field-observation-showcase/observations-map', {
		title: __( 'iNaturalist Observations Map', 'field-observation-showcase' ),
		icon: 'location-alt',
		category: 'widgets',
		attributes: {
			projectId: {
				type: 'number',
				default: defaultProjectId
			},
			projectSlug: {
				type: 'string',
				default: defaultProjectSlug
			},
			placeId: {
				type: 'number',
				default: 0
			},
			userId: {
				type: 'string',
				default: ''
			},
			perPage: {
				type: 'number',
				default: 200
			},
			openLinksInNewTab: {
				type: 'boolean',
				default: openLinksInNewTab
			}
		},
		edit: function ( props ) {
			var attributes = props.attributes;

			return el(
				'div',
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'iNaturalist Map Source', 'field-observation-showcase' ) },
						el( TextControl, {
							label: __( 'Project slug', 'field-observation-showcase' ),
							value: attributes.projectSlug,
							onChange: function ( value ) {
								props.setAttributes( { projectSlug: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Project ID fallback', 'field-observation-showcase' ),
							type: 'number',
							value: attributes.projectId,
							onChange: function ( value ) {
								props.setAttributes( { projectId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: __( 'Place ID', 'field-observation-showcase' ),
							type: 'number',
							value: attributes.placeId,
							onChange: function ( value ) {
								props.setAttributes( { placeId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: __( 'User ID or login', 'field-observation-showcase' ),
							value: attributes.userId,
							onChange: function ( value ) {
								props.setAttributes( { userId: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Mapped observations', 'field-observation-showcase' ),
							type: 'number',
							value: attributes.perPage,
							onChange: function ( value ) {
								var count = parseInt( value, 10 ) || 200;
								props.setAttributes( { perPage: Math.min( Math.max( count, 1 ), maxPerPage ) } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Open iNaturalist links in a new tab', 'field-observation-showcase' ),
							checked: attributes.openLinksInNewTab,
							onChange: function ( value ) {
								props.setAttributes( { openLinksInNewTab: value } );
							}
						} )
					)
				),
				el( MapServerSidePreview, {
					block: 'field-observation-showcase/observations-map',
					attributes: attributes,
					LoadingResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: __( 'iNaturalist Observations Map', 'field-observation-showcase' ) },
							el( Spinner ),
							el( 'span', {}, __( 'Loading observation map...', 'field-observation-showcase' ) )
						);
					},
					ErrorResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: __( 'iNaturalist Observations Map', 'field-observation-showcase' ) },
							el( 'span', {}, __( 'Unable to preview the map. Check the source settings and try again.', 'field-observation-showcase' ) )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender, window.wp.i18n );
