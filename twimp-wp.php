<?php
/*
Plugin Name: twimp-wp
Plugin URI: http://raza.narfum.org/twimp-wp/
Description: Publish blog posts to multiple twitter accounts.
Version: 0.1
Author: Jeroen Bolle
Author URI: http://raza.narfum.org/

Copyright 2009  Jeroen Bolle  (email : jeroen.bolle@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
*/

/* Configuration */

define( "PLUGIN_VERSION", "0.1" );
define( "TWIT_USERNAME_FIELD", "twit_username" );
define( "TWIT_PASSWORD_FIELD", "twit_password" );
define( "TWIT_UPDATE_URL", "http://twitter.com/statuses/update.json" );
define( "BITLY_USERNAME", "wptwitter" );
define( "BITLY_APIKEY", "R_12a9de317ac3a41fa5ade0fdc78db14c" );
define( "BITLY_FETCH_URL", "http://api.bit.ly/shorten?version=2.0.1&longUrl=" );

$userlevels = array( 
				"adminstrator" => 10,
				"editor" => 7,
				"author" => 2,
				"contributor" => 1,
				"subscriber" => 0 );


/* Add twitter fields to the users table */

function tmp_install() {
	
    global $wpdb;

	$sql = "ALTER TABLE %s 
				ADD COLUMN %s VARCHAR(100) NULL, 
				ADD COLUMN %s VARCHAR(100) NULL";

    $wpdb->query( sprintf ( $sql, $wpdb->users, TWIT_USERNAME_FIELD, TWIT_PASSWORD_FIELD ) );
	
	add_option( "message_format", "#posttitle# - #url#", "", "yes" );
	add_option( "twitter_role", "author", "", "yes" );
	add_option( "publishall_role", "editor", "", "yes" );
	
	add_option( "bitly_enabled", "1", "", "yes" );
	add_option( "bitly_username", BITLY_USERNAME, "", "yes" );
	add_option( "bitly_apikey", BITLY_APIKEY, "", "yes" );

}

/* Remove twitter fields from the users table */

function tmp_remove() {

	global $wpdb;
	
	$sql = "ALTER TABLE %s
				DROP COLUMN %s,
				DROP COLUMN %s";
	
	$wpdb->query( sprintf ( $sql, $wpdb->users, TWIT_USERNAME_FIELD, TWIT_PASSWORD_FIELD ) );
	
	delete_option( "message_format" );
	delete_option( "twitter_role" );
	delete_option( "publishall_role" );
	
	delete_option( "bitly_enabled" );
	delete_option( "bitly_username" );
	delete_option( "bitly_apikey" );

}

/* Register hooks for activation and deactivation */

register_activation_hook( __FILE__, "tmp_install" );
register_deactivation_hook( __FILE__, "tmp_remove" );

/* Display in the admin menu */

function tmp_menu() {
	
	add_options_page('twimp settings', 'Twimp', 8, __FILE__, 'tmp_options');

}

/* Generate options page */

function tmp_options() {
	
	global $wp_roles;
	
	if ( $_SERVER["REQUEST_METHOD"] == "POST" ) {
		
		if ( isset( $_POST["bitly_enabled"] ) ) {
	
			update_option( "bitly_enabled", "1" );
			
		} else {
		
			update_option( "bitly_enabled", "0" );
			
		}
		
		update_option( "bitly_username", $_POST["bitly_username"] );
		update_option( "bitly_apikey", $_POST["bitly_apikey"] );
		
		update_option( "message_format", $_POST["message_format"] );
		update_option( "twitter_role", $_POST["twitter_role"] );
		update_option( "publishall_role", $_POST["publishall_role"] );
	
	}
	
	$bitlyenabled = "";
	
	if ( get_option( "bitly_enabled" ) == "1" ) {
	
		$bitlyenabled = 'checked="checked"';
	
	}
?>
	<div class="wrap">
		<form method="post">
		<h2>Twimp settings</h2>
		<h3>Formatting</h3>
		<p><em>Specify the way your blog posts are tweeted. Use variables #url# and #posttitle# to display the URL and post title respectively.</em></p>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="message_format">twitter message format</label></th>
				<td>
					<input id="message_format" name="message_format" type="text" value="<?php echo get_option( "message_format" ); ?>" />
				</td>
			</tr>
		</table>
		<h3>bit.ly link shortening</h3>
		<p><em>bit.ly is a free service which makes URLs shorter. To monitor the traffic your short URLs generate, you need to make your own account at <a href="http://bit.ly">bit.ly</a> and receive a API key. You can also use the default account.</em></p>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="bitly_enabled">Enable</label></th>
				<td>
					<input id="bitly_enabled" name="bitly_enabled" type="checkbox" <?php echo $bitlyenabled; ?> />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="bitly_username">bit.ly username</label></th>
				<td>
					<input id="bitly_username" name="bitly_username" type="text" value="<?php echo get_option( "bitly_username" ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="bitly_apikey">bit.ly API key</label></th>
				<td>
					<input id="bitly_apikey" name="bitly_apikey" type="text" value="<?php echo get_option( "bitly_apikey" ); ?>" />
				</td>
			</tr>
		</table>
		<h3>Permissions</h3>
		<p><em>Specify the minimum role of a user to perform the following actions.</em></p>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="twitter_role">Add twitter details</label></th>
				<td>
					<select name="twitter_role" id="twitter_role">
					<?php
					$role_list = '';
				
					foreach( $wp_roles->role_names as $role => $name ) {
						
						$name = translate_with_context($name);
						
						if ( $role == get_option( "twitter_role" ) ) {
							
							$selected = 'selected="selected"';
							
						} else {
							
							$selected = '';
							
						}
						
						$role_list .= '<option value="' . $role . '" ' . $selected . '>' . $name . '</option>';
						
					}
					echo $role_list;
					?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="publishall_role">Publish to all twitter accounts</label></th>
				<td>
					<select name="publishall_role" id="publishall_role">
					<?php
					$role_list = '';
				
					foreach( $wp_roles->role_names as $role => $name ) {
						
						$name = translate_with_context($name);
						
						if ( $role == get_option( "publishall_role" ) ) {
							
							$selected = 'selected="selected"';
							
						} else {
							
							$selected = '';
							
						}
						
						$role_list .= '<option value="' . $role . '" ' . $selected . '>' . $name . '</option>';
						
					}
					echo $role_list;
					?>
					</select>
				</td>
			</tr>
		</table>
		<br />
		<span class="submit">
		  <input name="submit" value="Save Changes" type="submit" />
		</span>
		</form>
	</div>
	<?php
}

