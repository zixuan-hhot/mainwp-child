<?php

class MainWPChildBranding
{
    public static $instance = null;
    protected $child_plugin_dir;
    protected $settings = null;

    static function Instance()
    {
        if (MainWPChildBranding::$instance == null)
        {
            MainWPChildBranding::$instance = new MainWPChildBranding();
        }
        return MainWPChildBranding::$instance;
    }

    public function __construct()
    {
        $this->child_plugin_dir = dirname(dirname(__FILE__));        
        add_action('mainwp_child_deactivation', array($this, 'child_deactivation'));
        
        $label = get_option("mainwp_branding_button_contact_label");
        if (!empty($label)) {
            $label = stripslashes($label);
        } else 
            $label = "Contact Support";
        
        $this->settings['contact_support_label'] = $label;
    }

    public static function admin_init()
    {
        if (get_option('mainwp_branding_show_support') == 'T')
        {
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.css');
            add_action('wp_ajax_mainwp-child_branding_send_support_mail', array(MainWPChildBranding::Instance(), 'send_support_mail'));
        }
    }

    public function child_deactivation()
    {
        $dell_all = array('mainwp_branding_disable_change',
            'mainwp_branding_child_hide',
            'mainwp_branding_show_support',
            'mainwp_branding_support_email',
            'mainwp_branding_support_message',
            'mainwp_branding_remove_restore',
            'mainwp_branding_remove_setting',
            'mainwp_branding_remove_wp_tools',
            'mainwp_branding_remove_wp_setting',
            'mainwp_branding_plugin_header');
        foreach ($dell_all as $opt)
        {
            delete_option($opt);
        }
    }


    public function action()
    {
        $information = array();
        switch ($_POST['action'])
        {
            case 'update_branding':
                $information = $this->update_branding();
                break;
        }
        MainWPHelper::write($information);
    }

    public function update_branding()
    {
        $information = array();
        $settings = unserialize(base64_decode($_POST['settings']));
        if (!is_array($settings))
            return $information;

        $header = array('name' => $settings['child_plugin_name'],
            'description' => $settings['child_plugin_desc'],
            'author' => $settings['child_plugin_author'],
            'authoruri' => $settings['child_plugin_author_uri'],
            'pluginuri' => $settings['child_plugin_uri']);
        
        update_option('mainwp_branding_plugin_header', $header);
        update_option('mainwp_branding_support_email', $settings['child_support_email']);
        update_option('mainwp_branding_support_message', $settings['child_support_message']);
        update_option('mainwp_branding_remove_restore', $settings['child_remove_restore']);
        update_option('mainwp_branding_remove_setting', $settings['child_remove_setting']);
        update_option('mainwp_branding_remove_wp_tools', $settings['child_remove_wp_tools']);
        update_option('mainwp_branding_remove_wp_setting', $settings['child_remove_wp_setting']);
        update_option('mainwp_branding_button_contact_label', $settings['child_button_contact_label']);
        update_option('mainwp_branding_send_email_message', $settings['child_send_email_message']);
        

        if ($settings['child_plugin_hide'])
        {
            update_option('mainwp_branding_child_hide', 'T');
        }
        else
        {
            update_option('mainwp_branding_child_hide', '');
        }

        if ($settings['child_show_support_button'] && !empty($settings['child_support_email']))
        {
            update_option('mainwp_branding_show_support', 'T');
        }
        else
        {
            update_option('mainwp_branding_show_support', '');
        }

        if ($settings['child_disable_change'])
        {
            update_option('mainwp_branding_disable_change', 'T');
        }
        else
        {
            update_option('mainwp_branding_disable_change', '');
        }
        $information['result'] = 'SUCCESS';
        return $information;
    }


    public function branding_init()
    {
        add_filter('map_meta_cap', array($this, 'branding_map_meta_cap'), 10, 5);        
        add_filter('all_plugins', array($this, 'branding_child_plugin'));                
        if (get_option('mainwp_branding_show_support') == 'T')
        {
            add_submenu_page( null, $this->settings['contact_support_label'], $this->settings['contact_support_label'] , 'read', "ContactSupport", array($this, "contact_support") ); 
            add_action('admin_bar_menu', array($this, 'add_support_button'), 100);
            add_filter('update_footer', array(&$this, 'update_footer'), 15);
        }

    }
    
