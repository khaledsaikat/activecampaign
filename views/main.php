<?php
global $activeCampaign;

if( $connectionStatus['connected'] ){
    $remoteList = $activeCampaign->getRemoteList();
    $remoteFields = $activeCampaign->getRemoteFields();                   
}
?>

<link rel="stylesheet" href="<?php echo $activeCampaign->pluginUrl(); ?>/css/jquery.ui.core.css" />
<link rel="stylesheet" href="<?php echo $activeCampaign->pluginUrl(); ?>/css/jquery.ui.tabs.css" />
<link rel="stylesheet" href="<?php echo $activeCampaign->pluginUrl(); ?>/css/jquery.ui.theme.css" />
<link rel="stylesheet" href="<?php echo $activeCampaign->pluginUrl(); ?>/css/jquery-ui.css" />
<!--<link rel="stylesheet" href="<?php //echo $activeCampaign->pluginUrl(); ?>/css/demos.css" />-->
<link rel="stylesheet" href="<?php echo $activeCampaign->pluginUrl(); ?>/css/custom.css" />
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery-1.6.2.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery-ui.min.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery.ui.core.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery.ui.position.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery.ui.widget.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery.ui.tabs.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/jquery.ui.dialog.js"></script>
<script src="<?php echo $activeCampaign->pluginUrl(); ?>/js/custom.js"></script>

<style type="text/css">
.loading { 
    background:url(images/loading.gif) left top no-repeat;
    margin-left: 5px;
    padding-left: 20px;
    display: none;
}
</style>

<div class="wrap">
    <h2>ActiveCampaign Synchronization</h2>
    <div id="tabs">
    	<ul>
    		<li><a href="#tabs-1">Authentication</a></li>
    		<li><a href="#tabs-2">Synchronize Setting</a></li>
            <li><a href="#tabs-3">About</a></li>
    	</ul>
    	<div id="tabs-1">
            <form method="post" action="">
                <p><?php $activeCampaign->createInput('api_url', $auth['api_url'], 'text', array('label'=>'Application URL')); ?></p>
                <p><?php $activeCampaign->createInput('api_user', $auth['api_user'], 'text', array('label'=>'Application Username')); ?></p>
                <p><?php $activeCampaign->createInput('api_pass', $auth['api_pass'], 'password', array('label'=>'Application Password')); ?></p>
                <p><input type="submit" name="save_auth" value="Save"/></p>            
                <?php if( $connectionStatus['connected'] ): ?>
                    <p>ActiveCampaign are Connected with <b><?php echo $auth['api_url']; ?></b></p>
                <?php else: ?>
                    <?php echo $connectionStatus['msg']; ?>
                <?php endif; ?>
            </form>                
    	</div>
    	<div id="tabs-2">            
            <?php if(is_array($remoteList)) : ?>
            <div id="accordion">
                <?php foreach($roles as $role_name => $role_title) : ?>
                	<h3><a href="#"><?php echo $role_title; ?></a></h3>
                	<div>
                        <form name="config_form" id="config_form_<?php echo $role_name; ?>" method="post" action="" onsubmit="save_config('<?php echo $role_name; ?>');return false;" >
                        <input type='hidden' name='action' value='save_config' />
                        
                        <p><?php $activeCampaign->createInput( "post[$role_name][active]", $config[$role_name]['active'], "checkbox" ); ?>
                         Auto Synchronize while user added/updated/deleted.
                         <?php if($role_name == 'all_users') echo " (If set for All User, no other role will be auto synchronized)"; ?></p>
                        <p><?php foreach($remoteList as $key => $val): ?>
                            <?php $activeCampaign->createInput( "post[$role_name][list][$key]", $config[$role_name]['list'][$key], "checkbox" ); ?>
                            <?php echo " $val "; ?>
                        <?php endforeach; ?></p>
                        <?php foreach($remoteFields as $key => $val): ?>   
                            <p><?php $activeCampaign->createInput("post[$role_name][field][$key]", $config[$role_name]['field'][$key], "text", array('label'=>$val)); ?></p>
                         <?php endforeach; ?>
                        <p><input type="submit" name="saveSetting" class="saveSetting" value="Save" />
                        <span id="saving_<?php echo $role_name; ?>" class="loading">Saving...</span>
                        <span id="saving_response_<?php echo $role_name; ?>" class="updated"></span>                    
                        </p>                    
                        <p><a href="#" onclick="call_sync_dialog('<?php echo $role_name; ?>');return false;" >Synchronize Now</a></p>
                    	</form>
                        <div id="save_config_msg"></div>                                         
                	</div>                            
                <?php endforeach; ?>
            </div>  
            <div>
                <p>Default wordpress recognized user fields are: user_login, user_nicename, user_email, user_url, display_name, first_name, last_name, nickname, description, aim, yim, jabber</p>
            </div>
            <?php else: ?>
                <p>Setting will available after connecting with remote server. And there should at least one list in remote server.</p>
            <?php endif; ?>              
    	</div>
        <div id="tabs-3">
            <p>This plugin has sponsored by David Ciccolella (Founder/President of <a href="http://wedalertnetwork.com" target="_blank">WedAlertNetwork</a>)</p>
            <p>Developed by <a href="http://khaledsaikat.com" target="_blank">Khaled Saikat</a>.</p>
<!--If you find this plugins useful, please consider making a donation to keep the coffee brewing.
Thank you, <a href="http://khaledsaikat.com" target="_blank">Khaled Saikat</a>.</p>
            <p><a href="http://khaledsaikat.com/donate/"><img style="text-align: center;margin: auto; width:125px; height: 125px;" src="<?php //echo $activeCampaign->pluginUrl(); ?>/images/donation.jpg" alt=""/></a></p>-->
        </div>
    </div>
      
    <div id="sync_dialog" title="Synchronization" style="display:none">
        <form id='sync_form' name='sync_form' onsubmit='sync();return false;' method='post' action='' >
            <input type='hidden' name='action' value='synchronizing' />
            <p><?php $activeCampaign->createInput('user_role', '', 'text', array('label'=>'Role', 'extra'=>array('readonly'=>'readonly'))); ?></p>
            <p><?php $activeCampaign->createInput('sync_from', '', 'text', array('label'=>'From')); ?></p>
            <p><?php $activeCampaign->createInput('sync_to', '', 'text', array('label'=>'To')); ?></p>
            <p><?php $activeCampaign->createInput('add', true, 'checkbox'); ?> Add new subscriber</p>
            <p><?php $activeCampaign->createInput('update', true, 'checkbox'); ?> Update existing subscriber</p>
            <p><?php $activeCampaign->createInput('auto_sync', true, 'checkbox'); ?> Auto Submit</p>
            <p><input type='submit' name='sync_button' id='sync_button' value='Synchronize' /></p> 
            <p><a href="#" id="sync_stop" style="display:none;" onclick="sync_stop();return false;">Stop Synchronization</a></p>
            <p><a href="#" id="sync_close" onclick="close_sync_dialog();return false;">Close</a></p>               
            <div id="loading_sync" class="loading">Synchronizing...</div>
            <div id="sync_status"></div>        
        </form>
    </div>
</div>