<?php
/*
Plugin Name: RSS Includes Pages Pro
Version: 3.4
Plugin URI: http://infolific.com/technology/software-worth-using/include-pages-in-wordpress-rss-feeds/
Description: Include pages and custom post types in RSS feeds. Particularly useful to those that use WordPress as a CMS.
Author: Marios Alexandrou
Author URI: http://infolific.com/technology/
License: GPLv2 or later
Text Domain: rss-includes-pages
*/

/*
Copyright 2015 Marios Alexandrou

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EDD_RSSIP_STORE_URL', 'http://infolific.com' );
define( 'EDD_RSSIP_PLUGIN_NAME', 'RSS Includes Pages Pro for WordPress' );

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include_once( dirname( __FILE__ ) . '/inc/EDD_SL_Plugin_Updater.php' );
}

function rssip_edd_sl_plugin_updater() {
	$license_key = trim( get_option( 'rssip_edd_license_key' ) );

	$edd_updater = new EDD_SL_Plugin_Updater( EDD_RSSIP_STORE_URL, __FILE__, array(
			'version' 	=> '3.4',					// current version number
			'license' 	=> $license_key,			// license key (used get_option above to retrieve from DB)
			'item_name' => EDD_RSSIP_PLUGIN_NAME, 	// name of this plugin
			'author' 	=> 'Marios Alexandrou'		// author of this plugin
		)
	);

}
add_action( 'admin_init', 'rssip_edd_sl_plugin_updater', 0 );

function rssip_edd_register_option() {
	// creates our settings in the options table
	register_setting('rssip_edd_license', 'rssip_edd_license_key', 'rssip_edd_sanitize_license' );
}
add_action('admin_init', 'rssip_edd_register_option');

function rssip_edd_sanitize_license( $new ) {
	$old = get_option( 'rssip_edd_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'rssip_edd_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

function rssip_edd_activate_license() {
	if( isset( $_POST['rssip_edd_license_activate'] ) ) {
	 	if( ! check_admin_referer( 'rssip_edd_nonce', 'rssip_edd_nonce' ) ) {
			return; // get out if we didn't click the Activate button
		}
		
		$license = trim( get_option( 'rssip_edd_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( EDD_RSSIP_PLUGIN_NAME ),
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( EDD_RSSIP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"
		update_option( 'rssip_edd_license_status', $license_data->license );
	}
}
add_action('admin_init', 'rssip_edd_activate_license');

function rssip_edd_deactivate_license() {
	if( isset( $_POST['rssip_edd_license_deactivate'] ) ) {

	 	if( ! check_admin_referer( 'rssip_edd_nonce', 'rssip_edd_nonce' ) ) {
			return; // get out if we didn't click the Activate button
		}
			
		$license = trim( get_option( 'rssip_edd_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( EDD_RSSIP_PLUGIN_NAME ),
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( EDD_RSSIP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'rssip_edd_license_status' );
		}
	}
}
add_action('admin_init', 'rssip_edd_deactivate_license');

function rssip_edd_check_license() {
	global $wp_version;

	$license = trim( get_option( 'rssip_edd_license_key' ) );

	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_name' => urlencode( EDD_RSSIP_PLUGIN_NAME ),
		'url'       => home_url()
	);

	// Call the custom API.
	$response = wp_remote_post( EDD_RSSIP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

	if ( is_wp_error( $response ) )
		return false;

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if( $license_data->license == 'valid' ) {
		return true;
	} else {
		return false;
	}
}

function rssip_plugin_meta( $links, $file ) { // add some links to plugin meta row
	if ( strpos( $file, 'rss-includes-pages-pro.php' ) !== false ) {
		$links = array_merge( $links, array( '<a href="' . esc_url( get_admin_url(null, 'options-general.php?page=rss-includes-pages') ) . '">Settings</a>' ) );
	}
	return $links;
}
add_filter('plugin_row_meta', 'rssip_plugin_meta', 10, 2);

/*
* Add a submenu under Tools
*/
function rssip_add_pages() {
	$page = add_submenu_page( 'options-general.php', 'RSS Includes Pages Pro', 'RSS Includes Pages Pro', 'activate_plugins', 'rss-includes-pages', 'rssip_options_page' );
	add_action( "admin_print_scripts-$page", "rssip_admin_scripts" );
}
add_action( 'admin_menu', 'rssip_add_pages' );

/*
* Scripts needed for the admin side
*/
function rssip_admin_scripts() {
	wp_enqueue_style( 'rssip_styles', plugins_url() . '/rss-includes-pages-pro/css/rssip.css' );
}