/* Generate form fields to enter twitter authentication details */

function tmp_addTwitterFieldsToProfile() {
	
    global $wpdb, $user_ID;
	
	$sql = "SELECT %s, %s FROM %s WHERE ID = %d LIMIT 1";
	$result = $wpdb->get_row( sprintf( $sql, TWIT_USERNAME_FIELD, TWIT_PASSWORD_FIELD, $wpdb->users, $user_ID ), ARRAY_A );
	
	?>

	<h3>Twitter details</h3>

	<table class="form-table">
		<tr>
			<th><label for="<?php echo TWIT_USERNAME_FIELD; ?>">Twitter username</label></th>
			<td><input name="<?php echo TWIT_USERNAME_FIELD; ?>" id="<?php echo TWIT_USERNAME_FIELD; ?>" type="text" value="<?php echo $result[TWIT_USERNAME_FIELD]; ?>" /></td>
		</tr>
		<tr>
			<th><label for="<?php echo TWIT_PASSWORD_FIELD; ?>">Twitter password</label></th>
			<td><input name="<?php echo TWIT_PASSWORD_FIELD; ?>" id="<?php echo TWIT_PASSWORD_FIELD; ?>" type="password" value="<?php echo $result[TWIT_PASSWORD_FIELD]; ?>" /></td>
		</tr>
	</table>

	<?php
}

/* Save twitter authentication details to database */
 
function tmp_updateTwitterFields() {
	
	global $wpdb, $current_user, $twitterUserFields;
	
	$sql = "UPDATE " . $wpdb->users . "
				SET " . TWIT_USERNAME_FIELD . " = %s, " . TWIT_PASSWORD_FIELD . " = %s
				WHERE ID = %d
				LIMIT 1";
	
	$wpdb->query( $wpdb->prepare( $sql, $_POST[TWIT_USERNAME_FIELD], $_POST[TWIT_PASSWORD_FIELD], $current_user->id ) );
	
}

/* Show options for submitting to twitter when adding/editing a post  */

function tmp_showTwitterSubmitOptions() {

	global $current_user, $userlevels;

?>

	<div id="twitterdiv" class="postbox ">
		<div class="handlediv" title="Click to toggle"></div>
		<h3>Twitter multi publish</h3>
		<div class="inside">
			<input type="radio" name="twitteraction" id="twitno" value="no" checked="checked" /> <label for="twitno" class="selectit">Do not publish to twitter</label><br />
			<input type="radio" name="twitteraction" id="twitauthor" value="author" /> <label for="twitauthor" class="selectit">Publish to author's twitter</label><br />
			<?php
			if ( intval ( $current_user->wp_user_level ) >= $userlevels[get_option( "publishall_role" )] ) {
			?>
			<input type="radio" name="twitteraction" id="twitall" value="all" /> <label for="twitall" class="selectit">Publish to all twitter accounts</label>
			<?php
			}
			?>
		</div>
	</div>

<?php
}

/* Process the chosen option when submitting a blog post */

