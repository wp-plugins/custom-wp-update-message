<?php
/*
Plugin Name:  Custom WP Update Message
Description:  This plugin allows you to edit the WordPress update message shown when a new version of WordPress is available.  This plugin is targeted toward web developers who want to provide support to their clients when updates are available.  You can enter your contact information, and a personalized message.  You can also determine which types of users see the message and which do not.
Version:      1.0
Author:       Computer Courage
Author URI:   http://www.computercourage.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

*/

function ccmwcu_admin_init() {
		register_setting('ccmwcu-options', 'ccmwcu_company_name');
		register_setting('ccmwcu-options', 'ccmwcu_company_email');
		register_setting('ccmwcu-options', 'ccmwcu_company_website');
		register_setting('ccmwcu-options', 'ccmwcu_company_phone');
		register_setting('ccmwcu-options', 'ccmwcu_update_message');
}
add_action('admin_init', 'ccmwcu_admin_init');

function ccmwcu_update_nag() {
	$name = get_option('ccmwcu_company_name') ? get_option('ccmwcu_company_name') : 'your administrator';
	$email = get_option('ccmwcu_company_email');
	$website = get_option('ccmwcu_company_website');
	$phone = get_option('ccmwcu_company_phone');
	$message = get_option('ccmwcu_update_message') ? get_option('ccmwcu_update_message') : "Click here to upgrade at your own risk";
	
	if ( is_multisite() && !current_user_can('update_core') )
		return false;

	global $pagenow;

	if ( 'update-core.php' == $pagenow )
		return;

	$cur = get_preferred_from_update_core();

	if ( ! isset( $cur->response ) || $cur->response != 'upgrade' )
		return false;
	
	if ( ccmwcu_current_user_can_view_message() ) {
		$msg = sprintf( __('<a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> is available! We recommend contacting %2$s to see if this update is recommended. <br />'), $cur->current, $name);
		if( $email ) 
			$msg .= sprintf( __('<strong>Email:</strong> <a href="mailto:%1$s">%1$s</a> '), $email );
		if( $phone ) 
			$msg .= sprintf( __('<strong>Phone:</strong> %1$s '), $phone );
		if( $website ) 
			$msg .= sprintf( __('<strong>Website:</strong> <a href="%1$s">%1$s</a> '), $website );
		if ( current_user_can('update_core') )
			$msg .= sprintf( __('<br /><a href="%1$s">%2$s</a>'), network_admin_url( 'update-core.php' ), $message );
	} else {
		$msg = sprintf( __('<a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> is available! Please notify the site administrator.'), $cur->current );
	}
	echo "<div class='update-nag'>$msg</div>";
}

function ccmwcu_remove_nag() {
	remove_action( 'admin_notices', 'update_nag', 3);
}

function ccmwcu_messages() {
	add_action('admin_notices', 'ccmwcu_remove_nag', 1);
	add_action( 'admin_notices', 'ccmwcu_update_nag', 1);
}
add_action('init', 'ccmwcu_messages', 1);

