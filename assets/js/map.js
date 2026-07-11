( function () {
	function colorForGroup( group ) {
		var colors = {
			Birds: '#1e88e5',
			Mammals: '#8e44ad',
			Plants: '#5ca904',
			Insects: '#f4511e',
			Fungi: '#d81b60',
			Reptilia: '#00897b',
			Amphibia: '#6d9f71'
		};

		return colors[ group ] || '#5ca904';
	}

	function escapeHtml( value ) {
		return String( value || '' ).replace( /[&<>"']/g, function ( character ) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			}[ character ];
		} );
	}

	function escapeAttribute( value ) {
		return escapeHtml( value ).replace( /`/g, '&#096;' );
	}

	function popupHtml( observation, openLinksInNewTab, viewLabel, newTabLabel ) {
		var html = '';

		if ( observation.photo_url ) {
			html += '<img class="field-observation-showcase-map__popup-image" src="' + escapeAttribute( observation.photo_url ) + '" alt="">';
		}

		html += '<strong>' + escapeHtml( observation.common_name ) + '</strong>';

		if ( observation.scientific_name ) {
			html += '<em>' + escapeHtml( observation.scientific_name ) + '</em>';
		}

		if ( observation.observed_on ) {
			html += '<span>' + escapeHtml( observation.observed_on ) + '</span>';
		}

		if ( observation.url ) {
			html += '<a href="' + escapeAttribute( observation.url ) + '"';

			if ( openLinksInNewTab ) {
				html += ' target="_blank" rel="noopener noreferrer"';
			}

			html += '>' + escapeHtml( viewLabel );

			if ( openLinksInNewTab ) {
				html += '<span class="screen-reader-text"> ' + escapeHtml( newTabLabel ) + '</span>';
			}

			html += '</a>';
		}

		return html;
	}

	function initMap( wrapper ) {
		var canvas = wrapper.querySelector( '.field-observation-showcase-map__canvas' );
		var observations;
		var boundary;
		var map;
		var bounds = [];
		var boundaryLayer;
		var activeMarker;
		var markerById = {};
		var openLinksInNewTab = wrapper.getAttribute( 'data-open-links-new-tab' ) === 'true';
		var viewLabel = wrapper.getAttribute( 'data-view-label' ) || 'View observation';
		var newTabLabel = wrapper.getAttribute( 'data-new-tab-label' ) || 'opens in a new tab';

		if ( wrapper.dataset.mapInitialized || ! canvas || typeof window.L === 'undefined' ) {
			return;
		}

		wrapper.dataset.mapInitialized = 'true';

		try {
			observations = JSON.parse( wrapper.getAttribute( 'data-observations' ) || '[]' );
		} catch ( error ) {
			observations = [];
		}

		try {
			boundary = JSON.parse( wrapper.getAttribute( 'data-boundary' ) || '{}' );
		} catch ( error ) {
			boundary = {};
		}

		if ( ! observations.length && ! ( boundary && boundary.geometry ) ) {
			return;
		}

		map = window.L.map( canvas, {
			scrollWheelZoom: false
		} );

		map.createPane( 'natureReserveBoundary' );
		map.getPane( 'natureReserveBoundary' ).style.zIndex = 350;

		window.L.tileLayer( 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
			maxZoom: 19,
			attribution: 'Tiles &copy; Esri'
		} ).addTo( map );

		if ( boundary && boundary.geometry ) {
			boundaryLayer = window.L.geoJSON( boundary.geometry, {
				pane: 'natureReserveBoundary',
				style: {
					color: '#f0a000',
					fillColor: '#d69a18',
					fillOpacity: 0.42,
					opacity: 1,
					weight: 2
				}
			} ).addTo( map );
		}

		observations.forEach( function ( observation ) {
			var latLng = [ observation.lat, observation.lng ];
			var marker;

			bounds.push( latLng );

			marker = window.L.circleMarker( latLng, {
				radius: 6,
				color: '#fff',
				weight: 2.5,
				fillColor: colorForGroup( observation.taxon_group ),
				fillOpacity: 0.95
			} )
				.addTo( map )
				.bindPopup( popupHtml( observation, openLinksInNewTab, viewLabel, newTabLabel ) );

			markerById[ observation.id ] = marker;
		} );

		wrapper.addEventListener( 'field-observation-showcase-observation-select', function ( event ) {
			var marker = markerById[ event.detail.id ];

			if ( ! marker ) {
				return;
			}

			if ( activeMarker && activeMarker !== marker ) {
				activeMarker.setStyle( {
					radius: 6,
					weight: 2.5
				} );
			}

			activeMarker = marker;
			marker.setStyle( {
				radius: 9,
				weight: 4
			} );
			map.panTo( marker.getLatLng(), { animate: true } );
			marker.openPopup();
		} );

		if ( boundaryLayer ) {
			map.fitBounds( boundaryLayer.getBounds(), { padding: [ 24, 24 ] } );
		} else if ( bounds.length === 1 ) {
			map.setView( bounds[0], 14 );
		} else {
			map.fitBounds( bounds, { padding: [ 28, 28 ] } );
		}

		setTimeout( function () {
			map.invalidateSize();
		}, 80 );
	}

	function init( root ) {
		var scope = root || document;
		var wrappers = [];

		if ( scope.matches && scope.matches( '.field-observation-showcase-map[data-observations]' ) ) {
			wrappers.push( scope );
		}

		scope.querySelectorAll( '.field-observation-showcase-map[data-observations]' ).forEach( function ( wrapper ) {
			wrappers.push( wrapper );
		} );

		wrappers.forEach( function ( wrapper ) {
			initMap( wrapper );
		} );
	}

	window.fieldObservationShowcaseInitMaps = init;

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init();
		} );
	} else {
		init();
	}
}() );