    public function send_support_mail()
    {
        $email = get_option('mainwp_branding_support_email');
        if (!empty($_POST['content']) && !empty($email))
        {
            $mail = '<p>Support Email from: <a href="' . site_url() . '">' . site_url() . '</a></p>';
            $mail .= '<p>Sent from WordPress page: ' . (!empty($_POST['from_page']) ? '<a href="' . $_POST['from_page'] . '">' . $_POST['from_page'] . '</a></p>' : "");
            $mail .= '<p>Admin email: ' . get_option('admin_email') . ' </p>';
            $mail .= '<p>Support Text:</p>';            
            $mail .= '<p>' . $_POST['content'] . '</p>';
            if (wp_mail($email, 'MainWP - Support Contact', $mail, array('From: "' . get_option('admin_email') . '" <' . get_option('admin_email') . '>', 'content-type: text/html'))) ;
            die('SUCCESS');
        }
        die('');
    }

    function contact_support()
    {       
    ?>
    <style>
        .ui-dialog {
            padding: .5em;
            width: 600px !important;
            overflow: hidden;
            -webkit-box-shadow: 0px 0px 15px rgba(50, 50, 50, 0.45);
            -moz-box-shadow: 0px 0px 15px rgba(50, 50, 50, 0.45);
            box-shadow: 0px 0px 15px rgba(50, 50, 50, 0.45);
            background: #fff !important;
            z-index: 99999;
        }

        .ui-dialog .ui-dialog-titlebar {
            background: none;
            border: none;
        }

        .ui-dialog .ui-dialog-title {
            font-size: 20px;
            font-family: Helvetica;
            text-transform: uppercase;
            color: #555;
        }

        .ui-dialog h3 {
            font-family: Helvetica;
            text-transform: uppercase;
            color: #888;
            border-radius: 25px;
            -moz-border-radius: 25px;
            -webkit-border-radius: 25px;
        }

        .ui-dialog .ui-dialog-titlebar-close {
            background: none;
            border-radius: 15px;
            -moz- border-radius : 15 px;
            -webkit- border-radius : 15 px;
            color: #fff;
        }

        .ui-dialog .ui-dialog-titlebar-close:hover {
            background: #7fb100;
        }

        .mainwp_info-box-yellow {
            margin: 5px 0 15px;
            padding: .6em;
            background: #ffffe0;
            border: 1px solid #e6db55;
            border-radius: 3px;
            -moz-border-radius: 3px;
            -webkit-border-radius: 3px;
            clear: both;
        }
        .ui-widget-overlay {
            background: url("images/ui-bg_flat_0_aaaaaa_40x100.png") repeat-x scroll 50% 50% #AAAAAA;
            opacity: 0.3;
        }
        .ui-widget-overlay {
            height: 100%;
            left: 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 90 !important;
        }        
    </style>

    <div style="width: 99%;">
        <h2><?php echo $this->settings['contact_support_label']; ?></h2>
        <div style="height: auto; margin-bottom: 10px; text-align: left">
            <div class="mainwp_info-box-yellow" id="mainwp_branding_contact_ajax_message_zone"
                 style="display: none;"></div>                
            <p><?php echo stripslashes(get_option('mainwp_branding_support_message')); ?></p>
            <textarea id="mainwp_branding_contact_message_content" name="mainwp_branding_contact_message_content"
                      cols="58" rows="7" class="text"></textarea>
        </div>
        <input id="mainwp-branding-contact-support-submit" type="button" name="submit" value="Submit"
               class="button-primary button" style="float: left"/>
    </div>
    <?php      
    $send_email_message = get_option("mainwp_branding_send_email_message");
    if (!empty($send_email_message)) {
        $send_email_message = stripslashes($send_email_message);
    } else 
        $send_email_message = "Support Contacted Successfully.";
    $from_page = urldecode($_GET['from_page']);    
    ?>
    <input type="hidden" id="mainwp_branding_send_from_page" name="mainwp_branding_send_from_page" value="<?php echo $from_page;?>" />
    <script>
        jQuery(document).ready(function ()
        {   
            jQuery('#mainwp-branding-contact-support-submit').live('click', function (event)
            {
                var messageEl = jQuery('#mainwp_branding_contact_ajax_message_zone');
                messageEl.hide();
                var content = jQuery('#mainwp_branding_contact_message_content').val();
                var from_page = jQuery('#mainwp_branding_send_from_page').val();

                if (jQuery.trim(content) == '')
                {
                    messageEl.html(__('You content message must not be empty.')).fadeIn();
                    return false;
                }
                jQuery(this).attr('disabled', 'true'); //Disable
                messageEl.html('Mail sending...').fadeIn(1000);
                var data = {
                    action:'mainwp-child_branding_send_support_mail',
                    content:content,
                    from_page: from_page
                };
                jQuery.ajax({
                    type:"POST",
                    url:ajaxurl,
                    data:data,
                    success:function (resp)
                    {
                        if (resp == 'SUCCESS')
                        {
                            messageEl.html("<?php echo addslashes($send_email_message); ?>" + ' ' + (from_page ? ('<a href="' + from_page + '" title="<?php _e("Go Back"); ?>"><?php _e("Go Back"); ?></a>') : '')).fadeIn(1000);
                        }
                        else
                        {
                            messageEl.css('color', 'red');
                            messageEl.html('Error send mail.').show();
                            jQuery('#mainwp-branding-contact-support-submit').removeAttr('disabled');
                            return;
                        }
                    }
                });
                return false;
            });

        });
    </script>
    <?php
    }

