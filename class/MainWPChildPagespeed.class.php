<?php

class MainWPChildPagespeed
{   
    
    public static $instance = null;   
    
    static function Instance() {
        if (MainWPChildPagespeed::$instance == null) {
            MainWPChildPagespeed::$instance = new MainWPChildPagespeed();
        }
        return MainWPChildPagespeed::$instance;
    }  
    
    public function __construct() {
 
    }
    
    public function action() {   
        $information = array();
        if (!defined('GPI_ACTIVE')) {
            $information['error'] = 'NO_GOOGLEPAGESPEED';
            MainWPHelper::write($information);
        }   
        if (isset($_POST['mwp_action'])) {
            switch ($_POST['mwp_action']) {                
                case "save_settings":
                    $information = $this->save_settings();
                case "set_showhide":
                    $information = $this->set_showhide();                    
                break;
            }        
        }
        MainWPHelper::write($information);
    }  
   
    public function init()
    {  
        if (get_option('mainwp_pagespeed_ext_enabled') !== "Y")
            return;
        
        if (get_option('mainwp_pagespeed_hide_plugin') === "hide")
        {
            add_filter('all_plugins', array($this, 'hide_plugin'));   
            add_action('admin_menu', array($this, 'hide_menu'), 999);
        }
    }        
    
    public function hide_plugin($plugins) {
        foreach ($plugins as $key => $value)
        {
            $plugin_slug = basename($key, '.php');
            if ($plugin_slug == 'google-pagespeed-insights')
                unset($plugins[$key]);
        }
        return $plugins;       
    }
    
    public function hide_menu() {
        global $submenu;     
        if (isset($submenu['tools.php'])) {
            foreach($submenu['tools.php'] as $key => $menu) {
                if ($menu[2] == 'google-pagespeed-insights') {
                    unset($submenu['tools.php'][$key]);
                    break;
                }
            }
        }
    }    
    
     function set_showhide() {
        MainWPHelper::update_option('mainwp_pagespeed_ext_enabled', "Y");        
        $hide = isset($_POST['showhide']) && ($_POST['showhide'] === "hide") ? 'hide' : "";
        MainWPHelper::update_option('mainwp_pagespeed_hide_plugin', $hide);        
        $information['result'] = 'SUCCESS';
        return $information;
    }
    
    function save_settings() {
        MainWPHelper::update_option('mainwp_pagespeed_ext_enabled', "Y");        
        $settings = $_POST['settings'];
        $settings = unserialize(base64_decode($settings));
        
//        $settings = array('api_key' => $api_key, 
//                                'response_language' => $response_language, 
//                                'report_type' => $report_type, 
//                                'report_expiration' => $report_expiration,
//                                'check_report' => $report_check,
//                                'max_execution_time' => $exec_time, 
//                                'delay_time' => $delay_time,
//                                'log_exception' => $log_api_exception,
//                                'scan_technical' => $scan_tech,
//                                'delete_data' => $delete_data,
//                               );
        
        if (is_array($settings)) {
            $current_values = get_option('gpagespeedi_options');
            
            if (isset($settings['api_key']))                
                $current_values['google_developer_key'] = $settings['api_key'];
            
            if (isset($settings['response_language']))                
                $current_values['response_language'] = $settings['response_language'];
            
            if (isset($settings['max_execution_time']))                
                $current_values['max_execution_time'] = $settings['max_execution_time'];
            
            if (isset($settings['delay_time']))                
                $current_values['sleep_time'] = $settings['delay_time'];    
            
//                'sleep_time'                => $sleep_time,
//                'log_api_errors'            => $log_api_errors,
//                'scan_method'               => $scan_method,
//                'recheck_interval'          => $recheck_interval,
//                'check_pages'               => $check_pages,
//                'check_posts'               => $check_posts,
//                'cpt_whitelist'             => $cpt_whitelist,
//                'check_categories'          => $check_categories,
//                'first_run_complete'        => $options['first_run_complete'],
//                'last_run_finished'         => $options['last_run_finished'],
//                'bad_api_key'               => false,
//                'pagespeed_disabled'        => false,
//                'new_ignored_items'         => false,
//                'backend_error'             => false,
//                'new_activation_message'    => false
            
            if (update_option( 'gpagespeedi_options', $current_values ))
                $information['result'] = 'SUCCESS';
            else 
                $information['result'] = 'NOTCHANGE';
        }
        
        return $information;
    }
    
}

