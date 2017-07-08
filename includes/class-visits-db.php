<?php
/**
 * Class Affiliate_WP_Visits_DB
 *
 * @see Affiliate_WP_DB
 *
 * @property-read \AffWP\Affiliate\REST\v1\Endpoints $REST Visits REST endpoints.
 */
class Affiliate_WP_Visits_DB extends Affiliate_WP_DB {

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $cache_group = 'visits';

	/**
	 * Object type to query for.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $query_object_type = 'AffWP\Visit';

	public function __construct() {
		global $wpdb, $wp_version;

		if( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
			// Allows a single visits table for the whole network
			$this->table_name  = 'affiliate_wp_visits';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_visits';
		}
		$this->primary_key = 'visit_id';
		$this->version     = '1.1';

		// REST endpoints.
		if ( version_compare( $wp_version, '4.4', '>=' ) ) {
			$this->REST = new \AffWP\Visit\REST\v1\Endpoints;
		}
	}

	/**
	 * Retrieves a visit object.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @see Affiliate_WP_DB::get_core_object()
	 *
	 * @param int|object|AffWP\Visit $visit Visit ID or object.
	 * @return AffWP\Visit|null Visit object, null otherwise.
	 */
	public function get_object( $visit ) {
		return $this->get_core_object( $visit, $this->query_object_type );
	}

	public function get_columns() {
		return array(
			'visit_id'     => '%d',
			'affiliate_id' => '%d',
			'referral_id'  => '%d',
			'url'          => '%s',
			'referrer'     => '%s',
			'campaign'     => '%s',
			'context'      => '%s',
			'ip'           => '%s',
			'date'         => '%s',
		);
	}

	public function get_column_defaults() {
		return array(
			'affiliate_id' => 0,
			'referral_id'  => 0,
			'date'         => date( 'Y-m-d H:i:s' ),
			'referrer'     => ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '',
			'campaign'     => ! empty( $_REQUEST['campaign'] )    ? $_REQUEST['campaign']    : '',
			'context'      => ! empty( $_REQUEST['context'] )     ? $_REQUEST['context']     : ''
		);
	}

