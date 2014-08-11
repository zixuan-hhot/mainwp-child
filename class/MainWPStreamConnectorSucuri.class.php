<?php
if (class_exists('WP_Stream_Connector')) {
    class MainWPStreamConnectorSucuri extends WP_Stream_Connector   
    {   

	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public static $name = 'mainwp_sucuri';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public static $actions = array(
		'mainwp_sucuri_check',		
	);

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public static function get_label() {
		return __( 'MainWP Sucuri', 'default' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public static function get_action_labels() {
		return array(
			'mainwp_sucuri_check'    => __( 'Check', 'default' ),			
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public static function get_context_labels() {
		return array(
			'mainwp_sucuri' => __( 'MainWP Sucuri', 'default' ),
		);
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 * @param  array $links      Previous links registered
	 * @param  int   $record     Stream record
	 * @return array             Action links
	 */
	public static function action_links( $links, $record ) {
		if ( isset($record->object_id )) {
			
		}
		return $links;
	}

        public static function callback_mainwp_sucuri_check($result, $status) {
            $message = $scan_result = $scan_status = "";
            if ($status == "success") {
                $message = __("Sucuri scan success", "mainwp-child");
                $scan_result = $result;
                $scan_status = "success";
            } else {
                $message = __("Sucuri scan failed", "mainwp-child");
                $scan_result = $result;   
                $scan_status = "failed";
            }
            
            $record_id = self::log(
                $message,
                compact('scan_status'),
                0,
                array( 'mainwp_sucuri' => 'mainwp_sucuri_check' )
            );
            // scan result too big to save to log data
            if (!empty($record_id))
                update_option('mainwp_creport_sucuri_scan_result_' . $record_id, $scan_result);
        }
    }
}

