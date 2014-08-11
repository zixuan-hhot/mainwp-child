<?php
if (class_exists('WP_Stream_Connector')) {
    class MainWPStreamConnectorBackups extends WP_Stream_Connector   
    {   

	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public static $name = 'mainwp_backups';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public static $actions = array(
            'mainwp_backup',		
	);

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public static function get_label() {
            return __( 'MainWP Backups', 'default' );                
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public static function get_action_labels() {
            return array(
                'mainwp_backup'    => __( 'Backup', 'default' ),			
            );
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public static function get_context_labels() {
            return array(
                'mainwp_backups' => __( 'MainWP Backups', 'mainwp-child' ),
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
            if (isset($record->object_id)) {
            }
            return $links;
	}

        public static function callback_mainwp_backup($information) {            
            $message = "";
            if (is_array($information)) {
                if (isset($information['full'])) {
                    if (!empty($information['full'])) {                
                        $backup_type = "full";                        
                        $full_path = $information['full'];
                        $file_name = basename($full_path);
                        $size_in_byte = $information['size'];
                        $file_size = number_format($size_in_byte / (1024 * 1024), 2);
                        $message = __('Full backup success, destination %1$s, size %2$s MB', "mainwp-child");
                    } else {
                        $message = __("Full backup failed", "mainwp-child");
                    }
                } else if (isset($information['db'])) {
                   if (!empty($information['db'])) {                
                        $backup_type = "database";
                        $full_path = $information['db'];
                        $file_name = basename($full_path);
                        $size_in_byte = $information['size'];
                        $file_size = number_format($size_in_byte / (1024 * 1024), 2);
                        $message = __('Database backup success, destination %1$s, size %2$s MB', "mainwp-child");
                   } else {
                        $message = __("Database backup failed", "mainwp-child");
                   }
                } 
            } else {
                $message = __("Database backup failed due to an undefined error", "mainwp-child");
            }
            
            self::log(
                $message,
                compact('file_name', 'file_size', 'backup_type', 'full_path', 'size_in_byte'),
                0,
                array( 'mainwp_backups' => 'mainwp_backup' )
            );
            
        }
    }
}

