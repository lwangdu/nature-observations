<?php
/**
 * API cache and normalizers for iNaturalist.
 *
 * @package Nature_INat_Observations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches, normalizes, and caches iNaturalist API responses.
 */
final class Nature_INat_Observations_Cache {
	const API_BASE          = 'https://api.inaturalist.org/v1/observations';
	const PROJECTS_API_BASE = 'https://api.inaturalist.org/v1/projects';
	const PLACES_API_BASE   = 'https://api.inaturalist.org/v1/places';
	const MAX_PER_PAGE      = 200;
	const MAX_MAP_MARKERS   = 1000;
	const LOCK_TTL          = 60;

	/**
	 * Get available observation group filters.
	 *
	 * @return array
	 */
	public static function group_options() {
		return array(
			''        => __( 'All', 'nature-inat-observations' ),
			'birds'   => __( 'Birds', 'nature-inat-observations' ),
			'mammals' => __( 'Mammals', 'nature-inat-observations' ),
			'plants'  => __( 'Plants', 'nature-inat-observations' ),
			'insects' => __( 'Insects', 'nature-inat-observations' ),
			'fungi'   => __( 'Fungi', 'nature-inat-observations' ),
		);
	}

	/**
	 * Map filter keys to iNaturalist iconic taxa.
	 *
	 * @return array
	 */
	public static function iconic_taxa() {
		return array(
			'birds'   => 'Aves',
			'mammals' => 'Mammalia',
			'plants'  => 'Plantae',
			'insects' => 'Insecta',
			'fungi'   => 'Fungi',
		);
	}

	/**
	 * Sanitize an observation group filter.
	 *
	 * @param string $group Group key.
	 * @return string
	 */
	public static function sanitize_group( $group ) {
		$group = sanitize_key( $group );

		return array_key_exists( $group, self::group_options() ) ? $group : '';
	}