function rssip_options_page() {
	if ( isset( $_POST['setup-update'] ) ) {	
		unset( $_POST['setup-update'] );
		update_option( 'rssip_plugin_settings', $_POST );
		echo '<div id="message" class="updated fade">';
		echo '<p><strong>Options Updated</strong></p>';
		echo '</div>';
	} else if ( isset( $_POST['rssip_edd_license_save'] ) ) {
		update_option( 'rssip_edd_license_key', trim( $_POST['rssip_edd_license_key'] ) );
	}
?>
<div class="wrap" style="padding-bottom: 5em;">
	<h2>RSS Includes Pages Pro</h2>
	<div id="rssip-items">

		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<ul id="rssip_itemlist">
				<li>
				<?php
				$rssip_settings = get_option( 'rssip_plugin_settings' );

				if( isset( $rssip_settings['rssip_posts'] ) ) {
					$rssip_posts = 'CHECKED';
				} else {
					$rssip_posts = '';
				}

				if( isset( $rssip_settings['rssip_pages'] ) ) {
					$rssip_pages = 'CHECKED';
				} else {
					$rssip_pages = '';
				}

				if( isset( $rssip_settings['rssip_exclude'] ) ) {
					$rssip_exclude = $rssip_settings['rssip_exclude'];
				} else {
					$rssip_exclude = '';
				}

				if( isset( $rssip_settings['rssip_include'] ) ) {
					$rssip_include = $rssip_settings['rssip_include'];
				} else {
					$rssip_include = '';
				}

				echo "<div>";
					echo "Include These Post Types:";
					echo "<br />";

					echo "<label class='side-label' for='rssip_posts'>&bull; Posts:</label>";
					echo "<input class='checkbox' type='checkbox' name='rssip_posts' id='rssip_posts' $rssip_posts />";
					echo "<br />";

					echo "<label class='side-label' for='rssip_pages'>&bull; Pages:</label>";
					echo "<input class='checkbox' type='checkbox' name='rssip_pages' id='rssip_pages' $rssip_pages />";
					echo "<br />";

					$rssip_args = array(
						'public'   => true
					);
					$rssip_output = 'names'; // names or objects, note names is the default
					$rssip_operator = 'and'; // 'and' or 'or'
					$rssip_post_types = get_post_types( $rssip_args, $rssip_output, $rssip_operator ); 
					foreach ( $rssip_post_types as $rssip_post_type ) {
						if ( strcasecmp( $rssip_post_type, 'post' ) != 0 && strcasecmp( $rssip_post_type, 'page' ) != 0 ) {
							echo "<label class='side-label' for='rssip_pages'>&bull; " . ucfirst( $rssip_post_type ) . ":</label>";
							echo "<input class='checkbox' type='checkbox' name='rssip_" . $rssip_post_type . "' id='rssip_" . $rssip_post_type . "'";
							if( isset( $rssip_settings['rssip_' . $rssip_post_type] ) ) {
								echo ' checked';
							}
							echo " />";
							echo "<br />";
						}
					}

					echo "<br />";

					echo "<label class='side-label' for='rssip_exclude'>Exclude IDs:</label>";
					echo "<input class='textbox-long' type='text' name='rssip_exclude' id='rssip_exclude' value='$rssip_exclude' />";
					echo "<br />";                     

					echo "<label class='side-label' for='rssip_include'>Include IDs:</label>";
					echo "<input class='textbox-long' type='text' name='rssip_include' id='rssip_include' value='$rssip_include' />";
					echo "<br />";
				echo "</div>";

				unset( $rssip_posts );
				unset( $rssip_pages );
				unset( $rssip_exclude );
				unset( $rssip_include );
				?>
				</li>
			</ul>
			<div id="divTxt"></div>
		    <div class="clearpad"></div>
			<?php if ( rssip_edd_check_license() ) { ?>
			<input type="submit" class="button left" value="Update Settings" />
			<input type="hidden" name="setup-update" />
			<?php } else { ?>
			<input type="submit" class="button left" value="Update Settings" onClick="alert( 'Please buy/activate a license for this plugin. A license is required per domain other than localhost.' ); return false;" />
			<?php } ?>
		</form>
	</div>

	<div id="rssip-sb">
		<div class="postbox" id="rssip-sbzero">
			<h3 class="hndle"><span>License</span></h3>
			<div class="inside">
				<?php
				$license 	= get_option( 'rssip_edd_license_key' );
				$status 	= get_option( 'rssip_edd_license_status' );
				?>
				<div class="wrap">
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<?php settings_fields('rssip_edd_license'); ?>
						<input class="textbox-sidebar" id="rssip_edd_license_key" name="rssip_edd_license_key" type="text" value="<?php esc_attr_e( $license ); ?>" />
						<div id="license-buttons" style="display: inline-block; padding-top: 5px;">
							<div style="float: left;">
								<input type="submit" class="button" name="rssip_edd_license_save" value="Save License"/>
							</div>
							<div style="float: left;">
								<?php if( false !== $license ) { ?>
									<?php if( $status !== false && $status == 'valid' ) {
										wp_nonce_field( 'rssip_edd_nonce', 'rssip_edd_nonce' ); ?>
										<input type="submit" class="button" name="rssip_edd_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
									<?php } else {
										wp_nonce_field( 'rssip_edd_nonce', 'rssip_edd_nonce' ); ?>
										<input type="submit" class="button" name="rssip_edd_license_activate" value="<?php _e('Activate License'); ?>"/>
									<?php } ?>
								<?php } ?>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="postbox" id="rssip-sbone">
			<h3 class='hndle'><span>Documentation</span></h3>
			<div class="inside">
				<strong>Instructions</strong>
				<p>This plugin allows you to include pages and other custom post types to your RSS feed.</p>
				<ol>
					<li>Select the options to the left to indicate what should be included in your RSS feeds.</li>
					<li>To exclude items, list the IDs separated by commas. Or to include items, list the IDs separated by commas. Note that excluding and including are mutually exclusive. If you specify both, exclude will win.</li>
					<li>Note that entries in your RSS feed are still sorted by date with the most recently published items at the top.</li>
				</ol>
				<strong>Tips</strong>
				<ol>
					<li>If just posts are selected, you've specified WordPress' default behavior.</li>
					<li>If you cache your feeds, be sure to flush the cache when testing.</li>
					<li>Note that third-party services that distribute your feed may not update your feed immediately so you may not see the effect of your options to the left for some time.</li>
				</ol>
			</div>
		</div>
		<div class="postbox"  id="rssip-sbtwo">
			<h3 class='hndle'><span>Support</span></h3>
			<div class="inside">
				<p>Your best bet is to post on the <a href="http://infolific.com/technology/software-worth-using/include-pages-in-wordpress-rss-feeds/">plugin support page</a> for the pro version.</p>
				<p>Please consider supporting me by <a href="https://wordpress.org/support/view/plugin-reviews/rss-includes-pages">rating this plugin</a>. Thanks!</p>
			</div>
		</div>
		<div class="postbox" id="rssip-sbthree">
			<h3 class='hndle'><span>Other Plugins</span></h3>
			<div class="inside">
				<ul>
					<li><a href="https://wordpress.org/plugins/real-time-find-and-replace/">Real-Time Find and Replace</a>: Set up find and replace rules that are executed AFTER a page is generated by WordPress, but BEFORE it is sent to a user's browser.</li>
					<li><a href="https://wordpress.org/plugins/republish-old-posts/">Republish Old Posts</a>: Republish old posts automatically by resetting the date to the current date. Puts your evergreen posts back in front of your users.</li>
					<li><a href="https://wordpress.org/extend/plugins/rss-includes-pages/">RSS Includes Pages</a>: Modifies RSS feeds so that they include pages and not just posts. My most popular plugin!</li>
					<li><a href="https://wordpress.org/extend/plugins/enhanced-plugin-admin">Enhanced Plugin Admin</a>: At-a-glance info (rating, review count, last update date) on your site's plugin page about the plugins you have installed (both active and inactive).</li>
					<li><a href="https://wordpress.org/extend/plugins/add-any-extension-to-pages/">Add Any Extention to Pages</a>: Add any extension of your choosing (e.g. .html, .htm, .jsp, .aspx, .cfm) to WordPress pages.</li>
					<li><a href="https://wordpress.org/extend/plugins/social-media-email-alerts/">Social Media E-Mail Alerts</a>: Receive e-mail alerts when your site gets traffic from social media sites of your choosing. You can also set up alerts for when certain parameters appear in URLs.</li>				</ul>
			</div>
		</div>
	</div>
</div>
<?php } ?>
<?php
function rssip_posts_where( $var ) {
	if ( !is_feed() ) { // check if this is a feed
		return $var; // if not, return an unmodified variable

	} else {
		global $table_prefix; // get the table prefix
		$find = $table_prefix . "posts.post_type = 'post'"; // find where the query filters by post_type

		$rssip_settings = get_option( 'rssip_plugin_settings' );

		if( isset( $rssip_settings['rssip_exclude'] ) ) {
			$rssip_exclude = $rssip_settings['rssip_exclude'];
		} else {
			$rssip_exclude = -1;
		}

		if( strlen( trim( $rssip_exclude ) ) == 0 ) {
			$rssip_exclude = -1;
		}
		
		if( isset( $rssip_settings['rssip_include'] ) ) {
			$rssip_include = $rssip_settings['rssip_include'];
		} else {
			$rssip_include = -1;
		}

		if( strlen( trim( $rssip_include ) ) == 0 ) {
			$rssip_include = -1;
		}

		$rssip_additional_types_to_include = '';
		if( isset( $rssip_settings['rssip_pages'] )	) {
			$rssip_additional_types_to_include = "'page'";
		}

		$rssip_args = array(
			'public'   => true
		);
		$rssip_output = 'names'; // names or objects, note names is the default
		$rssip_operator = 'and'; // 'and' or 'or'
		$rssip_post_types = get_post_types( $rssip_args, $rssip_output, $rssip_operator ); 
		foreach ( $rssip_post_types as $rssip_post_type ) {
			if ( strcasecmp( $rssip_post_type, 'post' ) != 0 && strcasecmp( $rssip_post_type, 'page' ) != 0 ) {
				if( isset( $rssip_settings['rssip_' . $rssip_post_type] ) ) {
					$rssip_additional_types_to_include = $rssip_additional_types_to_include . ",'" . $rssip_post_type . "'";
				}
			}
		}

		$rssip_additional_types_to_include = trim( $rssip_additional_types_to_include, "," );
		
		//Includes posts and pages in feed
		if( isset( $rssip_settings['rssip_posts'] ) && strlen( $rssip_additional_types_to_include ) > 0  ) {

			//If no IDs included, fall back to excluding.
			if( $rssip_include == -1 ) {
				$replace = "(" . $find . " OR " . $table_prefix . "posts.post_type IN (" . $rssip_additional_types_to_include . ")) AND " . $table_prefix . "posts.ID NOT IN ($rssip_exclude)"; // add OR post_type 'page' to the query
			} else {
				$replace = "(" . $find . " OR " . $table_prefix . "posts.post_type IN (" . $rssip_additional_types_to_include . ")) AND " . $table_prefix . "posts.ID IN ($rssip_include)"; // add OR post_type 'page' to the query
			}
			
			$var = str_replace( $find, $replace, $var ); // change the query

		//Include just pages in feed
		} else if( strlen( $rssip_additional_types_to_include ) > 0  ) {

			//If no IDs included, fall back to excluding.
			if( $rssip_include == -1 ) {
				$replace = $table_prefix . "posts.post_type IN (" . $rssip_additional_types_to_include . ") AND " . $table_prefix . "posts.ID NOT IN ($rssip_exclude)";
			} else {
				$replace = $table_prefix . "posts.post_type IN (" . $rssip_additional_types_to_include . ") AND " . $table_prefix . "posts.ID IN ($rssip_include)";
			}

			$var = str_replace( $find, $replace, $var ); // change the query

		//Include just posts in feed i.e. default WordPress behavior. 
		} else {
			//do nothing
		}
	}

	return $var; // return the variable
}
add_filter( 'posts_where', 'rssip_posts_where' );

