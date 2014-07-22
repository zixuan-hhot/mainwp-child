<?php

class MainWPChildLinksChecker
{   
    
    public static $instance = null;   
    
    static function Instance() {
        if (MainWPChildLinksChecker::$instance == null) {
            MainWPChildLinksChecker::$instance = new MainWPChildLinksChecker();
        }
        return MainWPChildLinksChecker::$instance;
    }  
    
    public function __construct() {
        
    }
    
    public function action() {   
        $information = array();
        if (!defined('BLC_ACTIVE')) {
            $information['error'] = 'NO_BROKENLINKSCHECKER';
            MainWPHelper::write($information);
        }   
        if (isset($_POST['mwp_action'])) {
            switch ($_POST['mwp_action']) {                
                case "set_showhide":
                    $information = $this->set_showhide();                    
                    break;
                case "sync_data":
                    $information = $this->sync_data();                    
                    break;
            }        
        }
        MainWPHelper::write($information);
    }  
   
    public function init()
    {          
        if (get_option('mainwp_linkschecker_ext_enabled') !== "Y")
            return;
        
        if (get_option('mainwp_linkschecker_hide_plugin') === "hide")
        {
            add_filter('all_plugins', array($this, 'hide_plugin'));               
            add_filter('update_footer', array(&$this, 'update_footer'), 15);   
        }        
    }        
            
    public function hide_plugin($plugins) {
        foreach ($plugins as $key => $value)
        {
            $plugin_slug = basename($key, '.php');
            if ($plugin_slug == 'broken-link-checker')
                unset($plugins[$key]);
        }
        return $plugins;       
    }
 
    function update_footer($text){                
        ?>
           <script>
                jQuery(document).ready(function(){
                    jQuery('#menu-tools a[href="tools.php?page=view-broken-links"]').closest('li').remove();
                    jQuery('#menu-settings a[href="options-general.php?page=link-checker-settings"]').closest('li').remove();
                });        
            </script>
        <?php        
        return $text;
    }
    
    
     function set_showhide() {
        MainWPHelper::update_option('mainwp_linkschecker_ext_enabled', "Y");        
        $hide = isset($_POST['showhide']) && ($_POST['showhide'] === "hide") ? 'hide' : "";
        MainWPHelper::update_option('mainwp_linkschecker_hide_plugin', $hide);        
        $information['result'] = 'SUCCESS';
        return $information;
    }
    
    function sync_data($strategy = "") {  
        $information = array();           
        if (!defined('BLC_ACTIVE') && !function_exists('blc_init')) {
            $information['error'] = 'NO_BROKENLINKSCHECKER';
            MainWPHelper::write($information);
        }     
        blc_init();
        $data = array();
        $data['broken'] = self::sync_counting_data('broken');
        $data['redirects'] = self::sync_counting_data('redirects');
        $data['dismissed'] = self::sync_counting_data('dismissed');
        $data['all'] = self::sync_counting_data('all');  
        $data['link_data'] = self::sync_link_data();          
        $information['data'] = $data;
        return $information;
    }
    
    static function sync_counting_data($filter) {       
        global $wpdb;
        
        $all_filters = array(
            'broken' => '( broken = 1 )',
            'redirects' => '( redirect_count > 0 )',                
            'dismissed' => '( dismissed = 1 )',                
            'all' => '1'
        );
        
        $where = $all_filters[$filter];
        if (empty($where))
            return 0;
        
        return blc_get_links(array('count_only' => true, 'where_expr' => $where));
    }
    
    static function sync_link_data() {        
        $links = blc_get_links(array('load_instances' => true));
        $get_fields = array(
            'link_id',
            'url',
            'being_checked',
            'last_check',
            'last_check_attempt',
            'check_count',
            'http_code',
            'request_duration',
            'timeout',
            'redirect_count',
            'final_url',
            'broken', 
            'first_failure',
            'last_success',
            'may_recheck',
            'false_positive',
            //'result_hash',
            'dismissed', 
            'status_text',
            'status_code',
            'log',
        );
        $return = "";
        $site_id = $_POST['site_id'];
        $blc_option = get_option('wsblc_options');
        if (is_array($links)) {
            foreach($links as $link) {
                $lnk = new stdClass();
                foreach($get_fields as $field) {
                    $lnk->$field = $link->$field;
                }
                
                if (!empty($link->post_date) ) {
                    $lnk->post_date = $link->post_date;   
                } 
                
                $days_broken = 0;
                if ( $link->broken ){
                        //Add a highlight to broken links that appear to be permanently broken
                        $days_broken = intval( (time() - $link->first_failure) / (3600*24) );
                        if ( $days_broken >= $blc_option['failure_duration_threshold'] ){
                                $lnk->permanently_broken = 1;
                                if ( $blc_option['highlight_permanent_failures'] ){
                                    $lnk->permanently_broken_highlight = 1;
                                }
                        }
                }
                $lnk->days_broken = $days_broken;
                if ( !empty($link->_instances) ){			
                    $instance = reset($link->_instances); 
                    $lnk->link_text = $instance->ui_get_link_text();                    
                    $lnk->count_instance = count($link->_instances);                    
                    $container = $instance->get_container(); /** @var blcContainer $container */
                    $lnk->container = $container;
                    
                    if ( !empty($container) && ($container instanceof blcAnyPostContainer) ) {                        
                        $lnk->container_type = $container->container_type;
                        $lnk->container_id = $container->container_id;
                    }
                    
                    $can_edit_text = false;
                    $can_edit_url = false;
                    $editable_link_texts = $non_editable_link_texts = array();
                    $instances = $link->_instances;
                    foreach($instances as $instance) {
                            if ( $instance->is_link_text_editable() ) {
                                    $can_edit_text = true;
                                    $editable_link_texts[$instance->link_text] = true;
                            } else {
                                    $non_editable_link_texts[$instance->link_text] = true;
                            }

                            if ( $instance->is_url_editable() ) {
                                    $can_edit_url = true;
                            }
                    }

                    $link_texts = $can_edit_text ? $editable_link_texts : $non_editable_link_texts;
                    $data_link_text = '';
                    if ( count($link_texts) === 1 ) {
                            //All instances have the same text - use it.
                            $link_text = key($link_texts);
                            $data_link_text = esc_attr($link_text);
                    }
                    $lnk->data_link_text =  $data_link_text;
                    $lnk->can_edit_url =  $can_edit_url;
                    $lnk->can_edit_text =  $can_edit_text;                    
		} else {
                    $lnk->link_text = "";
                    $lnk->count_instance = 0;
                }                
                $lnk->site_id = $site_id; 
                                
                $return[] = $lnk;            
            }
        } else 
            return "";
        
        return $return;
  
    }   
}