	/**
	 * Get observations from iNaturalist.
	 *
	 * @param array $args Query arguments.
	 * @return array|WP_Error
	 */
	public static function get_observations( $args = array() ) {
		$options = Nature_INat_Observations_Admin::get_options();
		$args    = self::normalize_query_args( $args, $options );
		$args    = self::resolve_project_slug_arg( $args );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$cache_key = 'nature_inat_v4_' . md5( wp_json_encode( $args ) );

		return self::cached_result(
			$cache_key,
			absint( $options['cache_ttl'] ),
			function () use ( $args ) {
				$query = array_merge(
					self::source_query_args( $args ),
					array(
						'per_page' => $args['per_page'],
						'page'     => $args['page'],
						'photos'   => 'true',
						'order'    => 'desc',
						'order_by' => 'observed_on',
					)
				);

				if ( ! empty( $args['geo'] ) ) {
					$query['geo'] = 'true';
				}

				$data = self::request_json( add_query_arg( $query, self::API_BASE ) );
				if ( is_wp_error( $data ) ) {
					return $data;
				}

				if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
					return new WP_Error( 'nature_inat_bad_response', __( 'The iNaturalist response was not readable.', 'nature-inat-observations' ) );
				}

				return array(
					'total_results' => absint( $data['total_results'] ?? 0 ),
					'page'          => absint( $data['page'] ?? $args['page'] ),
					'per_page'      => absint( $data['per_page'] ?? $args['per_page'] ),
					'results'       => array_map( array( __CLASS__, 'normalize_observation' ), $data['results'] ),
				);
			}
		);
	}

	/**
	 * Get all-time source stats from iNaturalist.
	 *
	 * @param array $args Query arguments.
	 * @return array|WP_Error
	 */
	public static function get_source_stats( $args = array() ) {
		$options = Nature_INat_Observations_Admin::get_options();
		$args    = self::normalize_query_args( $args, $options );
		$args    = self::resolve_project_slug_arg( $args );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$cache_key = 'nature_inat_stats_v2_' . md5( wp_json_encode( $args ) );

		return self::cached_result(
			$cache_key,
			absint( $options['cache_ttl'] ),
			function () use ( $args ) {
				$query = array_merge(
					self::source_query_args( $args ),
					array(
						'per_page' => 0,
					)
				);

				$observations = self::request_count( self::API_BASE, $query );
				if ( is_wp_error( $observations ) ) {
					return $observations;
				}

				$species = self::request_count( self::API_BASE . '/species_counts', $query );
				if ( is_wp_error( $species ) ) {
					return $species;
				}

				$identifiers = self::request_count( self::API_BASE . '/identifiers', $query );
				if ( is_wp_error( $identifiers ) ) {
					return $identifiers;
				}

				$observers = self::request_count( self::API_BASE . '/observers', $query );
				if ( is_wp_error( $observers ) ) {
					return $observers;
				}

				return array(
					'observations' => $observations,
					'species'      => $species,
					'identifiers'  => $identifiers,
					'observers'    => $observers,
					'url'          => self::inat_url( $args ),
					'label'        => self::source_label( $args ),
				);
			}
		);
	}

	/**
	 * Get the source place boundary.
	 *
	 * @param array $args Query arguments.
	 * @return array|WP_Error
	 */
	public static function get_source_boundary( $args = array() ) {
		$options = Nature_INat_Observations_Admin::get_options();
		$args    = self::normalize_query_args( $args, $options );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$cache_key = 'nature_inat_boundary_v1_' . md5( wp_json_encode( $args ) );

		return self::cached_result(
			$cache_key,
			DAY_IN_SECONDS,
			function () use ( $args ) {
				$place_id = self::boundary_place_id( $args );
				if ( is_wp_error( $place_id ) ) {
					return $place_id;
				}

				if ( ! $place_id ) {
					return new WP_Error( 'nature_inat_boundary_missing', __( 'No iNaturalist place boundary was found for this source.', 'nature-inat-observations' ) );
				}

				$place = self::place_from_id( $place_id );
				if ( is_wp_error( $place ) ) {
					return $place;
				}

				$geometry = $place['geometry_geojson'] ?? array();
				if ( empty( $geometry ) || ! is_array( $geometry ) ) {
					$geometry = $place['bounding_box_geojson'] ?? array();
				}

				if ( empty( $geometry ) || ! is_array( $geometry ) ) {
					return new WP_Error( 'nature_inat_boundary_unavailable', __( 'The iNaturalist place boundary was not available.', 'nature-inat-observations' ) );
				}

				return array(
					'place_id' => absint( $place_id ),
					'label'    => sanitize_text_field( $place['display_name'] ?? $place['name'] ?? '' ),
					'geometry' => $geometry,
				);
			}
		);
	}

	/**
	 * Clear plugin transients.
	 *
	 * @return int
	 */
	public static function clear_cache() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_nature_inat_%'
			OR option_name LIKE '_transient_timeout_nature_inat_%'"
		);

		return false === $deleted ? 0 : absint( $deleted );
	}

	/**
	 * Warm the default source caches in the background.
	 */
	public static function warm_default_cache() {
		$options = Nature_INat_Observations_Admin::get_options();
		$args    = array(
			'project_id'   => $options['project_id'],
			'project_slug' => $options['project_slug'],
			'place_id'     => 0,
			'user_id'      => '',
			'per_page'     => $options['per_page'],
			'page'         => 1,
			'group'        => '',
		);

		self::get_source_stats( $args );
		self::get_source_boundary( $args );
		self::get_observations( $args );

		$map_args             = $args;
		$map_args['geo']      = true;
		$map_args['per_page'] = self::MAX_PER_PAGE;

		$first_page = self::get_observations( $map_args );
		if ( is_wp_error( $first_page ) ) {
			return;
		}

		$total_results = absint( $first_page['total_results'] ?? 0 );
		$per_page      = max( 1, absint( $first_page['per_page'] ?? self::MAX_PER_PAGE ) );
		$max_results   = min( self::MAX_MAP_MARKERS, $total_results );
		$max_pages     = (int) ceil( $max_results / $per_page );

		for ( $page = 2; $page <= $max_pages; $page++ ) {
			$map_args['page'] = $page;
			$page_data        = self::get_observations( $map_args );

			if ( is_wp_error( $page_data ) ) {
				return;
			}
		}
	}

	/**
	 * Get stats for observations displayed on the current page.
	 *
	 * @param array $data Observation data.
	 * @return array
	 */
	public static function displayed_stats( $data ) {
		$results   = $data['results'] ?? array();
		$species   = array();
		$observers = array();

		foreach ( $results as $observation ) {
			if ( ! empty( $observation['scientific_name'] ) ) {
				$species[ $observation['scientific_name'] ] = true;
			}

			if ( ! empty( $observation['observer'] ) ) {
				$observers[ $observation['observer'] ] = true;
			}
		}

		return array(
			'observations' => count( $results ),
			'species'      => count( $species ),
			'observers'    => count( $observers ),
		);
	}

	/**
	 * Normalize one iNaturalist observation record.
	 *
	 * @param array $observation Raw observation.
	 * @return array
	 */
	private static function normalize_observation( $observation ) {
		$taxon           = is_array( $observation['taxon'] ?? null ) ? $observation['taxon'] : array();
		$user            = is_array( $observation['user'] ?? null ) ? $observation['user'] : array();
		$photo           = is_array( $observation['photos'][0] ?? null ) ? $observation['photos'][0] : array();
		$scientific_name = sanitize_text_field( $taxon['name'] ?? '' );
		$species_guess   = sanitize_text_field( $observation['species_guess'] ?? '' );
		$common_name     = sanitize_text_field( $taxon['preferred_common_name'] ?? '' );
		$observer        = sanitize_text_field( $user['name'] ?? '' );

		if ( '' === $observer ) {
			$observer = sanitize_text_field( $user['login'] ?? __( 'Unknown observer', 'nature-inat-observations' ) );
		}

		if ( '' === $common_name ) {
			$common_name = '' !== $species_guess ? $species_guess : $scientific_name;
		}

		if ( '' === $common_name ) {
			$common_name = __( 'Unknown species', 'nature-inat-observations' );
		}

		return array(
			'id'              => absint( $observation['id'] ?? 0 ),
			'url'             => esc_url_raw( $observation['uri'] ?? '' ),
			'photo_url'       => self::photo_url( $photo['url'] ?? '' ),
			'photo_alt'       => self::photo_alt( $common_name, $scientific_name ),
			'taxon_group'     => self::taxon_group_label( $taxon['iconic_taxon_name'] ?? '' ),
			'common_name'     => $common_name,
			'scientific_name' => $scientific_name,
			'observed_on'     => sanitize_text_field( $observation['observed_on'] ?? '' ),
			'observer'        => $observer,
			'quality_grade'   => self::quality_grade( $observation['quality_grade'] ?? '' ),
			'latitude'        => self::coordinate( $observation, 'lat' ),
			'longitude'       => self::coordinate( $observation, 'lng' ),
		);
	}

	/**
	 * Normalize query arguments.
	 *
	 * @param array $args    Raw arguments.
	 * @param array $options Plugin options.
	 * @return array
	 */
	private static function normalize_query_args( $args, $options ) {
		$args = wp_parse_args(
			$args,
			array(
				'project_id'   => $options['project_id'],
				'project_slug' => '',
				'place_id'     => 0,
				'user_id'      => '',
				'per_page'     => $options['per_page'],
				'page'         => 1,
				'group'        => '',
				'geo'          => false,
			)
		);

		$normalized = array(
			'project_id'   => absint( $args['project_id'] ),
			'project_slug' => sanitize_title( $args['project_slug'] ),
			'place_id'     => absint( $args['place_id'] ),
			'user_id'      => sanitize_text_field( $args['user_id'] ),
			'per_page'     => min( self::MAX_PER_PAGE, max( 1, absint( $args['per_page'] ) ) ),
			'page'         => max( 1, absint( $args['page'] ) ),
			'group'        => self::sanitize_group( $args['group'] ),
			'geo'          => ! empty( $args['geo'] ),
		);

		if ( ! $normalized['project_id'] && '' === $normalized['project_slug'] && ! $normalized['place_id'] && '' === $normalized['user_id'] ) {
			$normalized['project_id'] = max( 1, absint( $options['project_id'] ) );
		}

		return $normalized;
	}

	/**
	 * Resolve a project slug to a numeric project ID.
	 *
	 * @param array $args Query arguments.
	 * @return array|WP_Error
	 */
	private static function resolve_project_slug_arg( $args ) {
		if ( '' === $args['project_slug'] ) {
			return $args;
		}

		$project_id = self::project_id_from_slug( $args['project_slug'] );
		if ( is_wp_error( $project_id ) ) {
			if ( $args['project_id'] ) {
				$args['project_slug'] = '';

				return $args;
			}

			return $project_id;
		}

		$args['project_id'] = $project_id;

		return $args;
	}

	/**
	 * Get a project ID from a project slug.
	 *
	 * @param string $project_slug Project slug.
	 * @return int|WP_Error
	 */
	private static function project_id_from_slug( $project_slug ) {
		$project = self::project_from_slug( $project_slug );

		if ( is_wp_error( $project ) ) {
			return $project;
		}

		return absint( $project['id'] ?? 0 );
	}

	/**
	 * Get project data from a slug.
	 *
	 * @param string $project_slug Project slug.
	 * @return array|WP_Error
	 */
	private static function project_from_slug( $project_slug ) {
		$cache_key = 'nature_inat_project_slug_' . md5( $project_slug );

		return self::cached_result(
			$cache_key,
			DAY_IN_SECONDS,
			function () use ( $project_slug ) {
				$data = self::request_json( trailingslashit( self::PROJECTS_API_BASE ) . rawurlencode( $project_slug ) );
				if ( is_wp_error( $data ) ) {
					return $data;
				}

				$project = $data['results'][0] ?? array();
				if ( empty( $project['id'] ) ) {
					return new WP_Error(
						'nature_inat_project_not_found',
						__( 'The iNaturalist project slug was not found.', 'nature-inat-observations' )
					);
				}

				return $project;
			}
		);
	}

	/**
	 * Get project data from an ID.
	 *
	 * @param int $project_id Project ID.
	 * @return array|WP_Error
	 */
	private static function project_from_id( $project_id ) {
		$cache_key = 'nature_inat_project_id_' . absint( $project_id );

		return self::cached_result(
			$cache_key,
			DAY_IN_SECONDS,
			function () use ( $project_id ) {
				$data = self::request_json( trailingslashit( self::PROJECTS_API_BASE ) . absint( $project_id ) );
				if ( is_wp_error( $data ) ) {
					return $data;
				}

				$project = $data['results'][0] ?? array();
				if ( empty( $project['id'] ) ) {
					return new WP_Error(
						'nature_inat_project_not_found',
						__( 'The iNaturalist project was not found.', 'nature-inat-observations' )
					);
				}

				return $project;
			}
		);
	}

	/**
	 * Get place data from an ID.
	 *
	 * @param int $place_id Place ID.
	 * @return array|WP_Error
	 */
	private static function place_from_id( $place_id ) {
		$cache_key = 'nature_inat_place_' . absint( $place_id );

		return self::cached_result(
			$cache_key,
			DAY_IN_SECONDS,
			function () use ( $place_id ) {
				$data = self::request_json( trailingslashit( self::PLACES_API_BASE ) . absint( $place_id ) );
				if ( is_wp_error( $data ) ) {
					return $data;
				}

				$place = $data['results'][0] ?? array();
				if ( empty( $place['id'] ) ) {
					return new WP_Error(
						'nature_inat_place_not_found',
						__( 'The iNaturalist place was not found.', 'nature-inat-observations' )
					);
				}

				return $place;
			}
		);
	}

	/**
	 * Resolve the source place ID used for reserve boundaries.
	 *
	 * @param array $args Query arguments.
	 * @return int|WP_Error
	 */
	private static function boundary_place_id( $args ) {
		if ( ! empty( $args['place_id'] ) ) {
			return absint( $args['place_id'] );
		}

		if ( '' !== $args['project_slug'] ) {
			$project = self::project_from_slug( $args['project_slug'] );
		} elseif ( ! empty( $args['project_id'] ) ) {
			$project = self::project_from_id( $args['project_id'] );
		} else {
			return 0;
		}

		if ( is_wp_error( $project ) ) {
			return $project;
		}

		if ( ! empty( $project['place_id'] ) ) {
			return absint( $project['place_id'] );
		}

		$rules = isset( $project['project_observation_rules'] ) && is_array( $project['project_observation_rules'] ) ? $project['project_observation_rules'] : array();
		foreach ( $rules as $rule ) {
			if ( 'Place' === ( $rule['operand_type'] ?? '' ) && ! empty( $rule['operand_id'] ) ) {
				return absint( $rule['operand_id'] );
			}
		}

		return 0;
	}

	/**
	 * Return a cached value or build it behind a short-lived lock.
	 *
	 * @param string   $cache_key Cache key.
	 * @param int      $ttl       Cache lifetime in seconds.
	 * @param callable $callback  Cache builder.
	 * @return mixed|WP_Error
	 */
	private static function cached_result( $cache_key, $ttl, $callback ) {
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$lock_key = self::acquire_cache_lock( $cache_key );
		if ( is_wp_error( $lock_key ) ) {
			if ( 'nature_inat_cache_filled' === $lock_key->get_error_code() ) {
				return $lock_key->get_error_data();
			}

			return $lock_key;
		}

		try {
			$result = call_user_func( $callback );

			if ( ! is_wp_error( $result ) ) {
				set_transient( $cache_key, $result, $ttl );
			}

			return $result;
		} finally {
			delete_transient( $lock_key );
		}
	}

	/**
	 * Acquire a cache refresh lock, waiting briefly for another request to finish.
	 *
	 * @param string $cache_key Cache key.
	 * @return string|WP_Error
	 */
	private static function acquire_cache_lock( $cache_key ) {
		$lock_key = 'nature_inat_lock_' . md5( $cache_key );

		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			if ( false === get_transient( $lock_key ) ) {
				set_transient( $lock_key, 1, self::LOCK_TTL );

				return $lock_key;
			}

			usleep( 250000 );

			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return new WP_Error( 'nature_inat_cache_filled', '', $cached );
			}
		}

		return new WP_Error( 'nature_inat_cache_busy', __( 'The iNaturalist cache is refreshing. Please try again shortly.', 'nature-inat-observations' ) );
	}

	/**
	 * Request JSON from an iNaturalist endpoint.
	 *
	 * @param string $url Endpoint URL.
	 * @return array|WP_Error
	 */
	private static function request_json( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'user-agent'  => 'Nature iNaturalist Observations/' . NATURE_INAT_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return new WP_Error(
				'nature_inat_api_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'iNaturalist returned HTTP %d.', 'nature-inat-observations' ),
					$status
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'nature_inat_bad_response', __( 'The iNaturalist response was not readable.', 'nature-inat-observations' ) );
		}

		return $data;
	}

	/**
	 * Build iNaturalist source query arguments.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	private static function source_query_args( $args ) {
		$query = array();

		if ( $args['project_id'] ) {
			$query['project_id'] = $args['project_id'];
		}

		if ( $args['place_id'] ) {
			$query['place_id'] = $args['place_id'];
		}

		if ( '' !== $args['user_id'] ) {
			$query['user_id'] = $args['user_id'];
		}

		$iconic_taxa = self::iconic_taxa();
		if ( isset( $iconic_taxa[ $args['group'] ] ) ) {
			$query['iconic_taxa'] = $iconic_taxa[ $args['group'] ];
		}

		return $query;
	}

	/**
	 * Request a count from an iNaturalist endpoint.
	 *
	 * @param string $endpoint Endpoint URL.
	 * @param array  $query    Query arguments.
	 * @return int|WP_Error
	 */
	private static function request_count( $endpoint, $query ) {
		$url      = add_query_arg( $query, $endpoint );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'user-agent'  => 'Nature iNaturalist Observations/' . NATURE_INAT_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return new WP_Error(
				'nature_inat_api_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'iNaturalist returned HTTP %d.', 'nature-inat-observations' ),
					$status
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return absint( $data['total_results'] ?? 0 );
	}

	/**
	 * Get a human-readable source label.
	 *
	 * @param array $args Query arguments.
	 * @return string
	 */
	private static function source_label( $args ) {
		if ( '' !== $args['project_slug'] ) {
			$project = self::project_from_slug( $args['project_slug'] );

			if ( ! is_wp_error( $project ) && ! empty( $project['title'] ) ) {
				return sanitize_text_field( $project['title'] );
			}
		}

		if ( $args['project_id'] ) {
			$label = self::entity_label( trailingslashit( self::PROJECTS_API_BASE ) . absint( $args['project_id'] ), 'title' );

			if ( '' !== $label ) {
				return $label;
			}
		}

		if ( $args['place_id'] ) {
			$label = self::entity_label( trailingslashit( self::PLACES_API_BASE ) . absint( $args['place_id'] ), 'display_name' );

			if ( '' !== $label ) {
				return $label;
			}
		}

		if ( '' !== $args['user_id'] ) {
			return sprintf(
				/* translators: %s: iNaturalist user ID or login. */
				__( 'iNaturalist user %s', 'nature-inat-observations' ),
				$args['user_id']
			);
		}

		return __( 'iNaturalist', 'nature-inat-observations' );
	}

	/**
	 * Get an entity label from an iNaturalist endpoint.
	 *
	 * @param string $url   Endpoint URL.
	 * @param string $field Preferred field.
	 * @return string
	 */
	private static function entity_label( $url, $field ) {
		$cache_key = 'nature_inat_label_' . md5( $url . '|' . $field );
		$label     = self::cached_result(
			$cache_key,
			DAY_IN_SECONDS,
			function () use ( $url, $field ) {
				$data = self::request_json( $url );
				if ( is_wp_error( $data ) ) {
					return '';
				}

				$entity = $data['results'][0] ?? $data;

				return sanitize_text_field( $entity[ $field ] ?? $entity['name'] ?? $entity['title'] ?? '' );
			}
		);

		return is_wp_error( $label ) ? '' : $label;
	}

	/**
	 * Build the iNaturalist source URL.
	 *
	 * @param array $args Query arguments.
	 * @return string
	 */
	private static function inat_url( $args ) {
		$query = self::source_query_args( $args );

		return esc_url_raw( add_query_arg( $query, 'https://www.inaturalist.org/observations' ) );
	}

	/**
	 * Convert iNaturalist photo URLs to medium size.
	 *
	 * @param string $url Photo URL.
	 * @return string
	 */
	private static function photo_url( $url ) {
		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return '';
		}

		return str_replace( array( '/square.', '/thumb.' ), '/medium.', $url );
	}

	/**
	 * Build image alt text.
	 *
	 * @param string $common_name     Common name.
	 * @param string $scientific_name Scientific name.
	 * @return string
	 */
	private static function photo_alt( $common_name, $scientific_name ) {
		$name = '' !== $common_name && __( 'Unknown species', 'nature-inat-observations' ) !== $common_name ? $common_name : $scientific_name;

		if ( '' === $name ) {
			return __( 'iNaturalist observation photo', 'nature-inat-observations' );
		}

		return sprintf(
			/* translators: %s: observation taxon name. */
			__( 'iNaturalist observation photo of %s', 'nature-inat-observations' ),
			$name
		);
	}

	/**
	 * Extract a latitude or longitude from an observation.
	 *
	 * @param array  $observation Raw observation.
	 * @param string $axis        Coordinate axis: lat or lng.
	 * @return float|null
	 */
	private static function coordinate( $observation, $axis ) {
		$coordinates = $observation['geojson']['coordinates'] ?? array();

		if ( is_array( $coordinates ) && count( $coordinates ) >= 2 ) {
			$value = 'lat' === $axis ? $coordinates[1] : $coordinates[0];

			return is_numeric( $value ) ? (float) $value : null;
		}

		if ( empty( $observation['location'] ) ) {
			return null;
		}

		$parts = array_map( 'trim', explode( ',', (string) $observation['location'] ) );
		if ( count( $parts ) < 2 ) {
			return null;
		}

		$value = 'lat' === $axis ? $parts[0] : $parts[1];

		return is_numeric( $value ) ? (float) $value : null;
	}

	/**
	 * Sanitize a quality grade.
	 *
	 * @param string $quality_grade Quality grade.
	 * @return string
	 */
	private static function quality_grade( $quality_grade ) {
		$quality_grade = sanitize_key( $quality_grade );

		if ( '' === $quality_grade ) {
			return 'unknown';
		}

		return $quality_grade;
	}

	/**
	 * Get a display label for an iconic taxon.
	 *
	 * @param string $iconic_taxon_name Iconic taxon name.
	 * @return string
	 */
	private static function taxon_group_label( $iconic_taxon_name ) {
		$labels = array(
			'Aves'     => __( 'Birds', 'nature-inat-observations' ),
			'Mammalia' => __( 'Mammals', 'nature-inat-observations' ),
			'Plantae'  => __( 'Plants', 'nature-inat-observations' ),
			'Insecta'  => __( 'Insects', 'nature-inat-observations' ),
			'Fungi'    => __( 'Fungi', 'nature-inat-observations' ),
			'Reptilia' => __( 'Reptilia', 'nature-inat-observations' ),
			'Amphibia' => __( 'Amphibia', 'nature-inat-observations' ),
		);

		return sanitize_text_field( $labels[ $iconic_taxon_name ] ?? $iconic_taxon_name );
	}
}