/*
* Deal with Last Post Modified so feeds will validate. WordPress default just checks for posts, not pages.
*/
function rssip_get_lastpostmodified( $lastpostmodified, $timezone ) {
	global $rssip_feed, $wpdb;

	if ( !( $rssip_feed ) ) {
		return $lastpostmodified;
	}

	$rssip_settings = get_option( 'rssip_plugin_settings' );

	if( isset( $rssip_settings['rssip_exclude'] ) ) {
		$rssip_exclude = $rssip_settings['rssip_exclude'];
	} else {
		$rssip_exclude = -1;
	}

	if( strlen( trim( $rssip_exclude ) ) == 0 ) {
		$rssip_exclude = -1;
	}

	if( isset( $rssip_settings['rssip_include'] ) ) {
		$rssip_include = $rssip_settings['rssip_include'];
	} else {
		$rssip_include = -1;
	}

	if( strlen( trim( $rssip_include ) ) == 0 ) {
		$rssip_include = -1;
	}

	$rssip_additional_types_to_include = '';
	if( isset( $rssip_settings['rssip_pages'] )	) {
		$rssip_additional_types_to_include = "'page'";
	}

	$rssip_args = array(
		'public'   => true
	);
	$rssip_output = 'names'; // names or objects, note names is the default
	$rssip_operator = 'and'; // 'and' or 'or'
	$rssip_post_types = get_post_types( $rssip_args, $rssip_output, $rssip_operator ); 
	foreach ( $rssip_post_types as $rssip_post_type ) {
		if ( strcasecmp( $rssip_post_type, 'post' ) != 0 && strcasecmp( $rssip_post_type, 'page' ) != 0 ) {
			if( isset( $rssip_settings['rssip_' . $rssip_post_type] ) ) {
				$rssip_additional_types_to_include = $rssip_additional_types_to_include . ",'" . $rssip_post_type . "'";
			}
		}
	}

	$rssip_additional_types_to_include = trim( $rssip_additional_types_to_include, "," );
	
	//Includes posts and pages in feed
	if( isset( $rssip_settings['rssip_posts'] ) && strlen( $rssip_additional_types_to_include ) > 0 ) {

		if( $rssip_include == -1 ) {
			//queries taken from wp-includes/post.php  modified to include pages
			$lastpostmodified = $wpdb->get_var( "SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type IN (" . $rssip_additional_types_to_include . ")) AND ID NOT IN ($rssip_exclude) ORDER BY post_modified_gmt DESC LIMIT 1" );
			$lastpostdate = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type IN (" . $rssip_additional_types_to_include . ")) AND ID NOT IN ($rssip_exclude) ORDER BY post_date_gmt DESC LIMIT 1" );
		} else {
			//queries taken from wp-includes/post.php
			$lastpostmodified = $wpdb->get_var( "SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type IN (" . $rssip_additional_types_to_include . ")) AND ID IN ($rssip_include) ORDER BY post_modified_gmt DESC LIMIT 1" );
			$lastpostdate = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type IN (" . $rssip_additional_types_to_include . ")) AND ID IN ($rssip_include) ORDER BY post_date_gmt DESC LIMIT 1" );	
		}
			
	//Include just pages in feed
	} else if( strlen( $rssip_additional_types_to_include ) > 0 ) {

		if( $rssip_include == -1 ) {
			//queries taken from wp-includes/post.php modified to just use pages
			$lastpostmodified = $wpdb->get_var( "SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type IN (" . $rssip_additional_types_to_include . ")) AND ID NOT IN ($rssip_exclude) ORDER BY post_modified_gmt DESC LIMIT 1" );
			$lastpostdate = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type IN (" . $rssip_additional_types_to_include . ")) AND ID NOT IN ($rssip_exclude) ORDER BY post_date_gmt DESC LIMIT 1" );
		} else {
			//queries taken from wp-includes/post.php
			$lastpostmodified = $wpdb->get_var( "SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type IN (" . $rssip_additional_types_to_include . ")) AND ID IN ($rssip_include) ORDER BY post_modified_gmt DESC LIMIT 1" );
			$lastpostdate = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type IN (" . $rssip_additional_types_to_include . ")) AND ID IN ($rssip_include) ORDER BY post_date_gmt DESC LIMIT 1" );
		}

	//Include just posts in feed i.e. default WordPress behavior. 
	} else {
		if( $rssip_include == -1 ) {
			//queries taken from wp-includes/post.php
			$lastpostmodified = $wpdb->get_var( "SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post') AND ID NOT IN ($rssip_exclude) ORDER BY post_modified_gmt DESC LIMIT 1" );
			$lastpostdate = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post') AND ID NOT IN ($rssip_exclude) ORDER BY post_date_gmt DESC LIMIT 1" );
		} else {
			//queries taken from wp-includes/post.php
			$lastpostmodified = $wpdb->get_var( "SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post') AND ID IN ($rssip_include) ORDER BY post_modified_gmt DESC LIMIT 1" );
			$lastpostdate = $wpdb->get_var( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type = 'post') AND ID IN ($rssip_include) ORDER BY post_date_gmt DESC LIMIT 1" );
		}
	}
	
	if ( $lastpostdate > $lastpostmodified ) {
		$lastpostmodified = $lastpostdate;
	}

	return $lastpostmodified;
}
add_filter( 'get_lastpostmodified', 'rssip_get_lastpostmodified', 10, 2 );

// We do this because is_feed is not set when calling get_lastpostmodified.
function rssip_feed_true() {
	global $rssip_feed;
	$rssip_feed = true;
}
add_action( 'rss2_ns', 'rssip_feed_true' );
add_action( 'atom_ns', 'rssip_feed_true' );
add_action( 'rdf_ns', 'rssip_feed_true' );

// We won't mess with comment feeds.
function rssip_feed_false() {
	global $rssip_feed;
	$rssip_feed = false;
}
add_action ( 'rss2_comments_ns', 'rssip_feed_false' );
add_action ( 'atom_comments_ns', 'rssip_feed_false' );
?>