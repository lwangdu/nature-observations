<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UCNature_INat_Observations_Cache {
	const API_BASE          = 'https://api.inaturalist.org/v1/observations';
	const PROJECTS_API_BASE = 'https://api.inaturalist.org/v1/projects';
	const PLACES_API_BASE   = 'https://api.inaturalist.org/v1/places';
	const MAX_PER_PAGE      = 200;

	public function __construct() {}

	public static function group_options() {
		return array(
			''        => __( 'All', 'ucnature-inat-observations' ),
			'birds'   => __( 'Birds', 'ucnature-inat-observations' ),
			'mammals' => __( 'Mammals', 'ucnature-inat-observations' ),
			'plants'  => __( 'Plants', 'ucnature-inat-observations' ),
			'insects' => __( 'Insects', 'ucnature-inat-observations' ),
			'fungi'   => __( 'Fungi', 'ucnature-inat-observations' ),
		);
	}

	public static function iconic_taxa() {
		return array(
			'birds'   => 'Aves',
			'mammals' => 'Mammalia',
			'plants'  => 'Plantae',
			'insects' => 'Insecta',
			'fungi'   => 'Fungi',
		);
	}

	public static function sanitize_group( $group ) {
		$group = sanitize_key( $group );

		return array_key_exists( $group, self::group_options() ) ? $group : '';
	}

	public static function get_observations( $args = array() ) {
		$options = UCNature_INat_Observations_Admin::get_options();
		$args    = self::normalize_query_args( $args, $options );
		$args    = self::resolve_project_slug_arg( $args );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$cache_key = 'ucnature_inat_v4_' . md5( wp_json_encode( $args ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

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

		$url      = add_query_arg( $query, self::API_BASE );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'user-agent'  => 'UCNature iNaturalist Observations/' . UCNATURE_INAT_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return new WP_Error(
				'ucnature_inat_api_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'iNaturalist returned HTTP %d.', 'ucnature-inat-observations' ),
					$status
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			return new WP_Error( 'ucnature_inat_bad_response', __( 'The iNaturalist response was not readable.', 'ucnature-inat-observations' ) );
		}

		$result = array(
			'total_results' => absint( $data['total_results'] ?? 0 ),
			'page'          => absint( $data['page'] ?? $args['page'] ),
			'per_page'      => absint( $data['per_page'] ?? $args['per_page'] ),
			'results'       => array_map( array( __CLASS__, 'normalize_observation' ), $data['results'] ),
		);

		set_transient( $cache_key, $result, absint( $options['cache_ttl'] ) );

		return $result;
	}

	public static function get_source_stats( $args = array() ) {
		$options = UCNature_INat_Observations_Admin::get_options();
		$args    = self::normalize_query_args( $args, $options );
		$args    = self::resolve_project_slug_arg( $args );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$cache_key = 'ucnature_inat_stats_v2_' . md5( wp_json_encode( $args ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			if ( ! is_array( $cached ) ) {
				return array(
					'id' => absint( $cached ),
				);
			}

			return $cached;
		}

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

		$result = array(
			'observations' => $observations,
			'species'      => $species,
			'identifiers'  => $identifiers,
			'observers'    => $observers,
			'url'          => self::inat_url( $args ),
			'label'        => self::source_label( $args ),
		);

		set_transient( $cache_key, $result, absint( $options['cache_ttl'] ) );

		return $result;
	}

	public static function clear_cache() {
		global $wpdb;

		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_ucnature_inat_%'
			OR option_name LIKE '_transient_timeout_ucnature_inat_%'"
		);

		return false === $deleted ? 0 : absint( $deleted );
	}

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

	private static function normalize_observation( $observation ) {
		$taxon           = is_array( $observation['taxon'] ?? null ) ? $observation['taxon'] : array();
		$user            = is_array( $observation['user'] ?? null ) ? $observation['user'] : array();
		$photo           = is_array( $observation['photos'][0] ?? null ) ? $observation['photos'][0] : array();
		$scientific_name = sanitize_text_field( $taxon['name'] ?? '' );
		$species_guess   = sanitize_text_field( $observation['species_guess'] ?? '' );
		$common_name     = sanitize_text_field( $taxon['preferred_common_name'] ?? '' );
		$observer        = sanitize_text_field( $user['name'] ?? '' );

		if ( '' === $observer ) {
			$observer = sanitize_text_field( $user['login'] ?? __( 'Unknown observer', 'ucnature-inat-observations' ) );
		}

		if ( '' === $common_name ) {
			$common_name = '' !== $species_guess ? $species_guess : $scientific_name;
		}

		if ( '' === $common_name ) {
			$common_name = __( 'Unknown species', 'ucnature-inat-observations' );
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
		);
	}

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
		);

		if ( ! $normalized['project_id'] && '' === $normalized['project_slug'] && ! $normalized['place_id'] && '' === $normalized['user_id'] ) {
			$normalized['project_id'] = max( 1, absint( $options['project_id'] ) );
		}

		return $normalized;
	}

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

	private static function project_id_from_slug( $project_slug ) {
		$project = self::project_from_slug( $project_slug );

		if ( is_wp_error( $project ) ) {
			return $project;
		}

		return absint( $project['id'] ?? 0 );
	}

	private static function project_from_slug( $project_slug ) {
		$cache_key = 'ucnature_inat_project_slug_' . md5( $project_slug );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$data = self::request_json( trailingslashit( self::PROJECTS_API_BASE ) . rawurlencode( $project_slug ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$project = $data['results'][0] ?? array();
		if ( empty( $project['id'] ) ) {
			return new WP_Error(
				'ucnature_inat_project_not_found',
				__( 'The iNaturalist project slug was not found.', 'ucnature-inat-observations' )
			);
		}

		set_transient( $cache_key, $project, DAY_IN_SECONDS );

		return $project;
	}

	private static function request_json( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'user-agent'  => 'UCNature iNaturalist Observations/' . UCNATURE_INAT_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return new WP_Error(
				'ucnature_inat_api_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'iNaturalist returned HTTP %d.', 'ucnature-inat-observations' ),
					$status
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'ucnature_inat_bad_response', __( 'The iNaturalist response was not readable.', 'ucnature-inat-observations' ) );
		}

		return $data;
	}

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

	private static function request_count( $endpoint, $query ) {
		$url      = add_query_arg( $query, $endpoint );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 2,
				'user-agent'  => 'UCNature iNaturalist Observations/' . UCNATURE_INAT_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return new WP_Error(
				'ucnature_inat_api_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'iNaturalist returned HTTP %d.', 'ucnature-inat-observations' ),
					$status
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return absint( $data['total_results'] ?? 0 );
	}

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
				__( 'iNaturalist user %s', 'ucnature-inat-observations' ),
				$args['user_id']
			);
		}

		return __( 'iNaturalist', 'ucnature-inat-observations' );
	}

	private static function entity_label( $url, $field ) {
		$cache_key = 'ucnature_inat_label_' . md5( $url . '|' . $field );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$data = self::request_json( $url );
		if ( is_wp_error( $data ) ) {
			return '';
		}

		$entity = $data['results'][0] ?? $data;
		$label  = sanitize_text_field( $entity[ $field ] ?? $entity['name'] ?? $entity['title'] ?? '' );

		set_transient( $cache_key, $label, DAY_IN_SECONDS );

		return $label;
	}

	private static function inat_url( $args ) {
		$query = self::source_query_args( $args );

		return esc_url_raw( add_query_arg( $query, 'https://www.inaturalist.org/observations' ) );
	}

	private static function photo_url( $url ) {
		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return '';
		}

		return str_replace( array( '/square.', '/thumb.' ), '/medium.', $url );
	}

	private static function photo_alt( $common_name, $scientific_name ) {
		$name = '' !== $common_name && __( 'Unknown species', 'ucnature-inat-observations' ) !== $common_name ? $common_name : $scientific_name;

		if ( '' === $name ) {
			return __( 'iNaturalist observation photo', 'ucnature-inat-observations' );
		}

		return sprintf(
			/* translators: %s: observation taxon name. */
			__( 'iNaturalist observation photo of %s', 'ucnature-inat-observations' ),
			$name
		);
	}

	private static function quality_grade( $quality_grade ) {
		$quality_grade = sanitize_key( $quality_grade );

		if ( '' === $quality_grade ) {
			return 'unknown';
		}

		return $quality_grade;
	}

	private static function taxon_group_label( $iconic_taxon_name ) {
		$labels = array(
			'Aves'     => __( 'Birds', 'ucnature-inat-observations' ),
			'Mammalia' => __( 'Mammals', 'ucnature-inat-observations' ),
			'Plantae'  => __( 'Plants', 'ucnature-inat-observations' ),
			'Insecta'  => __( 'Insects', 'ucnature-inat-observations' ),
			'Fungi'    => __( 'Fungi', 'ucnature-inat-observations' ),
			'Reptilia' => __( 'Reptilia', 'ucnature-inat-observations' ),
			'Amphibia' => __( 'Amphibia', 'ucnature-inat-observations' ),
		);

		return sanitize_text_field( $labels[ $iconic_taxon_name ] ?? $iconic_taxon_name );
	}
}
