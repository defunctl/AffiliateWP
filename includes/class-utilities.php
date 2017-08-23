<?php
use AffWP\Utils;
use \Carbon\Carbon;

/**
 * Utilities class for AffiliateWP.
 *
 * @since 2.0
 */
class Affiliate_WP_Utilities {

	/**
	 * Batch process registry class instance variable.
	 *
	 * @access public
	 * @since  2.0
	 * @var    \AffWP\Utils\Batch_Process\Registry
	 */
	public $batch;

	/**
	 * Temporary data storage class instance variable.
	 *
	 * @access public
	 * @since  2.0
	 * @var    \AffWP\Utils\Data_Storage
	 */
	public $data;

	/**
	 * Upgrades class instance variable.
	 *
	 * @access public
	 * @since  2.0
	 * @var    \Affiliate_WP_Upgrades
	 */
	public $upgrades;

	/**
	 * Logger class instance.
	 *
	 * @access public
	 * @since  2.0.2
	 * @var    \Affiliate_WP_Logging
	 */
	public $logs;

	/**
	 * Date utilities instance.
	 *
	 * @access public
	 * @since  2.2
	 * @var    \AffWP\Utils\Date
	 */
	public $date;

	/**
	 * Signifies whether debug mode is enabled.
	 *
	 * @access protected
	 * @since  2.0.2
	 * @var    bool
	 */
	public $debug_enabled;

	/**
	 * Instantiates the utilities class.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function __construct() {
		$this->includes();
		$this->setup_objects();
	}

	/**
	 * Includes necessary utility files.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function includes() {
		// Only load Carbon if not already loaded by another plugin.
		if ( ! class_exists( '\Carbon\Carbon' ) ) {
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/libraries/Carbon/Carbon.php';
		}

		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-affwp-date.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-logging.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-upgrade-registry.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-batch-process-registry.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-data-storage.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-upgrades.php';
	}

	/**
	 * Sets up utility objects.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function setup_objects() {
		// Set the debug flag.
		$this->debug_enabled = affiliate_wp()->settings->get( 'debug_mode', false );

		$this->date     = new \AffWP\Utils\Date;
		$this->logs     = new Affiliate_WP_Logging;
		$this->batch    = new Utils\Batch_Process\Registry;
		$this->upgrades = new Affiliate_WP_Upgrades( $this );
		$this->data     = new Utils\Data_Storage;

		// Initialize batch registry after loading the upgrades class.
		$this->batch->init();
	}

	/**
	 * Writes a debug log entry.
	 *
	 * @access public
	 * @since  2.0.2
	 *
	 * @param string $message Message to write to the debug log.
	 */
	public function log( $message = '' ) {
		if ( $this->debug_enabled ) {
			$this->logs->log( $message );
		}
	}

	/**
	 * Performs processes on request data depending on the given context.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param array  $data    Request data.
	 * @param string $old_key Optional. Old key under which to process data. Default empty.
	 * @return array (Maybe) processed request data.
	 */
	public function process_request_data( $data, $old_key = '' ) {
		switch ( $old_key ) {
			case 'user_name':
			case '_affwp_affiliate_user_name':
			case 'affwp_pms_user_name':
				if ( ! empty( $data[ $old_key ] ) ) {
					$username = sanitize_text_field( $data[ $old_key ] );

					if ( $user = get_user_by( 'login', $username ) ) {
						$data['user_id'] = $user->ID;

						unset( $data[ $old_key ] );
					} else {
						$data['user_id'] = 0;
					}
				}
				break;

			default : break;
		}
		return $data;
	}

	/**
	 * Retrieves a Carbon date instance for the WP timezone based on the given date string.
	 *
	 * @access public
	 * @since  2.2
	 *
	 * @param string $date_string Optional. Date string. Default 'now'.
	 * @param string $timezone    Optional. Timezone to generate the Carbon instance for.
	 *                            Default is the timezone set in WordPress settings.
	 * @return Carbon Carbon instance.
	 */
	public function date( $date_string = 'now', $timezone = '' ) {
		$timezone = empty( $timezone ) ? $this->date->timezone : $timezone;

		return new Carbon( $date_string, $timezone );
	}


}
