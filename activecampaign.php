<?php
/*
Plugin Name: ActiveCampaign Synchronization
Plugin URI: http://wordpress.org/extend/plugins/activecampaign
Description: Synchronize wordpress user as your ActiveCampaign email marketing software subscriber.
Version: 1.0.1
Author: Khaled Hossain Saikat
Author URI: http://khaledsaikat.com
*/

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
    exit('Please don\'t access this file directly.');

require_once('classes/core.php');    

if (!class_exists('activeCampaign')):
    class activeCampaign extends activeCampaignCore {
                     
        function __construct(){       
            register_activation_hook(__FILE__, array(__CLASS__, 'pluginInstall'));
        }        
       
       
        function pluginUrl(){
            return path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ )));
        }       
        
        function pluginPath(){
            return dirname( __FILE__ );
        }  
        
        
        function pluginInstall(){           
           // update_option('activecampaign_config', $config);
        }           
        
          
        function render($viewName, $parameter){
            extract($parameter);
            include (self::pluginPath().'/views/'.$viewName.'.php');
        }              
               
    }
endif;

global $activeCampaign;
$activeCampaign = new activeCampaign;

require_once('classes/sync.php');

?>