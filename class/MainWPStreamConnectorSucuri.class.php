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
		'mainwp_sucuri_scan',		
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
			'mainwp_sucuri_scan'    => __( 'Scan', 'default' ),			
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

        public static function callback_mainwp_sucuri_scan($data, $status) {
            $message = $scan_status = "";
            $scan_result = unserialize(base64_decode($data));
            if ($status == "success") {
                $message = __("Sucuri scan success", "mainwp-child");                
                $scan_status = "success";
            } else {
                $message = __("Sucuri scan failed", "mainwp-child");                
                $scan_status = "failed";
            }
            
            $status = $webtrust = $results = "";            
            if (is_array($scan_result)) {
                $status = isset($scan_result['sucuri.check.status']) ? base64_encode(serialize($scan_result['sucuri.check.status'])) : "";
                $webtrust = isset($scan_result['sucuri.check.webtrust']) ? base64_encode(serialize($scan_result['sucuri.check.webtrust'])) : "";
                $results = isset($scan_result['sucuri.check.results']) ? base64_encode(serialize($scan_result['sucuri.check.results'])) : "";
            }
            
            self::log(
                $message,
                compact('scan_status', 'status', 'webtrust', 'results'),
                0,
                array( 'mainwp_sucuri' => 'mainwp_sucuri_scan' )
            );            
        }
    }
}