function tmp_processTwitterSubmitOptions() {
	
	global $wpdb, $user_ID;
	
	$twitteraction = $_POST["twitteraction"];
	
	switch ( $twitteraction ) {
		
		case "no":
			
			/* Do not post to twitter */
			return true;
			
		break;
	
		case "author":
			
			/* Post to the twitter account of the author */
			$url = get_permalink( $_POST["ID"] );
		
			if ( get_option( "bitly_enabled" ) == "1" ) {
				
				$bitlyUrl = tmp_makeBitlyUrl( $url );
				
				if ( $bitlyUrl )  {
					$url = $bitlyUrl;
				}
			
			}
			
			$title = $_POST["post_title"];
			
			$message = get_option( "message_format" );
			$message = str_replace( "#url#", $url, $message );
			$message = str_replace( "#posttitle#", $title, $message );

			$sql = "SELECT %s, %s FROM %s WHERE ID = %d LIMIT 1";
			$authinfo = $wpdb->get_row( sprintf( $sql, TWIT_USERNAME_FIELD, TWIT_PASSWORD_FIELD, $wpdb->users, $user_ID), ARRAY_A );
			
			if ( trim( $authinfo[TWIT_USERNAME_FIELD] ) != "" && trim( $authinfo[TWIT_PASSWORD_FIELD] ) != "" ) {
				
				tmp_tweetPost( $authinfo[TWIT_USERNAME_FIELD], $authinfo[TWIT_PASSWORD_FIELD], $message );
			
			}
			
		break;
		
		case "all";
		
			/* Post to twitter accounts of all users on the site */
			if ( intval ( $current_user->wp_user_level ) >= $userlevels[get_option( "publishall_role" )] ) {
				
				$url = get_permalink( $_POST["ID"] );

				if ( get_option( "bitly_enabled" ) == "1" ) {
					
					$bitlyUrl = tmp_makeBitlyUrl( $url );
					
					if ( $bitlyUrl )  {
						$url = $bitlyUrl;
					}
				
				}

				$title = $_POST["post_title"];
				
				$message = get_option( "message_format" );
				$message = str_replace( "#url#", $url, $message );
				$message = str_replace( "#posttitle#", $title, $message );
			
				$sql = "SELECT %s, %s FROM %s WHERE %s IS NOT NULL OR %s IS NOT NULL";
				$accounts = $wpdb->get_results( sprintf( $sql, TWIT_USERNAME_FIELD, TWIT_PASSWORD_FIELD, $wpdb->users, TWIT_USERNAME_FIELD, TWIT_PASSWORD_FIELD ), ARRAY_A );
				
				foreach ( $accounts as $account ) {
					
					tmp_tweetPost( $account[TWIT_USERNAME_FIELD], $account[TWIT_PASSWORD_FIELD], $message );
					
				}
				
			}
			
		break;
		
		default:;
		
			return true;
			
		break;

	}
	
}

/* Do a twitter status update */

function tmp_tweetPost( $twitUsername, $twitPassword, $twitMessage ) {
	
	require_once( ABSPATH . WPINC . "/class-snoopy.php" );
	
	$snoopy = new Snoopy;
	$snoopy->agent = "twimp-wp - http://raza.narfum.org/twimp-wp/";
	$snoopy->rawheader = array ( 
			"X-Twitter-Client" => "twimp-wp",
			"X-Twitter-Client-Version" => PLUGIN_VERSION,
			"X-Twitter-Client-URL" => "http://raza.narfum.org/twimp-wp/"
		);
	
	$snoopy->user = $twitUsername;
	$snoopy->pass = $twitPassword;
	
	$snoopy->submit(
			TWIT_UPDATE_URL,
			array ( 
				"status" => $twitMessage,
				"source" => "twimp-wp"
			)
		);
	
	if ( strpos( $snoopy->response_code, "200" ) ) {
		return true;
	}
	
	return false;

}

/* Make a bit.ly short URL */

function tmp_makeBitlyUrl( $url ) {

	require_once( ABSPATH . WPINC . "/class-snoopy.php" );
	
	$snoopy = new Snoopy;
	
	$snoopy->agent = "twimp-wp - http://raza.narfum.org/twimp-wp/";
	
	$snoopy->user = get_option( "bitly_username" );
	$snoopy->pass = get_option( "bitly_apikey" );
	
	$fetchUrl = BITLY_FETCH_URL . urlencode ( $url );
	
	$snoopy->fetch( $fetchUrl );
	
	if ( strpos( $snoopy->response_code, "200" ) ) {
		
		$response = json_decode( $snoopy->results, true );
		
		return $response["results"][$url]["shortUrl"];
	
	} else {
	
		return false;
	
	}
}

/* Add actions */

function addActions() {
	
	global $current_user, $userlevels;

	if ( intval ( $current_user->wp_user_level ) >= $userlevels[get_option( "twitter_role" )] ) {
	
		add_action('show_user_profile', 'tmp_addTwitterFieldsToProfile');  
		add_action('personal_options_update', 'tmp_updateTwitterFields');
		add_action('dbx_post_sidebar', 'tmp_showTwitterSubmitOptions');
		add_action('publish_post', 'tmp_processTwitterSubmitOptions');

	}

}

add_action('set_current_user', 'addactions');
add_action('admin_menu', 'tmp_menu');

?>
