<?php
/**
 * Core class for ActiveCampaign plugin
 * all function releted to remote server by curl api call
 * other input function
 */
if (!class_exists('activeCampaignCore')):
    class activeCampaignCore{
        
        //Need more implementation
        /**
         * Wraper function for communicating with ActiveCampaign system by curl
         * @param string $action api_action for ActiveCampaign API
         * @param array $params, API paramater
         * @param array $post, additional data for API update
         * @return array response data. contain $response['result_code'] = 1 for successfull action
         */
        function curl( $action, $params=array(), $post=array() ){
            $config = get_option('activecampaign_auth');            
            
            $params['api_user'] = $config['api_user'];
            $params['api_pass'] = $config['api_pass'];
            $params['api_action'] = $action;
            $params['api_output'] = 'serialize';          
            
            // This section takes the input fields and converts them to the proper format
            $query = "";
            foreach( $params as $key => $value ) $query .= $key . '=' . urlencode($value) . '&';
            $query = rtrim($query, '& ');          
            
            // This section takes the input data and converts it to the proper format
            $data = "";
            if($post){
                foreach( $post as $key => $value ) $data .= $key . '=' . urlencode($value) . '&';
                $data = rtrim($data, '& ');             
            }
                         
            $url = rtrim($config['api_url'], '/ ');
            $api = $url . '/admin/api.php?' . $query;                
            
            //Establish cURL
            $request = curl_init($api); // initiate curl object
            curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)   
            if($data)
                curl_setopt($request, CURLOPT_POSTFIELDS, $data); // use HTTP POST to send form data     
                     
            $response = (string)curl_exec($request); // execute curl fetch and store results in $response
            curl_close($request); // close curl object

            return unserialize($response); 
        }
        
        //Need more implementation
        function auth(){
            if ( !function_exists('curl_init') ) die('CURL not supported. (introduced in PHP 4.0.2)');
            $response = self::curl('user_view_username');
            $result['connected'] = false;
            if(!$response)
                $result['msg'] = 'Do not connected with remote server';
            else{
                if( $response['result_code'] )
                    $result['connected'] = true;
                else
                    $result['msg'] = 'Username and password do not match';
            }
            return $result;
        }
        

        /**
        * Check email in ActiveCampaign system
        * @patram: $email.
        * @return: ID of subsccriber if email found.
        */
        function subscriberExists( $email ){
            $param['email'] = $email;
            
            $response = self::curl('subscriber_view_email', $param);
            if($response['result_code'])
                return $response['id'];
        }           
        
        
        /**
        * Add or Edit Subscriber
        * @param int $id. if $id=0 then add new subscriber. For update, specify subscriber id
        * @param array $primary Primary Info. contain email,first_name and last_name keys.
        * @param array $listIDs.
        * @param array $fields custom field with key as field id and value as field value
        * @return: ID, if successfully added
        */
        function subscriberUpdate( $id=0, $primary, $listIDs, $fields=array() ){                                       
            $post['email'] = $primary['email'];
            $post['first_name'] = $primary['first_name'];
            $post['last_name'] = $primary['last_name'];      
            if($id){
                $post['id'] = $id;
                $action = 'subscriber_edit';
            }else
                $action = 'subscriber_add';            
                             
            if($fields)
                foreach($fields as $key=>$val) $post["field[$key,0]"] = $val;     
                           
            foreach($listIDs as $lid){
                $post["p[$lid]"] = $lid;
                $post["status[$lid]"] = 1;                
            }     
            
            $response = self::curl($action, '', $post);
            if($response['result_code']){
                if($id)
                    return true;
                else
                   return $response['subscriber_id']; 
            }                           
        }
              
            
        /**
         * Delete Subscriber
         * @param int $id. Subscriber id for deleted
         * @return bool true if successful, false otherwise
         */
        function subscriberDelete($id){            
            $param['id'] = $id;
            $response = self::curl('subscriber_delete', $param);
            return $response['result_code'];
        }                
         
         
        /**
         * Array of Subscriber List
         * @return array key as list id value as list name.
         */
        function getRemoteList(){
            $param['limit'] = 1000;
            $response = self::curl('list_paginator', $param);
            if($response['result_code']){
                foreach($response['rows'] as $list)
                    $listArray[$list['id']] = $list['name'];               
                return $listArray;
            }           
        }
        
        
        /**
         * Subscriber custom fields
         * @return array key as field id value as field title
         */
        function getRemoteFields(){
            $param['ids'] = 'all';
            $response = self::curl('list_field_view', $param);
            if($response['result_code']){
                foreach($response as $key => $field){
                    if(is_int($key))
                        $fieldArray[$field['id']] = $field['title'];                    
                }                                   
                return $fieldArray;
            }            
        }   
        
        
        /**
         * Populate field with wp data as ActiveCampaign field
         */
        function getUserDataByFields( $user_info, $user_role, $config) {
            $result['primary'] = array(
                'email' => $user_info->user_email,
                'first_name' => $user_info->first_name,
                'last_name' => $user_info->last_name            
            );
            
            if( is_array($config[$user_role]['field']) ){
                foreach( $config[$user_role]['field'] as $key => $val ) 
                    $result['field'][$key] = $user_info->$val;                
            }
            
            if( is_array($config[$user_role]['list']) ){
                foreach( $config[$user_role]['list'] as  $key => $val ) 
                    $result['list'][] = $key;                
            }            
  
            if($result['list'])
                return $result;
        }
                
        
        /**
         * For creating HTML input
         * @param string $name Name of input.
         * @param mixed $value selected input value.
         * @param string $type input type. Like text,select etc.
         * @param array $attribute css attribute. If id is not set name will use as id, use 'haveKey'=>true for dropdown key/value
         * @param array $option Options for dropdown input
         * @return printed input field
         */
        function createInput($name, $value='', $type='', $attribute=array(), $options=array()){
        
            $id = isset($attribute['id']) ? $attribute['id'] : $name;
            $class = isset($attribute['class']) ? $attribute['class'] : 'text-input';
            $style = isset($attribute['style']) ? $attribute['style'] : '';
            $default = isset($attribute['default']) ? $attribute['default'] : '';
            
            //set like 
            $extra = '';
            if(isset($attribute['extra'])){
                foreach ($attribute['extra'] as $key => $val)
                    $extra .= "$key='$val' ";
            }
            
            
            
            $style = isset($attribute['label']) ? $attribute['style'] : '';
            if($attribute['label'])
                echo "<label for='$id' style='display:block'>{$attribute['label']}</label>";
            
            //if no value found and default as value
            if($default AND !$value)
                $value = $default;
            
            if(!$type OR $type == 'text'){
                $input = "<input type='text' name='$name' id='$id' class='$class' value='$value' $extra/>";
            }elseif($type == 'password'){
                $input = "<input type='password' name='$name' id='$id' class='$class' value='$value' $extra/>";
            }elseif($type == 'dropdown' OR $type == 'select'){
                if(isset($options)){
                    $input = "<select name='$id' id='$name' class='$class' $extra>";
                    foreach($options as $key => $val){
                        if($attribute['haveKey'])
                            $input .= ($key == $value) ? "<option value='$key' selected='true'>$val</option>" : "<option value='$key'>$val</option>";
                        else    
                            $input .= ($val == $value) ? "<option value='$val' selected='true'>$val</option>" : "<option value='$val'>$val</option>";
                    }
                    $input .= "</select>";
                }
            }elseif($type == 'textarea'){
                $rows = isset($attribute['rows']) ? $attribute['rows'] : '';
                $cols = isset($attribute['cols']) ? $attribute['cols'] : '';
                $input = "<textarea rows='$rows' cols='$cols' name='$id' id='$name' class='$class' $extra>$value</textarea>";
            }elseif($type == 'checkbox'){
                $checked = $value ? "checked='checked'" : '';
                $input = "<input type='$type' name='$name' id='$id' class='$class' value='true' $checked $extra/>";
            }else{
                $input = "<input type='$type' name='$name' id='$id' class='$class' value='$value' $extra/>";
            }            
            echo $input;   
        }        


        /**
         * Remove empty value and array from array
         * @param array 
         * @param bool $keepEmptyArray true for keep and false for not keep. Default false.
         * @return array
         */
        function arrayRemoveEmptyValue($array, $keepEmptyArray=false){
            $result = array();
            foreach ( $array as $key=>$val ){
                if(is_array($val)){
                    $child = self::arrayRemoveEmptyValue($val);       
                    if($child)
                        $result[$key] = $child;
                    elseif($keepEmptyArray)
                        $result[$key] = $child;                  
                }else{
                    if($val)
                        $result[$key] = $val;             
                }
            }
            return $result;
        }        
                   
    }
endif;
?>