function ccmwcu_add_settings_link($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

	if ($file == $this_plugin) {
		$settings_link = '<a href="admin.php?page=modify-core-update-message.php">'.__("Settings", "ccmwcu").'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'ccmwcu_add_settings_link', 10, 2 );

function ccmwcu_update_options() {
	if( is_admin() ) {
		if( isset($_REQUEST['ccmwcu_core_update_settings']) && $_REQUEST['ccmwcu_core_update_settings'] == 'update' ) {			
			update_option('ccmwcu_company_name', ( trim($_POST['ccmwcu_company_name']) != '' ? stripslashes(trim($_POST['ccmwcu_company_name'])) : '' ));
			update_option('ccmwcu_company_email', ( trim($_POST['ccmwcu_company_email']) != '' ? trim($_POST['ccmwcu_company_email']) : '' ));
			update_option('ccmwcu_company_website', ( trim($_POST['ccmwcu_company_website']) != '' ? stripslashes(trim($_POST['ccmwcu_company_website'])) : '' ));
			update_option('ccmwcu_company_phone', ( trim($_POST['ccmwcu_company_phone']) != '' ? stripslashes(trim($_POST['ccmwcu_company_phone'])) : '' ));
			update_option('ccmwcu_update_message', ( trim($_POST['ccmwcu_update_message']) != '' ? stripslashes(trim($_POST['ccmwcu_update_message'])) : '' ));
			update_option('ccmwcu_users', ( $_POST['ccmwcu_users'] != '' ? $_POST['ccmwcu_users'] : '' ));
		}
	}
}
add_action('admin_init', 'ccmwcu_update_options');

function ccmwcu_options_page() { 
	if (is_admin()) {
		$name = get_option('ccmwcu_company_name');
		$email = get_option('ccmwcu_company_email');
		$website = get_option('ccmwcu_company_website');
		$phone = get_option('ccmwcu_company_phone');
		$message = get_option('ccmwcu_update_message');
		$users = get_option('ccmwcu_users');
		$users = maybe_unserialize($users);
	?>
		<style type="text/css">
			.ccmwcu_form label { display:inline-block; width:155px; vertical-align:middle; }
			.ccmwcu_form input[type="text"] { width:300px; }
		</style>
		<div class="wrap">
			<h2>Modify Wordpress Core Update Message</h2>
			<p>This plugin allows you to edit the WordPress update message shown when a new version of WordPress is available.  This plugin is targeted toward web developers who want to provide support to their clients when updates are available.  You can enter your contact information, and a personalized message.  You can also determine which types of users see the message and which do not.</p>
			<form class="ccmwcu_form" method="post">
				<ul>
					<li><label for="ccmwcu_company_name">Company Name</label> <input type="text" id="ccmwcu_company_name" name="ccmwcu_company_name" value="<?=$name?>" /></li>
					<li><label for="ccmwcu_company_email">Company Email</label> <input type="text" id="ccmwcu_company_email" name="ccmwcu_company_email" value="<?=$email?>" /></li>
					<li><label for="ccmwcu_company_website">Company Website</label> <input type="text" id="ccmwcu_company_website" name="ccmwcu_company_website" value="<?=$website?>" /></li>
					<li><label for="ccmwcu_company_phone">Company Phone</label> <input type="text" id="ccmwcu_company_phone" name="ccmwcu_company_phone" value="<?=$phone?>" /></li>
					<li><label for="ccmwcu_update_message">Ugrade Anyway Message</label> <input type="text" id="ccmwcu_update_message" name="ccmwcu_update_message" value="<?=$message?>" /></li>
					<li>Users who can view this custom message (unchecked users will see a "Please notify the site administrator" message)<br />
						<?php 
						$user_roles = ccmwcu_get_user_roles();
						foreach($user_roles as $urkey=>$ur) { 
						?>
							<input type="checkbox" name="ccmwcu_users[]" value="<?=$urkey?>" id="ccmwcu_<?=$urkey?>"
							<?php if( is_array($users) && in_array($urkey, $users) ) echo ' checked'; ?>
							<?php if( $ur['capabilities']['update_core'] ) echo ' checked disabled'; ?>
							> <label for="ccmwcu_<?=$urkey?>"><?php echo $ur['name']?></label><br />
						<?php
						}
						?>
					</li>
				</ul>
				<input type="hidden" name="ccmwcu_core_update_settings" value="update" />
				<input type="submit" name="ccmwcu_submit" value="Update Messages" />
			</form>
		</div>
<?php
	}
}
function ccmwcu_options_menu() {
	add_options_page( 'Custom WP Update Message', 'Custom WP Update Message', 'manage_options', __FILE__, 'ccmwcu_options_page');
}
add_action('admin_menu', 'ccmwcu_options_menu');

function ccmwcu_get_user_roles() {
	global $wpdb;

	$optionstable = $wpdb->prefix .'options';
	$optionsname = $wpdb->prefix.'user_roles';
	$getRolesQuery = "select option_id, option_value
					  from $optionstable
					  where option_name='$optionsname'
					  limit 0, 1";
	$record = $wpdb->get_results($getRolesQuery);
	if ($wpdb->last_error) {
		return;
	}
	$ure_roles = unserialize($record[0]->option_value);

	return $ure_roles;
}

function ccmwcu_current_user_can_view_message() {
	if( current_user_can('update_core') ) return true;
	
	$users = get_option('ccmwcu_users') ? get_option('ccmwcu_users') : '';
	$users = maybe_unserialize($users);
	foreach($users as $user_role) {
		if( current_user_can($user_role) ) return true;
	}	
	return false;
}
?>