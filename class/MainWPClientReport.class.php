<?php

class MainWPClientReport
{   
    public static $instance = null;   
    
    static function Instance() {
        if (MainWPClientReport::$instance == null) {
            MainWPClientReport::$instance = new MainWPClientReport();
        }
        return MainWPClientReport::$instance;
    }    
    
    public function __construct() {
        global $wpdb;
        add_action('mainwp_child_deactivation', array($this, 'child_deactivation'));
    }
    
    public function child_deactivation()
    {
       
    }
    
    public function action() {   
        $information = array();
        if (!function_exists('wp_stream_query')) {
            $information['error'] = 'NO_STREAM';
            MainWPHelper::write($information);
        }   
        if (isset($_POST['mwp_action'])) {
            switch ($_POST['mwp_action']) {
                case "get_stream":
                    $information = $this->get_stream();
                break;                
            }        
        }
        MainWPHelper::write($information);
    }  
        
    public function get_stream() {        
        // Filters
        $allowed_params = array(
                'connector',
                'context',
                'action',
                'author',
                'author_role',
                'object_id',
                'search',
                'date',
                'date_from',
                'date_to',
                'record__in',
                'blog_id',
                'ip',
        );
        
        $sections = isset($_POST['sections']) ? unserialize(base64_decode($_POST['sections'])) : array();
        if (!is_array($sections))
            $sections = array();
        
        $other_tokens = isset($_POST['other_tokens']) ? unserialize(base64_decode($_POST['other_tokens'])) : array();
        if (!is_array($tokens))
            $tokens = array();
        
        unset($_POST['sections']);
        unset($_POST['other_tokens']);
        
        $args = array();  
        foreach ( $allowed_params as $param ) {                                            
                $paramval = wp_stream_filter_input( INPUT_POST, $param );                
                if ( $paramval || '0' === $paramval ) {
                        $args[ $param ] = $paramval;
                }
        }
        
        foreach ( $args as $arg => $val ) { 
            if (!in_array($arg, $allowed_params)) {
                unset($args[$arg]);
            }                
        }        
        if (isset($args['date_from']))
            $args['date_from'] = date("Y-m-d H:i:s", $args['date_from']);
        
        if (isset($args['date_to']))
            $args['date_to'] = date("Y-m-d H:i:s", $args['date_to']);
        
        $records = wp_stream_query( $args );
        if (!is_array($records)) 
            $records = array();
        
        $other_tokens_data = $this->get_other_tokens_data($records, $other_tokens);
        
        $sections_data = array();        
        foreach($sections as $sec => $tokens) {
            $sections_data[$sec] = $this->get_section_loop_data($records, $tokens, $sec);
        }
            
        $information = array('other_tokens_data' => $other_tokens_data,
                             'sections_data' => $sections_data );            
        
        return $information;
    }
    
    function get_other_tokens_data($records, $tokens) {
        $convert_context_name = array(
            "comment" => "comments",
            "plugin" => "plugins",
            "profile" => "profiles",
            "session" => "sessions",
            "setting" => "settings",
            "setting" => "settings",
            "theme" => "themes"            
        );
        
        $allowed_data = array(                             
            'count'          
        );
        $token_values = array();
        foreach ($tokens as $token) {
               $str_tmp = str_replace(array('[', ']'), "", $token);
               $array_tmp = explode(".", $str_tmp);  

               if (is_array($array_tmp)) {
                   $context = $action = $data = "";
                   if (count($array_tmp) == 2) {
                       list($context, $data) = $array_tmp;  
                   } else if (count($array_tmp) == 3) {
                       list($context, $action, $data) = $array_tmp;                        
                   }       

                   $context = isset($convert_context_name[$context]) ? $convert_context_name[$context] : $context;

                   switch ($data) {                      
                       case "count": 
                           $count = 0;
                           foreach ($records as $record) {                
                               if ($context == $record->context && $action == $record->action) {
                                   $count++;
                               }               
                           }     
                           $token_values[$token] = $count;                         
                           break;                
                   }            
               } 
        }            
        return $token_values;        
    }
    
    function get_section_loop_data($records, $tokens, $section) {
        
//        $convert_context_name = array(
//            "comment" => "comments",
//            "plugin" => "plugins",
//            "profile" => "profiles",
//            "session" => "sessions",
//            "setting" => "settings",
//            "setting" => "settings",
//            "theme" => "themes"            
//        );
        
        $allowed_data = array(            
            'name',
            'title',
            'oldversion',
            'currentversion',
            'date',            
            'count'          
        );
        
        $context = $action = "";        
        $str_tmp = str_replace(array('[', ']'), "", $section);
        $array_tmp = explode(".", $str_tmp);
        
        if (is_array($array_tmp)) 
            list($str1, $context, $action) = $array_tmp;

        
        $loops = array();
        $loop_count = 0;
        
        foreach ($records as $record) {  
            $token_values = array();
            if ($context == $record->context && $action == $record->action) {                
                foreach ($tokens as $token) {
                    $data = "";
                    $str_tmp = str_replace(array('[', ']'), "", $token);
                    $array_tmp = explode(".", $str_tmp);                                         
                    if (count($array_tmp) == 1) {
                        list($data) = $array_tmp;  
                    } else if (count($array_tmp) == 2) {
                        list($str1, $data) = $array_tmp;                        
                    }    
                    
                    switch ($data) {
                        case "date":
                            $token_values[$token] = $record->created;                            
                            break;
                        case "name":   
                            $token_values[$token] = $this->get_stream_meta_data($record->ID, $data);                                                  
                            break;
                        case "title":   
                            $token_values[$token] = $this->get_stream_meta_data($record->ID, $data, $context);                                                                                 
                            break;
                        default:   
                            $token_values[$token] = $token;                                                                                 
                            break;
                    }                                
                }
            }
            
            if (!empty($token_values)) {
                $loops[$loop_count] = $token_values;
                $loop_count++;
            }
        }
        return $loops;
    }
    
    function get_stream_meta_data($record_id, $data, $context = "") {        
        if (($context == "post" || $context == "page") && $data == "title") {
            $meta_key = "post_title";
        } else {
            $meta_key = $data;
        }
        global $wpdb;
        
        if (class_exists('WP_Stream_Install'))
            $prefix = WP_Stream_Install::$table_prefix;
        else
            $prefix = $wpdb->prefix;
        
	$sql    = "SELECT meta_value FROM {$prefix}stream_meta WHERE record_id = " . $record_id . " AND meta_key = '" . $meta_key . "'";
	$meta   = $wpdb->get_row( $sql );
        
        if ($meta)
            return $meta->meta_value;
        return "";            
    }
    
}

