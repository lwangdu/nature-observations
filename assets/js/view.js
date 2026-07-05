import { store, getContext, getElement, withSyncEvent } from '@wordpress/interactivity';

var prefetchedUrls = new Set();
var mapStore;

function getRail( context ) {
	return context.railId ? document.getElementById( context.railId ) : null;
}

function updateButtons( context, rail ) {
	var maxScroll;

	if ( ! rail ) {
		context.hasPrevious = false;
		context.hasNext = false;
		return;
	}

	maxScroll = Math.max( 0, rail.scrollWidth - rail.clientWidth );

	context.hasPrevious = rail.scrollLeft > 2;
	context.hasNext = rail.scrollLeft < maxScroll - 2;
}

function scrollByPage( direction ) {
	var context = getContext();
	var rail = getRail( context );
	var amount;

	if ( ! rail ) {
		return;
	}

	amount = Math.max( 120, rail.clientWidth - 48 );

	rail.scrollBy( {
		left: amount * direction,
		behavior: 'smooth'
	} );
}

function selectObservation() {
	var context = getContext();
	var element = getElement();
	var observationId = parseInt( context.observationId, 10 ) || 0;

	if ( ! observationId || ! element.ref ) {
		return;
	}

	mapStore.state.activeObservationId = observationId;
	element.ref.dispatchEvent(
		new CustomEvent( 'nature-inat-observation-select', {
			bubbles: true,
			detail: {
				id: observationId
			}
		} )
	);
}

function paginationUrlFromElement() {
	var element = getElement();

	if ( ! element.ref || ! element.ref.href ) {
		return '';
	}

	return element.ref.href;
}

function prefetchPaginationPage() {
	var link;
	var url = paginationUrlFromElement();

	if ( ! url || prefetchedUrls.has( url ) ) {
		return;
	}

	prefetchedUrls.add( url );
	link = document.createElement( 'link' );
	link.rel = 'prefetch';
	link.href = url;
	link.as = 'document';
	document.head.appendChild( link );
}

mapStore = store( 'nature-inat/observations-map', {
	state: {
		activeObservationId: 0,
		get isPreviousDisabled() {
			return ! getContext().hasPrevious;
		},
		get isNextDisabled() {
			return ! getContext().hasNext;
		},
		get isActiveObservation() {
			var context = getContext();

			return Boolean( context.observationId && mapStore.state.activeObservationId === context.observationId );
		},
		get activeObservationAriaCurrent() {
			var context = getContext();

			return context.observationId && mapStore.state.activeObservationId === context.observationId ? 'true' : null;
		}
	},
	actions: {
		scrollPrevious: withSyncEvent( function () {
			scrollByPage( -1 );
		} ),
		scrollNext: withSyncEvent( function () {
			scrollByPage( 1 );
		} ),
		selectObservation: withSyncEvent( function () {
			selectObservation();
		} )
	},
	callbacks: {
		initCarousel: function () {
			var context = getContext();
			var element = getElement();
			var rail = getRail( context );
			var update;

			if ( ! element.ref || ! rail ) {
				return;
			}

			update = function () {
				updateButtons( context, rail );
			};

			rail.addEventListener( 'scroll', update, { passive: true } );
			window.addEventListener( 'resize', update );
			update();
			setTimeout( update, 120 );

			return function () {
				rail.removeEventListener( 'scroll', update );
				window.removeEventListener( 'resize', update );
			};
		}
	}
} );

store( 'nature-inat/observations', {
	state: {
		get isPaginationLoading() {
			return Boolean( getContext().isLoading );
		}
	},
	actions: {
		prefetchPaginationPage: withSyncEvent( function () {
			prefetchPaginationPage();
		} ),
		setPaginationLoading: withSyncEvent( function ( event ) {
			var context = getContext();

			if ( event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}

			context.isLoading = true;
		} )
	}
} );
