( function ( blocks, element, components, blockEditor, serverSideRender ) {
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
	var settings = window.natureINatObservations || {};
	var maxPerPage = settings.maxPerPage || 200;
	var openLinksInNewTab = settings.openLinksInNewTab !== false;

	function initPreviewMaps( root ) {
		if ( window.natureINatInitMaps ) {
			window.natureINatInitMaps( root );
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

	blocks.registerBlockType( 'nature-inat/observations', {
		title: 'iNaturalist Observations',
		icon: 'visibility',
		category: 'widgets',
		attributes: {
			projectId: {
				type: 'number',
				default: 3234
			},
			projectSlug: {
				type: 'string',
				default: 'stunt-ranch-santa-monica-mountains-reserve'
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
				default: 100
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
						{ title: 'iNaturalist Source' },
						el( TextControl, {
							label: 'Project slug',
							value: attributes.projectSlug,
							onChange: function ( value ) {
								props.setAttributes( { projectSlug: value } );
							}
						} ),
						el( TextControl, {
							label: 'Project ID fallback',
							type: 'number',
							value: attributes.projectId,
							onChange: function ( value ) {
								props.setAttributes( { projectId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: 'Place ID',
							type: 'number',
							value: attributes.placeId,
							onChange: function ( value ) {
								props.setAttributes( { placeId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: 'User ID or login',
							value: attributes.userId,
							onChange: function ( value ) {
								props.setAttributes( { userId: value } );
							}
						} ),
						el( TextControl, {
							label: 'Observations per page',
							type: 'number',
							value: attributes.perPage,
							onChange: function ( value ) {
								var count = parseInt( value, 10 ) || 100;
								props.setAttributes( { perPage: Math.min( Math.max( count, 1 ), maxPerPage ) } );
							}
						} ),
						el( ToggleControl, {
							label: 'Open iNaturalist links in a new tab',
							checked: attributes.openLinksInNewTab,
							onChange: function ( value ) {
								props.setAttributes( { openLinksInNewTab: value } );
							}
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nature-inat/observations',
					attributes: attributes,
					LoadingResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: 'iNaturalist Observations' },
							el( Spinner ),
							el( 'span', {}, 'Loading observations...' )
						);
					},
					ErrorResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: 'iNaturalist Observations' },
							el( 'span', {}, 'Unable to preview observations. Check the source settings and try again.' )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );

	blocks.registerBlockType( 'nature-inat/observations-map', {
		title: 'iNaturalist Observations Map',
		icon: 'location-alt',
		category: 'widgets',
		attributes: {
			projectId: {
				type: 'number',
				default: 3234
			},
			projectSlug: {
				type: 'string',
				default: 'stunt-ranch-santa-monica-mountains-reserve'
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
						{ title: 'iNaturalist Map Source' },
						el( TextControl, {
							label: 'Project slug',
							value: attributes.projectSlug,
							onChange: function ( value ) {
								props.setAttributes( { projectSlug: value } );
							}
						} ),
						el( TextControl, {
							label: 'Project ID fallback',
							type: 'number',
							value: attributes.projectId,
							onChange: function ( value ) {
								props.setAttributes( { projectId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: 'Place ID',
							type: 'number',
							value: attributes.placeId,
							onChange: function ( value ) {
								props.setAttributes( { placeId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( TextControl, {
							label: 'User ID or login',
							value: attributes.userId,
							onChange: function ( value ) {
								props.setAttributes( { userId: value } );
							}
						} ),
						el( TextControl, {
							label: 'Mapped observations',
							type: 'number',
							value: attributes.perPage,
							onChange: function ( value ) {
								var count = parseInt( value, 10 ) || 200;
								props.setAttributes( { perPage: Math.min( Math.max( count, 1 ), maxPerPage ) } );
							}
						} ),
						el( ToggleControl, {
							label: 'Open iNaturalist links in a new tab',
							checked: attributes.openLinksInNewTab,
							onChange: function ( value ) {
								props.setAttributes( { openLinksInNewTab: value } );
							}
						} )
					)
				),
				el( MapServerSidePreview, {
					block: 'nature-inat/observations-map',
					attributes: attributes,
					LoadingResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: 'iNaturalist Observations Map' },
							el( Spinner ),
							el( 'span', {}, 'Loading observation map...' )
						);
					},
					ErrorResponsePlaceholder: function () {
						return el(
							Placeholder,
							{ label: 'iNaturalist Observations Map' },
							el( 'span', {}, 'Unable to preview the map. Check the source settings and try again.' )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender );
