<?php
/*
 * Plugin Name:  Custom WP Update Message
 * Description:  This plugin allows you to edit the WordPress update message shown when a new version of WordPress is available.  This plugin is targeted toward web developers who want to provide support to their clients when updates are available.  You can enter your contact information, and a personalized message.  You can also determine which types of users see the message and which do not.
 * Version:      1.0.2
 * Author:       Computer Courage
 * Author URI:   http://www.computercourage.com
 * Text Domain:  ccmwcu
 * License: 	 GPL2
 */

register_uninstall_hook( __FILE__, array( 'ccourage_update_message', 'on_uninstall' ) );

ccourage_update_message::init();

class ccourage_update_message {
	static public $optionkey = 'ccmwcu_options';
	static public $settingsname = 'ccmwcu_options';
	static public $pageslug = 'ccmwcu';
	static public $textdomain = 'ccmwcu';
	
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'set_messages' ), 1 );
	}
	
	function remove_nag() {
		remove_action( 'admin_notices', 'update_nag', 3);
	}
	function set_messages() {
		add_action( 'admin_notices', array( __CLASS__, 'remove_nag' ), 1 );
		add_action( 'admin_notices', array( __CLASS__, 'update_nag' ), 1 );
	}
	
	public static function register_menu_page() {
		add_options_page( __('Custom WP Update Message', self::$textdomain), __('Custom WP Update Message', self::$textdomain), 'manage_options', self::$pageslug, array( __CLASS__, 'settings_page' )	);
	}
	
	public static function register_settings() {
		add_settings_section( self::$settingsname, 'Settings', array( __CLASS__, 'settings_section' ), self::$pageslug );

		$fields = array(
			'company_name' 		=> __( 'Company Name', self::$textdomain ),
			'company_email'		=> __( 'Company Email', self::$textdomain ),
			'company_website' 	=> __( 'Company Website', self::$textdomain ),
			'company_phone' 	=> __( 'Company Phone', self::$textdomain ),
			'update_message' 	=> __( 'Ugrade Anyway Message', self::$textdomain ),
			'users' 			=> __( 'Users who can view this custom message (unchecked users will see a "Please notify the site administrator" message)', self::$textdomain ),
		);
		
		foreach($fields as $key => $label) {
			add_settings_field(
				'ccmwcu_' . $key,
				$label,
				array( __CLASS__, 'settings_field' ),
				self::$pageslug,
				self::$settingsname,
				array( 'key' => $key )	
			);
		}

		register_setting( self::$settingsname, self::$optionkey, array( __CLASS__, 'validate_field' ) );	
	}
	
	public static function settings_section() {
		_e( 'This plugin allows you to edit the WordPress update message shown when a new version of WordPress is available. This plugin is targeted toward web developers who want to provide support to their clients when updates are available. You can enter your contact information, and a personalized message. You can also determine which types of users see the message and which do not.', self::$textdomain );
	}
	
	public static function settings_field($args) {
		if( isset($args['key']) && ($key = $args['key']) ) {
			$val = self::get_option($key);

			if( $key == 'users' ) {
				$user_roles = self::get_user_roles();
				foreach($user_roles as $urkey => $ur) { 
					echo "<input type='checkbox' name='" . self::$settingsname . "[" . $key . "][]' value='" . $urkey . "' id='" . self::$settingsname . "_" . $urkey . "'";
					if( is_array($val) && in_array($urkey, $val) ) echo ' checked';
					if( isset($ur['capabilities']['update_core']) ) echo ' checked disabled';
					echo "> <label for='" . self::$settingsname . "_" . $urkey . "'>" . $ur['name'] . "</label><br />";
				}
			
			} else {
				echo "<input type='text' size='50' name='" . self::$settingsname . "[" . $key . "]' value='" . $val . "' />";
			}
		}
	}
	
	public static function validate_field($input) {
		foreach($input as $key => $in) {
			if( is_array($in) ) {
				$in = array_map( array( __CLASS__, 'clean_input' ), $in );
				
			} else {
				$in = self::clean_input($in);
			}
			
			$input[$key] = $in;
		}
		
		return $input;
	}
	
	public static function clean_input($input) {
		return trim(strip_tags($input));
	}
	
	public static function get_option($key = '') {
		$val = '';
		$options = get_option( self::$optionkey );
		
		if( $key ) {
			if( $options && $key && isset($options[$key]) )
				$val = $options[$key];
				
		} else {
			$val = $options;
		}
		
		return $val;
	}	
		
	public static function settings_page() {
	?>
		<div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h2><?php _e( 'Custom WP Update Message', self::$textdomain ); ?></h2>
            
            <form action="options.php" method="post">
				<?php settings_fields(self::$settingsname); ?>
                <?php do_settings_sections(self::$pageslug); ?>
                 
                <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
            </form>              
        </div>
    <?php
	}
	
	public static function on_uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;

		delete_option( self::$optionkey );
	}
	
	public static function get_user_roles() {
		global $wpdb;

		$options_name = $wpdb->prefix.'user_roles';
		$get_roles = "select option_id, option_value
						  from " . $wpdb->prefix . "options
						  where option_name='$options_name'
						  limit 0, 1";
		$roles = $wpdb->get_results($get_roles);
		if ($wpdb->last_error) {
			return;
		}
		$user_roles = maybe_unserialize($roles[0]->option_value);

		return $user_roles;
	}
	
	// based on the update_nag() function in /wp-admin/includes/update.php
	// we're replacing the default notice from update_nag with our expanded one
	public static function update_nag() {
		if ( is_multisite() && !current_user_can('update_core') )
			return false;

		global $pagenow;

		if ( 'update-core.php' == $pagenow )
			return;

		$cur = get_preferred_from_update_core();

		if ( ! isset( $cur->response ) || $cur->response != 'upgrade' )
			return false;
		
		if ( self::current_user_can_view_message() ) {
			$options = self::get_option();
			$name = $options['company_name'] ? $options['company_name'] : __( 'your administrator', self::$textdomain );
			$email = $options['company_email'];
			$website = $options['company_website'];
			$phone = $options['company_phone'];
			$message = $options['update_message'] ? $options['update_message'] : __( "Click here to upgrade at your own risk", self::$textdomain );

			$msg = sprintf( __('<a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> is available! We recommend contacting %2$s to see if this update is recommended.', self::$textdomain ), $cur->current, $name) . "<br />";
			if( $email ) 
				$msg .= sprintf( __('<strong>Email:</strong> <a href="mailto:%1$s">%1$s</a> ', self::$textdomain), $email );
			if( $phone ) 
				$msg .= sprintf( __('<strong>Phone:</strong> %1$s ', self::$textdomain), $phone );
			if( $website ) 
				$msg .= sprintf( __('<strong>Website:</strong> <a href="%1$s">%1$s</a> ', self::$textdomain), $website );
			if ( current_user_can('update_core') )
				$msg .= '<br />' . sprintf( __('<a href="%1$s">%2$s</a>'), network_admin_url( 'update-core.php' ), $message );
		} else {
			$msg = sprintf( __('<a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> is available! Please notify the site administrator.', self::$textdomain), $cur->current );
		}
		echo "<div class='update-nag'>$msg</div>";
	}

	public static function current_user_can_view_message() {
		if( current_user_can('update_core') ) return true;
		
		$ccmwcu_options = get_option('ccmwcu_options');
		$users = $ccmwcu_options['users'] ? $ccmwcu_options['users'] : '';
		$users = maybe_unserialize($users);
		foreach($users as $user_role) {
			if( current_user_can($user_role) ) return true;
		}	
		return false;
	}
}