    /**
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_support_button($wp_admin_bar)
    {
        if (isset($_GET['from_page']))
            $href = admin_url('admin.php?page=ContactSupport&from_page=' . urlencode ($_GET['from_page']));
        else {                         
            $protocol = isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://';
            $fullurl = $protocol .$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
            $href = admin_url('admin.php?page=ContactSupport&from_page=' . urlencode($fullurl));
        }
        $args = array(
            'id' => false,
            'title' => $this->settings['contact_support_label'],
            'parent' => 'top-secondary',
            'href' => $href,
            'meta' => array('class' => 'mainwp_branding_support_top_bar_button', 'title' => $this->settings['contact_support_label'])
        );
        
        $wp_admin_bar->add_node($args);
    }


    public function branding_map_meta_cap($caps, $cap, $user_id, $args)
    {
        if (get_option('mainwp_branding_disable_change') == 'T')
        {
            // disable: edit, update, install, active themes and plugins
            if (strpos($cap, 'plugins') !== false || strpos($cap, 'themes') !== false || $cap == 'edit_theme_options')
            {
                //echo $cap."======<br />";
                $caps[0] = 'do_not_allow';
            }
        }
        return $caps;
    }

    public function branding_child_plugin($plugins)
    {
        if (get_option('mainwp_branding_child_hide') == 'T')
        {
            foreach ($plugins as $key => $value)
            {
                $plugin_slug = basename($key, '.php');
                if ($plugin_slug == 'mainwp-child')
                    unset($plugins[$key]);
            }
            return $plugins;
        }

        $header = get_option('mainwp_branding_plugin_header');
        if (is_array($header) && !empty($header['name']))
            return $this->update_child_header($plugins, $header);
        else
            return $plugins;
    }
   
    public function update_child_header($plugins, $header)
    {
        $plugin_key = "";
        foreach ($plugins as $key => $value)
        {
            $plugin_slug = basename($key, '.php');
            if ($plugin_slug == 'mainwp-child')
            {
                $plugin_key = $key;
                $plugin_data = $value;
            }
        }

        if (!empty($plugin_key))
        {
            $plugin_data['Name'] = stripslashes($header['name']);
            $plugin_data['Description'] = stripslashes($header['description']);            
            $plugin_data['Author'] = stripslashes($header['author']);
            $plugin_data['AuthorURI'] = stripslashes($header['authoruri']);
            if (!empty($header['pluginuri']))
                $plugin_data['PluginURI'] = stripslashes($header['pluginuri']);
            $plugins[$plugin_key] = $plugin_data;
        }
        return $plugins;
    }
}