	/**
	 * Retrieve visits from the database
	 *
	 * @access  public
	 * @since   1.0
	 * @param   array $args {
	 *     Optional. Arguments to retrieve visits. Default empty array.
	 *
	 *     @type int          $number           Number of visits to retrieve. Accepts -1 for all. Default 20.
	 *     @type int          $offset           Number of visits to offset in the query. Default 0.
	 *     @type int|array    $visit_id         Specific visit ID or array of IDs to query for. Default 0 (all).
	 *     @type int|array    $affiliate_id     Specific affiliate ID or array of IDs to query visits for.
	 *                                          Default 0 (all).
	 *     @type int|array    $referral_id      Specific referral ID or array of IDs to query visits for.
	 *                                          Default 0 (all).
	 *     @type string       $referral_status  Specific conversion status to query for. Accepts 'converted'
	 *                                          or 'unconverted'. Default empty (all).
	 *     @type string|array $campaign         Specific campaign or array of campaigns to query visits for. Default
	 *                                          empty.
	 *     @type string       $campaign_compare Comparison operator to use when querying for visits by campaign.
	 *                                          Accepts '=', '!=' or 'NOT EMPTY'. If 'EMPTY' or 'NOT EMPTY', `$campaign`
	 *                                          will be ignored and visits will simply be queried based on whether
	 *                                          the campaign column is empty or not. Default '='.
	 *     @type string|array $context          Context or array of contexts under which the visit was generated.
	 *                                          Default empty.
	 *     @type string       $context_compare  Comparison operator to use when querying for visits by context. Accepts
	 *                                          '=', '!=', or 'NOT EMPTY'. If 'EMPTY' or 'NOT EMPTY', `$context`
	 *                                          will be ignored and visits will simply be queried based on whether the
	 *                                          context column is empty or not. Default '='.
	 *     @type string       $orderby          Column to order results by. Accepts any valid referrals table column.
	 *                                          Default 'referral_id'.
	 *     @type string       $order            How to order results. Accepts 'ASC' (ascending) or 'DESC' (descending).
	 *                                          Default 'DESC'.
	 *     @type string|array $fields           Specific fields to retrieve. Accepts 'ids', a single visit field, or an
	 *                                          array of fields. Default '*' (all).
	 * }
	 * @param   bool  $count  Return only the total number of results found (optional)
	 * @return \AffWP\Visit[]|int|stdClass[]
	*/
	public function get_visits( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'visit_id'         => 0,
			'affiliate_id'     => 0,
			'referral_id'      => 0,
			'referral_status'  => '',
			'campaign'         => '',
			'campaign_compare' => '=',
			'context'          => '',
			'context_compare'  => '=',
			'order'            => 'DESC',
			'orderby'          => 'visit_id',
			'fields'           => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$claws = claws();

		// Specific visits.
		if( ! empty( $args['visit_id'] ) ) {

			$claws->where( 'visit_id' )->in( $args['visit_id'], 'int' );

		}

		// visits for specific affiliates
		if( ! empty( $args['affiliate_id'] ) ) {

			$claws->where( 'affiliate_id' )->in( $args['affiliate_id'], 'int' );

		}

		// visits for specific referral
		if( ! empty( $args['referral_id'] ) ) {

			$claws->where( 'referral_id' )->in( $args['referral_id'], 'int' );

		}

		if ( empty( $args['campaign_compare'] ) ) {
			$campaign_compare = '=';
		} else {
			if ( 'NOT EMPTY' === $args['campaign_compare'] ) {
				$campaign_compare = '!=';

				// Cancel out campaign value for comparison purposes.
				$args['campaign'] = '';
			} elseif ( 'EMPTY' === $args['campaign_compare'] ) {
				$campaign_compare = '=';

				// Cancel out campaign value for comparison purposes.
				$args['campaign'] = '';
			} else {
				$campaign_compare = $args['campaign_compare'];
			}
		}

		// visits for specific campaign
		if( ! empty( $args['campaign'] )
			|| ( empty( $args['campaign'] ) && '=' !== $campaign_compare )
		) {

			if( is_array( $args['campaign'] ) ) {

				if ( '!=' === $campaign_compare ) {
					$claws->where( 'campaign' )->not_in( $args['campaign'] );
				} else {
					$claws->where( 'campaign' )->in( $args['campaign'] );
				}

			} else {

				$claws->where( 'campaign', $campaign_compare, $args['campaign'] );

			}

		}

		// Visits context comparison.
		if ( empty( $args['context_compare'] ) ) {
			$context_compare = '=';
		} else {
			if ( 'NOT EMPTY' === $args['context_compare'] ) {
				$context_compare = '!=';

				// Cancel out context value for comparison purposes.
				$args['context'] = '';
			} elseif ( 'EMPTY' === $args['context_compare'] ) {
				$context_compare = '=';

				// Cancel out context value for comparison purposes.
				$args['context'] = '';
			} else {
				$context_compare = $args['context_compare'];
			}
		}

		// Visits context.
		if( ! empty( $args['context'] )
			|| ( empty( $args['context'] ) && '=' !== $context_compare )
			|| ( empty( $args['context'] ) && '=' === $context_compare && 'EMPTY' === $args['context_compare'] )
		) {

			if( is_array( $args['context'] ) ) {

				if ( '!=' === $context_compare ) {
					$claws->where( 'context' )->not_in( $args['context'] );
				} else {
					$claws->where( 'context' )->in( $args['context'] );
				}

			} else {

				$claws->where( 'context', $context_compare, $args['context'] );

			}

		}

		// visits for specific referral status
		if ( ! empty( $args['referral_status'] ) ) {

			if ( 'converted' === $args['referral_status'] ) {

				$claws->where( 'referral_id' )->gt( 0 );

			} elseif ( 'unconverted' === $args['referral_status'] ) {

				$claws->where( 'referral_id' )->equals( 0 );

			}

		}

		// Visits for a date or date range
		if( ! empty( $args['date'] ) ) {

			if( is_array( $args['date'] ) ) {

				if( ! empty( $args['date']['start'] ) ) {

					$start = date( 'Y-m-d H:i:s', strtotime( $args['date']['start'] ) );

					$claws->where( 'date' )->gte( $start );
				}

				if( ! empty( $args['date']['end'] ) ) {

					$end = date( 'Y-m-d H:i:s', strtotime( $args['date']['end'] ) );

					$claws->where( 'date' )->lte( $end );
				}

			} else {

				$year  = date( 'Y', strtotime( $args['date'] ) );
				$month = date( 'm', strtotime( $args['date'] ) );
				$day   = date( 'd', strtotime( $args['date'] ) );

				$claws->add_clause_sql( "$year = YEAR ( date ) AND $month = MONTH ( date ) AND $day = DAY ( date )", 'where' );
			}

		}

		// Build the search query
		if( ! empty( $args['search'] ) ) {

			if ( filter_var( $args['search'], FILTER_VALIDATE_IP ) ) {
				$claws->where( 'ip' )->like( $args['search'] );
			} else {
				$claws->where( 'referrer' )->like( $args['search'], array( $this, 'esc_like' ) )
					->or()
					->where( 'url' )->like( $args['search'], array( $this, 'esc_like' ) );
			}
		}

		if ( 'DESC' === strtoupper( $args['order'] ) ) {
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}

		$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? $args['orderby'] : $this->primary_key;

		// Overload args values for the benefit of the cache.
		$args['orderby'] = $orderby;
		$args['order']   = $order;

		// Fields.
		$callback = '';

		if ( 'ids' === $args['fields'] ) {
			$fields   = "$this->primary_key";
			$callback = 'intval';
		} else {
			$fields = $this->parse_fields( $args['fields'] );

			if ( '*' === $fields ) {
				$callback = 'affwp_get_visit';
			}
		}

		$where = $claws->get_sql( 'where' );

		$key = ( true === $count ) ? md5( 'affwp_visits_count' . serialize( $args ) ) : md5( 'affwp_visits_' . serialize( $args ) );

		$last_changed = wp_cache_get( 'last_changed', $this->cache_group );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
		}

