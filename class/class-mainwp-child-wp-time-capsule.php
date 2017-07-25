<?php

class MainWP_Child_WP_Time_Capsule {
    
    public static $instance = null;
    public $is_plugin_installed = false;
    private $excluded_files;
	private $included_files;
	private $excluded_tables;
	private $included_tables;
    private $default_wp_folders;
    private $default_wp_files;
    private $default_wp_files_n_folders;
    
    static function Instance() {
        if ( null === MainWP_Child_WP_Time_Capsule::$instance ) {
            MainWP_Child_WP_Time_Capsule::$instance = new MainWP_Child_WP_Time_Capsule();
        }
        return MainWP_Child_WP_Time_Capsule::$instance;
    }

    public function __construct() {		                
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-time-capsule/wp-time-capsule.php' ) && defined('WPTC_CLASSES_DIR')) {
            $this->is_plugin_installed = true;			
		}   
        
        if (!$this->is_plugin_installed)
            return;
        
        $this->default_wp_folders = array(
						WPTC_ABSPATH.'wp-admin',
						WPTC_ABSPATH.'wp-includes',
						WPTC_WP_CONTENT_DIR,
					);
		$this->default_wp_files = array(
						WPTC_ABSPATH.'favicon.ico',
						WPTC_ABSPATH.'index.php',
						WPTC_ABSPATH.'license.txt',
						WPTC_ABSPATH.'readme.html',
						WPTC_ABSPATH.'robots.txt',
						WPTC_ABSPATH.'sitemap.xml',
						WPTC_ABSPATH.'wp-activate.php',
						WPTC_ABSPATH.'wp-blog-header.php',
						WPTC_ABSPATH.'wp-comments-post.php',
						WPTC_ABSPATH.'wp-config-sample.php',
						WPTC_ABSPATH.'wp-config.php',
						WPTC_ABSPATH.'wp-cron.php',
						WPTC_ABSPATH.'wp-links-opml.php',
						WPTC_ABSPATH.'wp-load.php',
						WPTC_ABSPATH.'wp-login.php',
						WPTC_ABSPATH.'wp-mail.php',
						WPTC_ABSPATH.'wp-settings.php',
						WPTC_ABSPATH.'wp-signup.php',
						WPTC_ABSPATH.'wp-trackback.php',
						WPTC_ABSPATH.'xmlrpc.php',
						WPTC_ABSPATH.'.htaccess',
					);
		$this->default_wp_files_n_folders = array_merge($this->default_wp_folders, $this->default_wp_files);
		
    }

    
	public function init() {                
		if ( get_option( 'mainwp_time_capsule_ext_enabled' ) !== 'Y' ) 
            return;     
        
        if (!$this->is_plugin_installed) 
            return;      
        
        add_action( 'mainwp_child_site_stats', array( $this, 'do_site_stats' ) );
        add_action( 'record_auto_backup_complete', array( $this, 'do_report_backups_logging' ) );
        
		if ( get_option( 'mainwp_time_capsule_hide_plugin' ) === 'hide' ) {
			add_filter( 'all_plugins', array( $this, 'all_plugins' ) );
			add_action( 'admin_menu', array( $this, 'remove_menu' ) );
			add_filter( 'site_transient_update_plugins', array( &$this, 'remove_update_nag' ) );
		}
	}

    
    public function action() {
            if (!$this->is_plugin_installed) {
                 MainWP_Helper::write( array('error' => 'Please install WP Time Capsule plugin on child website') );
            }
            
            if (!class_exists('WPTC_Base_Factory')) {
                require_once WPTC_CLASSES_DIR.'Factory.php';
            }   
            
            if (!class_exists('Wptc_Options_Helper')) {
                require_once WPTC_PLUGIN_DIR . 'Views/wptc-options-helper.php';
            }
                    
            $this->db = WPTC_Factory::db();
            $this->load_exc_inc_files();
            $this->load_exc_inc_tables();
        
            $information = array();		
            if (get_option( 'mainwp_time_capsule_ext_enabled' ) !== 'Y')
                MainWP_Helper::update_option( 'mainwp_time_capsule_ext_enabled', 'Y', 'yes' );	
            
            if ( isset( $_POST['mwp_action'] ) ) {
                switch ( $_POST['mwp_action'] ) {
                    case 'set_showhide':
                            $information = $this->set_showhide();
                        break; 
                    case 'get_init_root_files':
                            $information = $this->get_root_files();
                        break; 
                    case 'get_tables':
                            $information = $this->get_tables();
                        break; 
                    case 'exclude_file_list':
                            $information = $this->exclude_file_list();
                        break; 
                    case 'exclude_table_list':
                            $information = $this->do_exclude_table_list();
                        break;                     
                    case 'include_table_list':
                            $information = $this->include_table_list();
                        break;
                    case 'include_file_list':
                            $information = $this->include_file_list();
                        break;
                    case 'get_files_by_key':
                            $information = $this->get_files_by_key();
                        break;
                    case 'wptc_login':
                            $information = $this->process_wptc_login();
                        break;
                    case 'save_settings':
                            $information = $this->save_settings_wptc();
                        break;
                    case 'start_fresh_backup':
                            $information = $this->start_fresh_backup_tc_callback_wptc();
                        break;       
                    case 'save_manual_backup_name':
                            $information = $this->save_manual_backup_name_wptc();
                        break;
                    case 'progress_wptc':
                            $information = $this->progress_wptc();
                        break;
                    case 'stop_fresh_backup':
                            $information = $this->stop_fresh_backup_tc_callback_wptc();
                        break;
                    case 'wptc_cron_status':
                            $information = $this->wptc_cron_status();
                        break;
                    case 'get_this_backups_html':
                            $information = $this->get_this_backups_html();
                        break;
                    case 'start_restore_tc_wptc':
                            $information = $this->start_restore_tc_callback_wptc();
                        break;
                    case 'get_logs_rows':
                            $information = $this->get_logs_rows();
                        break;
                    case 'clear_logs':
                            $information = $this->clear_wptc_logs();
                        break;                    
                    case 'get_issue_report_specific':
                            $information = $this->get_issue_report_specific_callback_wptc();
                        break;                    
                    case 'send_issue_report':
                            $information = $this->send_wtc_issue_report_wptc();
                        break;
                    case 'lazy_load_activity_log':
                            $information = $this->lazy_load_activity_log_wptc();
                        break;
                }
            }
            MainWP_Helper::write( $information );
    }           
        
    function set_showhide() {
		$hide = isset( $_POST['showhide'] ) && ( 'hide' === $_POST['showhide'] ) ? 'hide' : '';
		MainWP_Helper::update_option( 'mainwp_time_capsule_hide_plugin', $hide, 'yes' );
		$information['result'] = 'SUCCESS';
		return $information;
	}
    
    public function get_sync_data() {	        
        
        require_once WPTC_PLUGIN_DIR . '/Views/wptc-options-helper.php';        
        require_once WPTC_PLUGIN_DIR . '/Views/wptc-options.php';  
        
        if (!class_exists('WPTC_Base_Factory')) {
            require_once WPTC_CLASSES_DIR.'Factory.php';
        }
            
        $config = WPTC_Factory::get('config');
        $main_account_email_var = $config->get_option('main_account_email');        
        $last_backup_time = $config->get_option('last_backup_time');
        $wptc_settings = WPTC_Base_Factory::get('Wptc_Settings');
            
        $options_helper = new Wptc_Options_Helper();                                    
		$return = array(    
                    'main_account_email' => $main_account_email_var,
                    'signed_in_repos' =>   $wptc_settings->get_connected_cloud_info(),
                    'plan_name' => $options_helper->get_plan_name_from_privileges(),
                    'plan_interval' => $options_helper->get_plan_interval_from_subs_info(),
                    'lastbackup_time' => !empty($last_backup_time) ? $last_backup_time : 0,
                    'is_user_logged_in' => $options_helper->get_is_user_logged_in()
                );
		return $return;
	}
    
    public function get_tables() {        
        $exc_wp_tables = $_POST['exc_wp_tables'];
        $processed_files = WPTC_Factory::get('processed-files'); 
		$tables = $processed_files->get_all_tables();
        $config = WPTC_Base_Factory::get('Wptc_Exclude_Config');     	
		if ($exc_wp_tables && !$config->get_option('non_wp_tables_excluded')) {
			$this->exclude_non_wp_tabes($tables);
			$this->load_exc_inc_tables();
			$config->set_option('non_wp_tables_excluded', true);
		}
		$tables_arr = array();
		
		foreach ($tables as $table) {
			$excluded = $this->is_excluded_table($table);
			if ($excluded) {
				$temp = array(
					'title' => $table,
					'key' => $table,
					'size' => $processed_files->get_table_size($table),
				);
			} else {
				$temp = array(
					'title' => $table,
					'key' => $table,
					'size' => $processed_files->get_table_size($table),
					'preselected' => true,
				);
			}
			$temp['size_in_bytes'] = $processed_files->get_table_size($table, 0);
			$tables_arr[] = $temp;
		}		
        $information['result'] = $tables_arr;
        return $information;
	}

    public function exclude_file_list(){
        $data = $_POST['data'];
        
		if (empty($data['file'])) {
			return false;
		}
        
		$data['file'] = wp_normalize_path($data['file']);
		if ($data['isdir']) {
			$this->remove_include_files($data['file'], 1);
			$this->remove_exclude_files($data['file'], 1);
		} else {
			$this->remove_exclude_files($data['file']);
			$this->remove_include_files($data['file']);
		}

		$result = $this->db->insert("{$this->db->base_prefix}wptc_excluded_files", $data);

		if ($result) {
            $information['status'] = 'success';			
		} else {
            $information['status'] = 'error';
        }
        return $information;		
	}
    
    private function remove_exclude_files($file, $force = false){
		if (empty($file)) {
			return false;
		}

		if ($force) {
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_files WHERE file LIKE '%%%s%%'", $file);
		} else{
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_files WHERE file = %s", $file);
		}
		$result = $this->db->query($re_sql);
	}
    
    private function remove_include_files($file, $force = false){
		if (empty($file)) {
			return false;
		}
		if ($force) {
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_files WHERE file LIKE '%%%s%%'", $file);
		} else{
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_files WHERE file = %s", $file);
		}
		$result = $this->db->query($re_sql);
	}

    
    public function is_excluded_table($table){
		if (empty($table)) {
			return true;
		}
		if($this->is_wp_table($table)){
			return $this->exclude_table_check_deep($table);
		}
		if ($this->is_included_table($table)) {
			return false;
		}
		return true;
	}
    
    function progress_wptc() {
        
        $config = WPTC_Factory::get('config');
        global $wpdb;
        if (!$config->get_option('in_progress')) {
            spawn_cron();
        }
        
        $processed_files = WPTC_Factory::get('processed-files');
        $return_array = array();
        $return_array['stored_backups'] = $processed_files->get_stored_backups();
        $return_array['backup_progress'] = array();
        $return_array['starting_first_backup'] = $config->get_option('starting_first_backup');
        $return_array['meta_data_backup_process'] = $config->get_option('meta_data_backup_process');
        $return_array['backup_before_update_progress'] = $config->get_option('backup_before_update_progress');
        $return_array['is_staging_running'] = apply_filters('is_any_staging_process_going_on', '');
        $cron_status = $config->get_option('wptc_own_cron_status');

        if (!empty($cron_status)) {
            $return_array['wptc_own_cron_status'] = unserialize($cron_status);
            $return_array['wptc_own_cron_status_notified'] = (int) $config->get_option('wptc_own_cron_status_notified');
        }

        $start_backups_failed_server = $config->get_option('start_backups_failed_server');
        if (!empty($start_backups_failed_server)) {
            $return_array['start_backups_failed_server'] = unserialize($start_backups_failed_server);
            $config->set_option('start_backups_failed_server', false);
        }

        $processed_files->get_current_backup_progress($return_array);

        $return_array['user_came_from_existing_ver'] = (int) $config->get_option('user_came_from_existing_ver');
        $return_array['show_user_php_error'] = $config->get_option('show_user_php_error');
        $return_array['bbu_setting_status'] = apply_filters('get_backup_before_update_setting_wptc', '');
        $return_array['bbu_note_view'] = apply_filters('get_bbu_note_view', '');
        $return_array['staging_status'] = apply_filters('staging_status_wptc', '');

        $processed_files = WPTC_Factory::get('processed-files');
        $last_backup_time = $config->get_option('last_backup_time');

        if (!empty($last_backup_time)) {
            $user_time = $config->cnvt_UTC_to_usrTime($last_backup_time);
            $processed_files->modify_schedule_backup_time($user_time);
            $formatted_date = date("M d @ g:i a", $user_time);
            $return_array['last_backup_time'] = $formatted_date;
        } else {
            $return_array['last_backup_time']  = 'No Backup Taken';
        }

        return array( 'result' => $return_array );
        
    }
    
    function wptc_cron_status(){
        $config = WPTC_Factory::get('config');
        wptc_own_cron_status();
        $status = array();
        $cron_status = $config->get_option('wptc_own_cron_status');
        if (!empty($cron_status)) {
            $cron_status = unserialize($cron_status);
            dark_debug($cron_status,'--------------$cron_status-------------');
            if ($cron_status['status'] == 'success') {                
                $status['status'] = 'success';
            } else {                
                $status['status'] = 'failed';
                $status['status_code'] = $cron_status['statusCode'];
                $status['err_msg'] = $cron_status['body'];
                $status['cron_url'] = $cron_status['cron_url'];
                $status['ips'] = $cron_status['ips'];
            }            
            return array('result' => $status);            
        }
        return false;
    }

    function get_this_backups_html() {        
        $this_backup_ids = $_POST['this_backup_ids'];
        $specific_dir = $_POST['specific_dir'];
        $type = $_POST['type'];
        $treeRecursiveCount = $_POST['treeRecursiveCount'];
        $processed_files = WPTC_Factory::get('processed-files');
        $result = $processed_files->get_this_backups_html($this_backup_ids, $specific_dir, $type, $treeRecursiveCount);
        return array( 'result' => $result );
    }
    
    function start_restore_tc_callback_wptc() {
        WPTC_Factory::get('Debug_Log')->wptc_log_now('Started restore', 'RESTORE');

        global $start_time_tc_bridge;
        $start_time_tc_bridge = microtime(true);

        $config = WPTC_Factory::get('config');
        // $config->set_option('wptc_profiling_start', microtime(true)); //used only for chart

        $data = array();
        if (isset($_POST['data']) && !empty($_POST['data'])) {
            $data = $_POST['data'];
        }

        dark_debug_func_map($data, "--- function " . __FUNCTION__ . "--------", WPTC_WPTC_DARK_TEST_PRINT_ALL);

        try {
            if (!empty($data) && !empty($data['is_first_call'])) {
                //initializing restore options
                reset_restore_related_settings_wptc();
                $config->set_option('restore_post_data', 0);
                $config->set_option('restore_post_data', serialize($data));
                $config->set_option('restore_action_id', time()); //main ID used througout the restore process
                $config->set_option('in_progress_restore', true);

                if (isset($data['ignore_file_write_check']) && !empty($data['ignore_file_write_check'])) {
                    $config->set_option('check_is_safe_for_write_restore', $data['ignore_file_write_check']);
                }

                $current_bridge_file_name = "wp-tcapsule-bridge-" . hash("crc32", microtime(true));
                $config->set_option('current_bridge_file_name', $current_bridge_file_name);

                $config->set_option('check_is_safe_for_write_restore', 1);
            }

            WPTC_Factory::get('Debug_Log')->wptc_log_now('Creating Dump dir', 'RESTORE');
            $config->create_dump_dir(); //This will initialize wp_filesystem
            WPTC_Factory::get('Debug_Log')->wptc_log_now('Dump dir created', 'RESTORE');

            $backup = new WPTC_BackupController();
            WPTC_Factory::get('Debug_Log')->wptc_log_now('Copying Bridge files', 'RESTORE');
            $copy_result = $backup->copy_bridge_files_tc();
            WPTC_Factory::get('Debug_Log')->wptc_log_now('Bridge files copied', 'RESTORE');

            if (!empty($copy_result) && is_array($copy_result) && !empty($copy_result['error'])) {
                return $copy_result;                
            }

            send_restore_initiated_email_wptc();
            WPTC_Factory::get('Debug_Log')->wptc_log_now('Restore init email sent', 'RESTORE');
            do_action('turn_off_auto_update_wptc', time());
            WPTC_Factory::get('Debug_Log')->wptc_log_now('Auto update turned off', 'RESTORE');
            
            return array('restoreInitiatedResult' => array('bridgeFileName' => $config->get_option('current_bridge_file_name'), 'safeToCallPluginAjax' => true),
                         'site_url' => get_bloginfo( 'url' )   
                        );
            
        } catch (Exception $e) {
            return array('error' => $e->getMessage());            
        }    
    }
   
    function get_issue_report_specific_callback_wptc() {
        $Report_issue = '';
        if ($_REQUEST['data']['log_id'] != "") {
            $Report_issue = WPTC_BackupController::construct()->wtc_report_issue($_REQUEST['data']['log_id']);            
        }
        return array('result' => $Report_issue);
    }
    
    
    function send_wtc_issue_report_wptc() {
        $options_obj = WPTC_Factory::get('config');
        $data = $_REQUEST['data'];
        $random = generate_random_string_wptc();
        if (empty($data['name'])) {
            $data['name'] = 'Admin';
        }
        global $wpdb;
        $report_issue_data['server']['PHP_VERSION'] 	= phpversion();
        $report_issue_data['server']['PHP_CURL_VERSION']= curl_version();
        $report_issue_data['server']['PHP_WITH_OPEN_SSL'] = function_exists('openssl_verify');
        $report_issue_data['server']['PHP_MAX_EXECUTION_TIME'] =  ini_get('max_execution_time');
        $report_issue_data['server']['PHP_MEMORY_LIMIT'] =  ini_get('memory_limit');
        $report_issue_data['server']['MYSQL_VERSION'] 	= $wpdb->get_var("select version() as V");
        $report_issue_data['server']['OS'] =  php_uname('s');
        $report_issue_data['server']['OSVersion'] =  php_uname('v');
        $report_issue_data['server']['Machine'] =  php_uname('m');
        $report_issue_data['server']['PHP_DISABLED_FUNCTIONS'] = explode(',', ini_get('disable_functions'));
        array_walk($report_issue_data['server']['PHP_DISABLED_FUNCTIONS'], 'trim_value_wptc');

        $report_issue_data['server']['PHP_DISABLED_CLASSES'] = explode(',', ini_get('disable_classes'));
        array_walk($report_issue_data['server']['PHP_DISABLED_CLASSES'], 'trim_value_wptc');

        $report_issue_data['server']['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $report_issue_data['server']['reportTime'] = time();
        $plugin_data['url'] = home_url();
        $plugin_data['main_account_email'] = $options_obj->get_option('main_account_email');
        $plugin_data['appID'] = $options_obj->get_option('appID');
        $plugin_data['wptc_version'] = $options_obj->get_option('wptc_version');
        $plugin_data['wptc_database_version'] = $options_obj->get_option('database_version');

        $logs['issue']['issue_data'] = $data['issue_data'];
        $logs['issue']['plugin_info'] = serialize($plugin_data);
        $logs['issue']['server_info'] = serialize($report_issue_data);
        $final_log = serialize($logs);
        dark_debug($logs,'--------------$report_issue_data-------------');
        $post_arr = array(
            'type' => 'issue',
            'issue' => $final_log,
            'useremail' => $data['email'],
            'title' => $data['desc'],
            'rand' => $random,
            'name' => $data['name'],
        );

        dark_debug(http_build_query($post_arr), "--------sending report issue--------");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, WPTC_APSERVER_URL . "/report_issue/index.php");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_arr));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $result = curl_exec($ch);

        dark_debug($result, "--------curl result report issue--------");

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_errno($ch);

        curl_close($ch);

        if ($curlErr || ($httpCode == 404)) {
            WPTC_Factory::get('logger')->log("Curl Error no : $curlErr - While Sending the Report data to server", 'connection_error');
            return array( 'result' =>  "fail" );            
        } else {
            if ($result == 'insert_success') {
                return array( 'result' =>  "sent" );                
            } else {
                return array( 'result' =>  "insert_fail" ); 
            }            
        }
        
        return false;
    }

    
    function get_logs_rows() {
        $result = $this->prepare_items();
        $result['display_rows'] = base64_encode(serialize($this->get_display_rows($result['items'])));
        return $result;
    }
    
    function prepare_items() {        
        global $wpdb;		
		
		if (isset($_POST['type'])) {
			$type = $_POST['type'];
			switch ($type) {
			case 'backups':
				$query = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE type LIKE '%backup%' AND show_user = 1 GROUP BY action_id";
				break;
			case 'restores':
				$query = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE type LIKE 'restore%' GROUP BY action_id";
				break;
			case 'others':
				$query = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE type NOT LIKE 'restore%' AND type NOT LIKE 'backup%' AND show_user = 1";
				break;
			default:
				$query = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log GROUP BY action_id UNION SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE action_id='' AND show_user = 1";
				break;
			}
		} else {
			$query = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE show_user = 1   GROUP BY action_id ";
		}
		/* -- Preparing your query -- */

		/* -- Ordering parameters -- */
		//Parameters that are going to be used to order the result
		$orderby = !empty($_POST["orderby"]) ? mysql_real_escape_string($_POST["orderby"]) : 'id';
		$order = !empty($_POST["order"]) ? mysql_real_escape_string($_POST["order"]) : 'DESC';
		if (!empty($orderby) & !empty($order)) {$query .= ' ORDER BY ' . $orderby . ' ' . $order;}

		/* -- Pagination parameters -- */
		//Number of elements in your table?
		$totalitems = $wpdb->query($query); //return the total number of affected rows
		//How many to display per page?
		$perpage = 20;
		//Which page is this?
		$paged = !empty($_POST["paged"]) ? $_POST["paged"] : '';
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {$paged = 1;} //Page Number
		//How many pages do we have in total?
		$totalpages = ceil($totalitems / $perpage); //Total number of pages
		//adjust the query to take pagination into account
		if (!empty($paged) && !empty($perpage)) {
			$offset = ($paged - 1) * $perpage;
			$query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}	
        
        return array(   'items' => $wpdb->get_results($query) ,
                        'totalitems' => $totalitems,
                        'perpage' => $perpage
                );
    }
    
     
    function lazy_load_activity_log_wptc(){
        
        if (!isset($_POST['data'])) {
            return false;
        }
        
        $data = $_POST['data'];
        if (!isset($data['action_id']) || !isset($data['limit'])) {
            return false;
        }
        global $wpdb;
        $action_id = $data['action_id'];
        $from_limit = $data['limit'];
        $detailed = '';
        $load_more = false;
        $current_limit = WPTC_Factory::get('config')->get_option('activity_log_lazy_load_limit');
        $to_limit = $from_limit + $current_limit;
        $sql = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE action_id=" . $action_id . ' AND show_user = 1 ORDER BY id DESC LIMIT '.$from_limit.' , '.$current_limit;
        $sub_records = $wpdb->get_results($sql);
        $row_count = count($sub_records);
        if ($row_count == $current_limit) {
            $load_more = true;
        }
        $wptc_list_table = new WPTC_List_Table();
        $detailed = $wptc_list_table->get_activity_log($sub_records);
        if (isset($load_more) && $load_more) {
            $detailed .= '<tr><td></td><td><a style="cursor:pointer; position:relative" class="mainwp_wptc_activity_log_load_more" action_id="'.$action_id.'" limit="'.$to_limit.'">Load more</a></td><td></td></tr>';
        }
        return array( 'result' => $detailed);
    }

    
    function get_display_rows($records) {
		global $wpdb;
		//Get the records registered in the prepare_items method
        if (!is_array($records))
            return '';
        
		$i=0;
		$limit = WPTC_Factory::get('config')->get_option('activity_log_lazy_load_limit');
		//Get the columns registered in the get_columns and get_sortable_columns methods
		// $columns = $this->get_columns();
		$timezone = WPTC_Factory::get('config')->get_option('wptc_timezone');
		if (count($records) > 0) {

			foreach ($records as $key => $rec) {
                $html = '';
                
				$more_logs = false;
				$load_more = false;
				if ($rec->action_id != '') {
					$sql = "SELECT * FROM " . $wpdb->base_prefix . "wptc_activity_log WHERE action_id=" . $rec->action_id . ' AND show_user = 1 ORDER BY id DESC LIMIT 0 , '.$limit;
					$sub_records = $wpdb->get_results($sql);
					$row_count = count($sub_records);
					if ($row_count == $limit) {
						$load_more = true;
					}

					if ($row_count > 0) {
						$more_logs = true;
						$detailed = '<table>';
						$detailed .= $this->get_activity_log($sub_records);
						if (isset($load_more) && $load_more) {
							$detailed .= '<tr><td></td><td><a style="cursor:pointer; position:relative" class="mainwp_wptc_activity_log_load_more" action_id="'.$rec->action_id.'" limit="'.$limit.'">Load more</a></td><td></td></tr>';
						}
						$detailed .= '</table>';

					}
				}
				//Open the line
				$html .= '<tr class="act-tr">';
				$Ldata = unserialize($rec->log_data);
				$user_time = WPTC_Factory::get('config')->cnvt_UTC_to_usrTime($Ldata['log_time']);
				WPTC_Factory::get('processed-files')->modify_schedule_backup_time($user_time);
				// $user_tz = new DateTime('@' . $Ldata['log_time'], new DateTimeZone(date_default_timezone_get()));
				// $user_tz->setTimeZone(new DateTimeZone($timezone));
				// $user_tz_now = $user_tz->format("M d, Y @ g:i:s a");
				$user_tz_now = date("M d, Y @ g:i:s a", $user_time);
				$msg = '';
				if (!(strpos($rec->type, 'backup') === false)) {
					//Backup process
					$msg = 'Backup Process';
				} else if (!(strpos($rec->type, 'restore') === false)) {
					//Restore Process
					$msg = 'Restore Process';
				} else if (!(strpos($rec->type, 'staging') === false)) {
					//Restore Process
					$msg = 'Staging Process';
				} else {
					if ($row_count < 2) {
						$more_logs = false;
					}
					$msg = $Ldata['msg'];
				}
				$html .= '<td class="wptc-act-td">' . $user_tz_now . '</td><td class="wptc-act-td">' . $msg;
				if ($more_logs) {
					$html .= "&nbsp&nbsp&nbsp&nbsp<a class='wptc-show-more' action_id='" . round($rec->action_id) . "'>View details</a></td>";
				} else {
					$html .= "</td>";
				}
				$html .= '<td class="wptc-act-td"><a class="report_issue_wptc" id="' . $rec->id . '" href="#">Send report to plugin developer</a></td>';
				if ($more_logs) {

					$html .= "</tr><tr id='" . round($rec->action_id) . "' class='wptc-more-logs'><td colspan=3>" . $detailed . "</td>";
				} else {
					$html .= "</td>";
				}
				//Close the line
				$html .= '</tr>';
                
                $display_rows[$key] = $html;
			}

		}
        return $display_rows;
	}
    
   
    function get_activity_log($sub_records){
		if (count($sub_records) < 1) {
			return false;
		}
		$detailed = '';
		$timezone = WPTC_Factory::get('config')->get_option('wptc_timezone');
		foreach ($sub_records as $srec) {
			$Moredata = unserialize($srec->log_data);
			$user_tmz = new DateTime('@' . $Moredata['log_time'], new DateTimeZone(date_default_timezone_get()));
			$user_tmz->setTimeZone(new DateTimeZone($timezone));
			$user_tmz_now = $user_tmz->format("M d @ g:i:s a");
			$detailed .= '<tr><td>' . $user_tmz_now . '</td><td>' . $Moredata['msg'] . '</td><td></td></tr>';
		}
		return $detailed;
	}
    
    function clear_wptc_logs() {
        global $wpdb;
        if ($wpdb->query("TRUNCATE TABLE `" . $wpdb->base_prefix . "wptc_activity_log`")) {
            $result = 'yes';
        } else {
            $result = 'no';
        }
        return array('result' => $result);
    }

    function stop_fresh_backup_tc_callback_wptc() {              
        //for backup during update
        $deactivated_plugin = null;
        $backup = new WPTC_BackupController();
        $backup->stop($deactivated_plugin); 
        return array('result' => 'ok');
    }
    
    private function exclude_table_check_deep($table){
		foreach ($this->excluded_tables as $value) {
			if (preg_match('#^'.$value.'#', $table) === 1) {
				return true;
			}
		}
		return false;
	}
    
    function get_root_files() {         
        $exc_wp_files = $_POST['exc_wp_files'];
        $path = get_tcsanitized_home_path();
        $config = WPTC_Base_Factory::get('Wptc_Exclude_Config');     	
		$result_obj = WPTC_Base_Factory::get('Wptc_ExcludeOption')->get_files_by_path($path);  
        
        if ($exc_wp_files && !$config->get_option('non_wp_files_excluded')) {
			$this->exclude_non_wp_files($result_obj);
			$this->load_exc_inc_files();
			$config->set_option('non_wp_files_excluded', true);
		}
        
		$result = $this->format_result_data($result_obj);        
                
		$information['result'] = $result;
		return $information;
	}
    
    private function load_exc_inc_files(){
		$this->excluded_files = $this->get_exlcuded_files_list();
		$this->included_files = $this->get_included_files_list();
	}
    
    private function load_exc_inc_tables(){
		$this->excluded_tables = $this->get_exlcuded_tables_list();
		$this->included_tables = $this->get_included_tables_list();
	}

    
    private function exclude_non_wp_files($file_obj){
		$selected_files = array();
		foreach ($file_obj as $Ofiles) {
			$file_path = $Ofiles->getPathname();
			$file_name = basename($file_path);
			if ($file_name == '.' || $file_name == '..') {
				continue;
			}
			if(!$this->is_wp_file($file_path)){
				$isdir = override_is_dir($file_path);
				$this->exclude_file_list(array('file'=> $file_path, 'isdir' => $isdir ), true);
			}
		}
	}
    
    
	private function is_wp_table($table){
		if (preg_match('#^'.$this->db->base_prefix.'#', $table) === 1) {
			return true;
		}
		return false;
	}
    
    private function exclude_non_wp_tabes($tables){
		foreach ($tables as $table) {
			if (!$this->is_wp_table($table)) {
				$this->exclude_table_list(array('file' => $table), true);
			}
		}
	}
        
    public function do_exclude_table_list(){
        $data = $_POST['data'];        
		return $this->exclude_table_list($data);               
    }
    
    function do_report_backups_logging($backup_id) {        
        $backup_time = time(); // may be difference a bit with WTC logging    
        $message = 'WP Time Capsule backup finished';
        $backup_type = 'WP Time Capsule';       
        do_action( 'mainwp_wptimecapsule_backup', $message, $backup_type, $backup_time );         
    }
    
    function do_site_stats() {
        if (has_action('mainwp_child_reports_log')) {
            do_action( 'mainwp_child_reports_log', 'wptimecapsule');
        } else {
            $this->do_reports_log('wptimecapsule');
        }
    }
        
    public function do_reports_log($ext = '') {
        if ( $ext !== 'wptimecapsule' ) return;
        if (!$this->is_plugin_installed)
            return;
        $config = WPTC_Base_Factory::get('Wptc_Exclude_Config');    
        $backup_time = $config->get_option('last_backup_time');
        if (!empty($backup_time)) {
            MainWP_Helper::update_lasttime_backup( 'wptimecapsule', $backup_time ); // to support backup before update feature
        }
    }
    
    
	//table related functions
	public function exclude_table_list($data, $do_not_die = false){
		if (empty($data['file'])) {
			return false;
		}

		$this->remove_exclude_table($data['file']);
		$this->remove_include_table($data['file']);

		$table_arr['id'] = NULL;
		$table_arr['table_name'] = $data['file'];
		$result = $this->db->insert("{$this->db->base_prefix}wptc_excluded_tables", $table_arr);
        
		if ($do_not_die) {
			return false;
		}
		if ($result) {
			return array('status' => 'success');
		}
		return array('status' => 'error');
	}
    
    public function include_table_list(){
        
        $data = $_POST['data'];  
        
		if (empty($data['file'])) {
			return false;
		}
		$this->remove_exclude_table($data['file']);
		$this->remove_include_table($data['file']);
		if ($this->is_wp_table($data['file'])) {
			dark_debug($data['file'], '---------------Wordpress table so cannot be inserted-----------------');
			return array('status' => 'success');
		}
		$table_arr['id'] = NULL;
		$table_arr['table_name'] = $data['file'];
		$result = $this->db->insert("{$this->db->base_prefix}wptc_included_tables", $table_arr);
		if ($result) {
			return array('status' => 'success');
		}
		return array('status' => 'error');
	}

    public function include_file_list(){
        $data = $_POST['data'];  
         
		if (empty($data['file'])) {
			return false;
		}
		$data['file'] = wp_normalize_path($data['file']);
		if ($data['isdir']) {
			$this->remove_exclude_files($data['file'], 1);
			$this->remove_include_files($data['file'], 1);
		} else {
			$this->remove_include_files($data['file']);
			$this->remove_exclude_files($data['file']);
		}
		if ($this->is_wp_file($data['file'])) {
			dark_debug(array(), '---------------wordpress folder cannot be inserted ----------------');
			return array('status' => 'success');
			return false;
		}

		$result = $this->db->insert("{$this->db->base_prefix}wptc_included_files", $data);

		if ($result) {
			return array('status' => 'success');
		}
		return array('status' => 'error');
	}
    
    private function remove_exclude_table($table, $force = false){
		if (empty($table)) {
			return false;
		}

		$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_tables WHERE table_name = %s", $table);
		$result = $this->db->query($re_sql);
	}
    
    private function remove_include_table($table, $force = false){
		if (empty($table)) {
			return false;
		}
		$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_tables WHERE table_name = %s", $table);
		$result = $this->db->query($re_sql);
	}
    
    public function get_files_by_key() {
        $path = $_POST['key'];
		$result_obj = WPTC_Base_Factory::get('Wptc_ExcludeOption')->get_files_by_path($path);  
		$result = $this->format_result_data($result_obj);
		return array('result' => $result);
	}
    
        
    private function process_wptc_login() {
        $options_helper = new Wptc_Options_Helper();          
        if($options_helper->get_is_user_logged_in()){            
            return array(
                'result' => 'is_user_logged_in',
                'sync_data' => $this->get_sync_data()
            );
        }   
                
        $email = $_POST['acc_email'];
        $pwd = $_POST['acc_pwd'];
        
        $config = WPTC_Base_Factory::get('Wptc_InitialSetup_Config');
		$options = WPTC_Factory::get('config');
        
		$config->set_option('wptc_main_acc_email_temp', base64_encode($email));
		$config->set_option('wptc_main_acc_pwd_temp', base64_encode(md5(trim( wp_unslash( $pwd ) ))));
		$config->set_option('wptc_token', false);

		$auth_result = $options->is_main_account_authorized($email, trim( wp_unslash( $pwd ) ));

		$privileges_wptc = $config->get_option('privileges_wptc');
		$privileges_wptc = json_decode($privileges_wptc);

		dark_debug($privileges_wptc, "--------privileges_wptc-----process_wptc_login---");

		if (empty($auth_result)) {
			return array('error' => 'Login failed.');
		}
        return array('result' => 'ok', 'sync_data' => $this->get_sync_data());
	}
   
    
    function save_settings_wptc(){
        $options_helper = new Wptc_Options_Helper();
        if(!$options_helper->get_is_user_logged_in()){            
            return array(                
                'sync_data' => $this->get_sync_data(),
                'error' => 'Login to your WP Time Capsule account first'
            );
        } 
        
        $return_array = array();
        $processed_files = WPTC_Factory::get('processed-files');
        $processed_files->get_current_backup_progress($return_array);
        
        if (is_array($return_array) && isset($return_array['backup_progress'])) {
            return array('error' => 'A backup is currently running. Please wait until it finishes to change settings.');
        }
        
        global $settings_ajax_start_time;
        $settings_ajax_start_time = time();
        $config = WPTC_Factory::get('config');
        $data = $_POST['data'];
        $config->set_option('schedule_time_str', $data['schedule_time_str']);
        $config->set_option('wptc_timezone', $data['wptc_timezone']);
        $config->set_option('anonymous_datasent', $data['anonymous_datasent']);
        $config->set_option('user_excluded_extenstions', $data['user_excluded_extenstions']);
        $config->set_option('backup_before_update_setting', $data['backup_before_update_setting']);
        $config->set_option('backup_type_setting', $data['backup_type_setting']);
        wptc_modify_schedule_backup();
        do_action('update_auto_updater_settings_wptc');
        return array('result' => 'over');
    }
    
    
    function start_fresh_backup_tc_callback_wptc() {
        $type = '';
        WPTC_Factory::get('Debug_Log')->wptc_log_now('Starting Manual Backup', 'BACKUP');
        reset_restore_if_long_time_no_ping();
        $config = WPTC_Factory::get('config');
        $result = is_wptc_cron_fine();
        dark_debug($result,'-----------is_wptc_cron_fine----------------');
        if($result == false){
            $config->set_option('in_progress', false);
            dark_debug(array(),'-----------Cron not connected so backup aborted----------------');
            return $this->send_response_wptc('declined_by_wptc_cron_not_connected', 'SCHEDULE');            
        }
        if ($config->get_option('in_progress', true)) {
            set_server_req_wptc(true);
            $config->set_option('recent_backup_ping', time());
            set_backup_in_progress_server(true);
            return $this->send_response_wptc('already_backup_running_and_retried', $type);
        }
        // $config->set_option('wptc_profiling_start', microtime(true));
        dark_debug(array(), '-----------in progress set 1-------------');
        $config->set_option('in_progress', true);
        $config->set_option('backup_before_update_details', false);
        WPTC_Factory::get('Debug_Log')->wptc_log_now('Manual Backup flags are set', 'BACKUP');

        dark_debug_func_map(func_get_args(), "--- function " . __FUNCTION__ . "--------", WPTC_WPTC_DARK_TEST_PRINT_ALL);
        
        $args = $_POST['data'];        

        do_action('just_initialized_fresh_backup_wptc_h', $args);

        $config->create_dump_dir(); //This will initialize wp_filesystem

        if (isset($_REQUEST['type'])) {
            $type = $_REQUEST['type'];
            if ($type == 'manual') {
                $config->set_option('wptc_current_backup_type', 'M');
            }
        }
        dark_debug($_REQUEST,'--------------$_REQUEST-------------');
        do_action('add_staging_req_h', time());
        dark_debug(array(), '---------coming backup 1------------');

        $config->set_option('file_list_point', 0);
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE `" . $wpdb->base_prefix . "wptc_current_process`");

        $backup = new WPTC_BackupController();
        $backup->delete_prev_records();
        dark_debug(array(), '---------coming backup sfdsfs------------');
        //$config->remove_garbage_files();
        $backup->backup_now($type);
        return array('result' => 'success');
    }

    public function save_manual_backup_name_wptc() {
		global $wpdb;        
        $backup_name = $_POST['backup_name'];        
		$backup_id = getTcCookie('backupID');
		$query = $wpdb->prepare("UPDATE {$wpdb->base_prefix}wptc_backup_names SET backup_name = %s WHERE `backup_id` = ".$backup_id."", $backup_name);
		dark_debug($query, '---------$query------------');
		$query_result = $wpdb->query($query);
        return array('result' => 'ok');		
	}
        
    function send_response_wptc($status = null, $type = null, $data = null, $is_log =0) {
		if (!is_wptc_server_req() && !is_wptc_node_server_req()) {
			return false;
		}
		$config = WPTC_Factory::get('config');
		dark_debug(get_backtrace_string_wptc(),'---------send_response_wptc-----------------');
		if (empty($is_log)) {
			$post_arr['status'] = $status;
			$post_arr['type'] = $type;
			$post_arr['version'] = WPTC_VERSION;
			$post_arr['source'] = 'WPTC';
			$post_arr['scheduled_time'] = $config->get_option('schedule_time_str');
			$post_arr['timezone'] = $config->get_option('wptc_timezone');
			$post_arr['last_backup_time'] = $config->get_option('last_backup_time');
			if (!empty($data)) {
				$post_arr['progress'] = $data;
			}
		} else {
			$post_arr = $data;
		}
		dark_debug($post_arr, '---------$post_arr------------');
        
        return array( 'result' => 'success', 'data' => "<WPTC_START>".json_encode($post_arr)."<WPTC_END>"  );		
    }

    private function format_result_data($file_obj){
		$files_arr	= array();        
		if (empty($file_obj)) {
			return false;
		}        
                
        $processed_files = WPTC_Factory::get('processed-files'); 
        
		foreach ($file_obj as $Ofiles) {
			$file_path = $Ofiles->getPathname();
			$file_name = basename($file_path);
             
			if ($file_name == '.' || $file_name == '..') {
				continue;
			}
			if (!$Ofiles->isReadable()) {
				continue;
			}
			$file_size = $Ofiles->getSize();
			$temp = array(
					'title' => basename($file_name),
					'key' => $file_path,
					'size' => $processed_files->convert_bytes_to_hr_format($file_size),
				);
			$is_dir = override_is_dir($file_path);
			if ($is_dir) {
				$is_excluded = $this->is_excluded_file($file_path, true);
				$temp['folder'] = true;
				$temp['lazy'] = true;
				$temp['size'] = '';
			} else {
				$is_excluded = $this->is_excluded_file($file_path, false);
				$temp['false'] = false;
				$temp['folder'] = false;
				$temp['size_in_bytes'] = $Ofiles->getSize();
			}
			if($is_excluded){
				$temp['partial'] = false;
				$temp['preselected'] = false;
			} else {
				$temp['preselected'] = true;
			}

			$files_arr[] = $temp;
		}
		$this->sort_by_folders($files_arr);
		// dark_debug($files_arr, '---------------$files_arr-----------------');
               
		return $files_arr;
	}

    private function sort_by_folders(&$files_arr) {
		if (empty($files_arr) || !is_array($files_arr)) {
			return false;
		}
		foreach ($files_arr as $key => $row) {
			$volume[$key]  = $row['folder'];
		}
		array_multisort($volume, SORT_DESC, $files_arr);
	}

    public function is_excluded_file($file, $is_dir = false){
		if (empty($file)) {
			return true;
		}       
        
		$file = wp_normalize_path($file);
		$found = false;
		if ($this->is_wp_file($file)) {
			return $this->exclude_file_check_deep($file);
		}
		if (!$this->is_included_file($file)) {
			return true;
		} else {
			return $this->exclude_file_check_deep($file);
		}
	}
    
    private function is_wp_file($file){
		if (empty($file)) {
			return false;
		}
		$file = wp_normalize_path($file);
		foreach ($this->default_wp_files_n_folders as $path) {
			if(strpos($file, $path) !== false){
				return true;
			}
		}
		return false;
	}
    
    private function is_included_file($file, $is_dir = false){
		$found = false;
		foreach ($this->included_files as $value) {
			$value = str_replace('(', '-', $value);
			$value = str_replace(')', '-', $value);
			$file = str_replace('(', '-', $file);
			$file = str_replace(')', '-', $file);
			if (preg_match('#^'.$value.DIRECTORY_SEPARATOR.'#', $file.DIRECTORY_SEPARATOR) === 1) {
				$found = true;
				break;
			}
		}
		return $found;
	}
    
    
    private function exclude_file_check_deep($file){
		foreach ($this->excluded_files as $value) {
			$value = str_replace('(', '-', $value);
			$value = str_replace(')', '-', $value);
			$file = str_replace('(', '-', $file);
			$file = str_replace(')', '-', $file);
			if(strpos($file.'/', $value.'/') === 0){
				return true;
			}
		}
		return false;
	}
    
    private function get_exlcuded_files_list(){
		$raw_data = $this->db->get_results("SELECT file FROM {$this->db->base_prefix}wptc_excluded_files", ARRAY_N);
		if (empty($raw_data)) {
			return array();
		}
		$result = array();
		foreach ($raw_data as $value) {
			$result[] = $value[0];
		}
                
		return empty($result) ? array() : $result;
	}
    
    
    private function get_included_files_list(){
		$raw_data = $this->db->get_results("SELECT file FROM {$this->db->base_prefix}wptc_included_files", ARRAY_N);
		if (empty($raw_data)) {
			return array();
		}
		$result = array();
		foreach ($raw_data as $value) {
			$result[] = $value[0];
		}
		return empty($result) ? array() : $result;
	}
    
    private function get_exlcuded_tables_list(){
		$raw_data = $this->db->get_results("SELECT table_name FROM {$this->db->base_prefix}wptc_excluded_tables", ARRAY_N);
		if (empty($raw_data)) {
			return array();
		}
		$result = array();
		foreach ($raw_data as $value) {
			$result[] = $value[0];
		}
		return empty($result) ? array() : $result;
	}

	private function get_included_tables_list(){
		$raw_data = $this->db->get_results("SELECT table_name FROM {$this->db->base_prefix}wptc_included_tables", ARRAY_N);
		if (empty($raw_data)) {
			return array();
		}
		$result = array();
		foreach ($raw_data as $value) {
			$result[] = $value[0];
		}
		return empty($result) ? array() : $result;
	}
    
	public function all_plugins( $plugins ) {
		foreach ( $plugins as $key => $value ) {
			$plugin_slug = basename( $key, '.php' );
			if ( 'wp-time-capsule' === $plugin_slug ) {
				unset( $plugins[ $key ] );
			}
		}

		return $plugins;
	}

	public function remove_menu() {
        remove_menu_page( 'wp-time-capsule-monitor' );
		$pos = stripos( $_SERVER['REQUEST_URI'], 'admin.php?page=wp-time-capsule-monitor' );
		if ( false !== $pos ) {
			wp_redirect( get_option( 'siteurl' ) . '/wp-admin/index.php' );
			exit();
		}
	}
    
	function remove_update_nag( $value ) {
		if ( isset( $_POST['mainwpsignature'] ) ) {
			return $value;
		}
		if ( isset( $value->response['wp-time-capsule/wp-time-capsule.php'] ) ) {
			unset( $value->response['wp-time-capsule/wp-time-capsule.php'] );
		}

		return $value;
	}
}

