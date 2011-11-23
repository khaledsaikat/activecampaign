<?php

if (!class_exists('activeCampaignSync')):
    class activeCampaignSync{
                        
        function __construct(){       
            add_action('admin_menu', array(__CLASS__, 'admin_menu'));  
               
            add_action( 'wp_ajax_save_config', array(__CLASS__, 'save_config') );    
            add_action( 'wp_ajax_synchronizing', array(__CLASS__, 'synchronizing') ); 
                        
            add_action( 'user_register', array(__CLASS__, 'subscriber_update') );
            add_action( 'profile_update', array(__CLASS__, 'subscriber_update') ); 
            add_action( 'delete_user', array(__CLASS__, 'subscriber_delete') );
        }        
        
        
        function admin_menu(){
            $page = add_submenu_page( 'options-general.php', 'ActiveCampaign', 'ActiveCampaign', 'manage_options', 'activecampaign', array(__CLASS__, 'init'));
        }  
        
        
        function init(){
            global $activeCampaign;
            
            if($_POST['save_auth']){
                $auth = array(
                    'api_url' => $_POST['api_url'],
                    'api_user' => $_POST['api_user'],
                    'api_pass' => $_POST['api_pass'],
                );
                update_option('activecampaign_auth', $auth);
            }

            self::inputForm();
            //echo get_option('test_option');
//            $user = new WP_User( 779 );
//            echo "<pre>";
//            print_r($user);
//            echo "</pre>";
        }
        
        
        function inputForm(){
            global $activeCampaign, $wp_roles;
            
            $roles = $wp_roles->role_names;
            $roles = array_merge( array('all_users' => 'All Users'), $roles );  

            $activeCampaign->render('main', array(               
                'connectionStatus' => $activeCampaign->auth(),
                'auth' => get_option('activecampaign_auth'),
                'config' => get_option('activecampaign_config'),
                'roles' => $roles,
            ));
        }
                
        
        function save_config() {
            global $activeCampaign;
            
            $config = get_option('activecampaign_config');
            foreach( $_POST['post'] as $key => $val )
                $config[$key] = $val;
             
            $config = $activeCampaign->arrayRemoveEmptyValue($config);  
            update_option('activecampaign_config', $config);
            
            echo "Setting Saved!";
            die();
        }
        
        
        function synchronizing(){
            global $activeCampaign;

            $user_role = $_POST['user_role'];            
            $limit = 10;
            $config = get_option('activecampaign_config');
            $offset = $_POST['sync_from'] ? $_POST['sync_from']-1 : 0;
            $number = $_POST['sync_to'] ? $_POST['sync_to'] - $offset : $offset + $limit;
                        
            if ($user_role == "all_users")
                $user_role = "";
            
            $blogusers = get_users("orderby=id&role=$user_role&offset=$offset&number=$number");
                   
            if(!$blogusers)
                die();
                     
            foreach ($blogusers as $user) {                
                $subscriberID = $activeCampaign->subscriberExists( $user->user_email ); 
                if( !$subscriberID ) $subscriberID = 0;
                                
                $user_info = get_userdata($user->ID);
                $data = $activeCampaign->getUserDataByFields( $user_info, $user_role, $config);                
                if($data){
                    if( $_POST['add'] && !$subscriberID)
                        $activeCampaign->subscriberUpdate( $subscriberID, $data['primary'], $data['list'], $data['field'] );
                    elseif( $_POST['update'] && $subscriberID)
                        $activeCampaign->subscriberUpdate( $subscriberID, $data['primary'], $data['list'], $data['field'] );
                }
            }
            
            $from = $offset + 1;
            $to = $offset + $number;
            echo "Synchronization from $from to $to has completed";        

            die();
        }
        
        
        function subscriber_update($user_id){
            global $activeCampaign;
            $config = get_option('activecampaign_config');
            
            $user = new WP_User( $user_id );
            if( $config['all_users']['active'] )
                $user_role = 'all_users';
            else
               $user_role = $user->roles[0]; 
            
            if( !$config[$user_role]['active'] )
                return;
            
            $subscriberID = $activeCampaign->subscriberExists( $user->user_email );
            if( !$subscriberID ) $subscriberID = 0;
            $data = $activeCampaign->getUserDataByFields( $user, $user_role, $config); 
            $activeCampaign->subscriberUpdate( $subscriberID, $data['primary'], $data['list'], $data['field'] );
        }
        
        
        function subscriber_delete($user_id){
            global $activeCampaign;
            $config = get_option('activecampaign_config');
            
            $user = new WP_User( $user_id );
            if( $config['all_users']['active'] )
                $user_role = 'all_users';
            else
               $user_role = $user->roles[0]; 
            
            if( !$config[$user_role]['active'] )
                return;            
            
            $subscriberID = $activeCampaign->subscriberExists( $user->user_email );
            if($subscriberID)
                $activeCampaign->subscriberDelete($subscriberID);
        }  
        

         
    }
endif;

$activeCampaignSync = new activeCampaignSync;
?>