		$cache_key = "{$key}:{$last_changed}";

		$results = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $results ) {

			$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

			$results = $this->get_results( $clauses, $args, $callback );
		}

		wp_cache_add( $cache_key, $results, $this->cache_group, HOUR_IN_SECONDS );

		return $results;

	}

	/**
	 * Returns the number of results found for a given query
	 *
	 * @param  array  $args
	 * @return int
	 */
	public function count( $args = array() ) {
		return $this->get_visits( $args, true );
	}

	/**
	 * Adds a visit to the database.
	 *
	 * @access public
	 *
	 * @param array $data Optional. Arguments for adding a new visit. Default empty array.
	 * @return int ID of the added visit.
	 */
	public function add( $data = array() ) {

		if( ! empty( $data['url'] ) ) {
			$data['url'] = affwp_sanitize_visit_url( $data['url'] );
		}

		if( ! empty( $data['campaign'] ) ) {

			// Make sure campaign is not longer than 50 characters
			$data['campaign'] = substr( $data['campaign'], 0, 50 );

		}

		if ( ! empty( $data['context'] ) ) {
			$data['context'] = sanitize_key( substr( $data['context'], 0, 50 ) );
		}

		$visit_id = $this->insert( $data, 'visit' );

		affwp_increase_affiliate_visit_count( $data['affiliate_id'] );

		return $visit_id;
	}

	/**
	 * Updates a visit.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param int|AffWP\Visit $visit_id Visit ID or object.
	 * @param array           $data     Optional. Data array. Default empty array.
	 * @return int|false The visit ID if successfully updated, false otherwise.
	 */
	public function update_visit( $visit, $data = array() ) {

		if ( ! $visit = affwp_get_visit( $visit ) ) {
			return false;
		}

		if ( ! empty( $data['url'] ) ) {
			$data['url'] = affwp_sanitize_visit_url( $data['url'] );
		}

		if ( ! empty( $data['campaign'] ) ) {
			$data['campaign'] = substr( $data['campaign'], 0, 50 );
		}

		if ( ! empty( $data['context'] ) ) {
			$data['context'] = sanitize_key( substr( $data['context'], 0, 50 ) );
		}

		if ( ! empty( $data['affiliate_id'] ) ) {
			// If the passed affiliate ID is invalid, ignore the new value.
			if ( ! affwp_get_affiliate( $data['affiliate_id'] ) ) {
				$data['affiliate_id'] = $visit->affiliate_id;
			}
		}
		if ( $this->update( $visit->ID, $data, '', 'visit' ) ) {
			$updated_visit = affwp_get_visit( $visit->ID );

			// Handle visit counts if the affiliate was changed.
			if ( $updated_visit->affiliate_id !== $visit->affiliate_id ) {
				affwp_decrease_affiliate_visit_count( $visit->affiliate_id );
				affwp_increase_affiliate_visit_count( $updated_visit->affiliate_id );
			}
			return $visit->ID;
		}
		return false;
	}

	/**
	 * Creates the visits database table.
	 *
	 * @access public
	 *
	 * @see dbDelta()
	 */
	public function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
			visit_id bigint(20) NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) NOT NULL,
			referral_id bigint(20) NOT NULL,
			url mediumtext NOT NULL,
			referrer mediumtext NOT NULL,
			campaign varchar(50) NOT NULL,
			context varchar(50) NOT NULL,
			ip tinytext NOT NULL,
			date datetime NOT NULL,
			PRIMARY KEY  (visit_id),
			KEY affiliate_id (affiliate_id)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
