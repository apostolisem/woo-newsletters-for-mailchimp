<?php

/*
Plugin Name: WooCommerce Newsletters for MailChimp
Plugin URI: https://wpcare.gr
Description: Easily create a new newsletter with WooCommerce products, send it to your customers via MailChimp with one click.
Version: 1.0.0
Author: WPCARE
Author URI: https://wpcare.gr
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woo-newsletters-for-mailchimp
*/

if (! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

/*
	#nav-mark: TABLE OF CONTENTS
	1. HOOKS
		1.1 - add_action - wpcf_admin_menus
		1.2 - register_deactivation_hook - wpcf_deactivation_hook
		1.3 - add_action - wpcf_register_options
		1.4 - register_activation_hook - wpcf_activation_hook

	2. SHORTCODES

	3. FILTERS
		3.1 - wpcf_admin_menus()

	4. EXTERNAL SCRIPTS
		4.1 - include - newsletter-generator.php

	5. ACTIONS
		5.1 - wpcf_deactivation_hook()
		5.2 - wpcf_activation_hook()
		5.3 - wpcf_write_log()
		5.4 - wpcf_woo_checkout_add_client_to_mailchimp()

	6. HELPERS
		6.1 - wpcf_support_url()
		6.2 - wpcf_woo_enabled()
		6.3 - wpcf_get_option()
		6.4 - wpcf_get_current_options()
		6.5 - wpcf_add_mailchimp_subscriber()
		6.6 - wpcf_add_mailchimp_subscriber()

	7. CUSTOM POST TYPES

	8. ADMIN PAGES
		8.1 - wpcf_options_admin_page()

	9. SETTINGS
		9.1 - wpcf_register_options()

	10. MISCELLANEOUS

*/

#nav-mark: 1. HOOKS

	// 1.1
	add_action('admin_menu', 'wpcf_admin_menus');

	// 1.2
	register_deactivation_hook(__FILE__, 'wpcf_deactivation_hook');

	// 1.3
	add_action('admin_init', 'wpcf_register_options');

	// 1.4
	register_activation_hook( __FILE__, 'wpcf_activation_hook' );


#nav-mark: 2. SHORTCODES


#nav-mark: 3. FILTERS

	// 3.1
	function wpcf_admin_menus() {

		/* main menu */
		$top_menu_item = 'wpcf_dashboard_admin_page';
		if (wpcf_woo_enabled() == true) {
			add_menu_page( 'Newsletter Generator', 'Newsletter Generator', 'edit_posts', 'wpcf_compose_newsletter_admin_page', 'wpcf_compose_newsletter_admin_page', 'dashicons-admin-generic' );
		}


	}


#nav-mark: 4. EXTERNAL SCRIPTS

	// 4.1
	include('scripts/newsletter-generator.php');


#nav-mark: 5. ACTIONS

	// 5.1
	function wpcf_deactivation_hook() {
		wpcf_write_log('Plugin was succesfully Deactivated.');
	}

	// 5.2
	function wpcf_activation_hook() {
		// check if version of wp is updated
		if ( version_compare( get_bloginfo( 'version' ), '4.9.7', '<' ) )  {
			wpcf_write_log('Plugin was NOT activated because the vesrion of WordPress is old. An update to the latest version is required.');
			wp_die("You must update WordPress to use this plugin!");
		} elseif (!get_option('wpcf_first_time_installed')) {

			$email_template = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
			<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html;UTF-8">
			</head>
			<body style="background: #efefef; font: 13px \'Lucida Grande\', \'Lucida Sans Unicode\', Tahoma, Verdana, sans-serif; padding: 5px 0 10px" bgcolor="#efefef">
				<style type="text/css">
				body {
					font: 13px "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana, sans-serif; background-color: #efefef; padding: 5px 0 10px 0;
				}
				</style>
				<div id="body" style="width: 600px; background-color:#ffffff; padding: 30px; margin: 30px; text-align: left;">

				<p><img src="'.plugins_url( "images/email-logo.png", __FILE__ ).'" /></p>

					%content%

				</div>
			</body>
			</html>';

			// create the default option values if it's the first time the plugin is installed
			update_option( 'wpcf_first_time_installed', 'no', '', 'yes' );
			// insert default values
			update_option( 'wpcf_mailchimp_integration', '', '', 'yes' );
			update_option( 'wpcf_subscribe_clients_to_mailchimp_on_checkout', '', '', 'yes' );
			update_option( 'wpcf_mailchimp_list_id', '', '', 'yes' );
			update_option( 'wpcf_mailchimp_api_key', '', '', 'yes' );
			update_option( 'wpcf_mailchimp_pool', '', '', 'yes' );
			update_option( 'wpcf_email_template', $email_template, '', 'yes' );
			update_option( 'wpcf_newsletter_logo', '', '', 'yes' );

		}
		wpcf_write_log('Plugin was succesfully Activated.');
	}

	// 5.3
	function wpcf_write_log( $log, $function='' ) {

		$upload_dir = wp_upload_dir();

		if ($function !== '') {
			$File = $upload_dir['basedir']."/wpcf-plugin-logs/function-".$function.".log";
		} else {
			$File = $upload_dir['basedir']."/wpcf-plugin-logs/function-core.log";
		}

		if (!file_exists($upload_dir['basedir']."/wpcf-plugin-logs")) {
		    mkdir($upload_dir['basedir']."/wpcf-plugin-logs", 0755, true);
		}

	 	$Handle = fopen($File, 'a');
	 	$Data = date("Y-m-d H:i:s")." - ".$log."\r\n";
	 	fwrite($Handle, $Data);
	 	fclose($Handle);

	}

	// 5.4
	function wpcf_woo_checkout_add_client_to_mailchimp( $order_id ) {

		// check if function is enabled at plugin options
		if (get_option( 'wpcf_mailchimp_integration' ) !== "on") return;

		// check if function is enabled at plugin options
		if (get_option( 'wpcf_subscribe_clients_to_mailchimp_on_checkout' ) !== "on") return;

		$order = new WC_Order( $order_id );

		// check if we added already the email to mailchimp before
		$wpcf_mailchimp_pool = get_option( 'wpcf_mailchimp_pool' );
		if (preg_match("/".$order->billing_email."/i", $wpcf_mailchimp_pool)) return;

		$data = [
			'email'     => $order->billing_email,
			'status'    => 'subscribed',
			'firstname' => $order->billing_first_name,
			'lastname'  => $order->billing_last_name,
		];

		wpcf_add_mailchimp_subscriber($data);
		update_option('wpcf_mailchimp_pool',$order->billing_email."::".$wpcf_mailchimp_pool);

		return;

	}

#nav-mark: 6. HELPERS

	// 6.1
	function wpcf_support_url() {

		return 'http://company.gr';

	}

	// 6.2
	function wpcf_woo_enabled() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	// 6.3
	function wpcf_get_option( $option_name ) {

		// setup return variable
		$option_value = '';

		try {

			// get the requested option
			switch( $option_name ) {

				case 'wpcf_mailchimp_integration':
				// wpcf_mailchimp_integration
				$option_value = get_option('wpcf_mailchimp_integration');
				break;
				case 'wpcf_subscribe_clients_to_mailchimp_on_checkout':
				// wpcf_subscribe_clients_to_mailchimp_on_checkout
				$option_value = get_option('wpcf_subscribe_clients_to_mailchimp_on_checkout');
				break;
				case 'wpcf_mailchimp_list_id':
				// wpcf_mailchimp_list_id
				$option_value = get_option('wpcf_mailchimp_list_id');
				break;
				case 'wpcf_mailchimp_api_key':
				// wpcf_mailchimp_api_key
				$option_value = get_option('wpcf_mailchimp_api_key');
				break;
				case 'wpcf_email_template':
				// wpcf_email_template
				$option_value = get_option('wpcf_email_template');
				break;
				case 'wpcf_newsletter_logo':
				// wpcf_newsletter_logo
				$option_value = get_option('wpcf_newsletter_logo');
				break;

			}

		} catch( Exception $e) {

			// php error

		}

		// return option value or it's default
		return $option_value;

	}

	// 6.4
	function wpcf_get_current_options() {

		// setup our return variable
		$current_options = array();

		try {

			// build our current options associative array
			$current_options = array(
				'wpcf_mailchimp_integration' => wpcf_get_option('wpcf_mailchimp_integration'),
				'wpcf_subscribe_clients_to_mailchimp_on_checkout' => wpcf_get_option('wpcf_subscribe_clients_to_mailchimp_on_checkout'),
				'wpcf_mailchimp_list_id' => wpcf_get_option('wpcf_mailchimp_list_id'),
				'wpcf_mailchimp_api_key' => wpcf_get_option('wpcf_mailchimp_api_key'),
				'wpcf_email_template' => wpcf_get_option('wpcf_email_template'),
				'wpcf_newsletter_logo' => wpcf_get_option('wpcf_newsletter_logo'),
			);

		} catch( Exception $e ) {

			// php error

		}

		// return current options
		return $current_options;

	}

	// 6.5
	function wpcf_add_mailchimp_subscriber($data) {

		// get the api key and list id from wp options database
		$apiKey = get_option('wpcf_mailchimp_api_key');
		$listId = get_option('wpcf_mailchimp_list_id');

		// check that we have all the data we need to authenticate with MailChimp
		if (strlen($apiKey) < 1) return 'ERROR: API key is missing!';
		if (strlen($listId) < 1) return 'ERROR: List ID is missing!';

		$memberId = md5(strtolower($data['email']));
		$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
		$url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

		$json = json_encode([
			'email_address' => $data['email'],
			'status'        => $data['status'], // "subscribed","unsubscribed","cleaned","pending"
			'merge_fields'  => [
				'FNAME'     => $data['firstname'],
				'LNAME'     => $data['lastname']
			]
		]);

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $httpCode;

	}

	// 6.6
	function wpcf_mailchimp_api_request( $endpoint, $type = 'POST', $body = '' ) {

	    // Configure --------------------------------------

	    $api_key = get_option( 'wpcf_mailchimp_api_key' );

	    // STOP Configuring -------------------------------

	    $core_api_endpoint = 'https://<dc>.api.mailchimp.com/3.0/';
	    list(, $datacenter) = explode( '-', $api_key );
	    $core_api_endpoint = str_replace( '<dc>', $datacenter, $core_api_endpoint );

	    $url = $core_api_endpoint . $endpoint;

	    $request_args = array(
	        'method'      => $type,
	        'timeout'     => 20,
	        'headers'     => array(
	            'Content-Type' => 'application/json',
	            'Authorization' => 'apikey ' . $api_key
	        )
	    );

	    if ( $body ) {
	        $request_args['body'] = json_encode( $body );
	    }

	    $request = wp_remote_post( $url, $request_args );
	    $response = is_wp_error( $request ) ? false : json_decode( wp_remote_retrieve_body( $request ) );

	    return $response;
	}



#nav-mark: 7. CUSTOM POST TYPES


#nav-mark: 8. ADMIN PAGES

	// 8.1
	// todos: add form validation of the plugin options
	function wpcf_options_admin_page() {

		// get the default values for our options
		$site_url = urlencode(get_bloginfo( 'url' ));
		$options = wpcf_get_current_options();
		$checked = array();

		$checked['wpcf_subscribe_clients_to_mailchimp_on_checkout'] = ($options['wpcf_subscribe_clients_to_mailchimp_on_checkout']) ? 'checked' : '';

		echo('<div class="wrap">

			<h2>WordPress Care Plugin Options</h2>
			<p>Adjust the settings of the plugin functions. You can enable or disable any Function from admin menu "Manage Functions".</p>

			<form action="options.php" method="post">');

			// outputs a unique nounce for our plugin options
			settings_fields('wpcf_plugin_options');
			// generates a unique hidden field with our form handling url
			do_settings_fields('wpcf_plugin_options','');


			echo('<h3>Email<span class="function-woo"> & Newsletter</span> Options</h3>
				<p>You can set various email options from this section.</p>

				<table class="form-table">

					<tbody>');

					wp_enqueue_script('jquery');
					wp_enqueue_media();

			    echo('

					<tr>
						<th scope="row"><label for="wpcf_newsletter_logo">Newsletter Logo</label></th>
						<td>
						<input type="text" name="wpcf_newsletter_logo" id="wpcf_newsletter_logo" class="regular-text" value="'.$options['wpcf_newsletter_logo'].'">
				    <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Upload Image">
							<p class="description" id="wpcf_newsletter_logo-description">Set a logo to appear on top of Newsletters that you generate with WordPress Care Functions plugin. If you leave it empty, the name of the website will appear instead.</p>
						</td>
					</tr>

					<script type="text/javascript">
					jQuery(document).ready(function($){
					    $(\'#upload-btn\').click(function(e) {
					        e.preventDefault();
					        var image = wp.media({
					            title: \'Upload Image\',
					            // mutiple: true if you want to upload multiple files at once
					            multiple: false
					        }).open()
					        .on(\'select\', function(e){
					            // This will return the selected image from the Media Uploader, the result is an object
					            var uploaded_image = image.state().get(\'selection\').first();
					            // We convert uploaded_image to a JSON object to make accessing it easier
					            // Output to the console uploaded_image
					            console.log(uploaded_image);
					            var image_url = uploaded_image.toJSON().url;
					            // Let\'s assign the url value to the input field
					            $(\'#wpcf_newsletter_logo\').val(image_url);
					        });
					    });
					});
					</script>

					<tr>
						<th scope="row"><label for="wpcf_email_template">Default Email Template</label>
						<p class="description" style="display:none;" id="wpcf_email_template-description">The emails sent from WordPress Care Functions Plugin will use this template as default. Use the variable <strong>%content%</strong> where the message content should appear. This does not apply to Newsletters generated by this plugin.</p></th>
						<td>
						<textarea name="wpcf_email_template" rows="15" id="wpcf_email_template" style="display:none;" class="large-text code">'.$options['wpcf_email_template'].'</textarea>
						<input type="button" class="button" id="wpcf_email_template_edit_button" onclick="AddProductSelect()" value="Edit the Template" />
						<script type="text/javascript">
						function AddProductSelect() {
						    document.getElementById("wpcf_email_template_edit_button").style.display = "none";
						    document.getElementById("wpcf_email_template").style.display = "block";
						    document.getElementById("wpcf_email_template-description").style.display = "block";
						}
						</script>
						</td>
					</tr>

					</tbody>

				</table>');

				// get the lists available from mailchimp api
				$mailchimp_lists = wpcf_mailchimp_api_request( "lists", 'GET' );

				if (!empty($mailchimp_lists->lists)) {
					$mc_lists = $mailchimp_lists->lists;
					update_option( 'wpcf_mailchimp_lists', $mailchimp_lists->lists );
				} else {
					$mc_lists = get_option( 'wpcf_mailchimp_lists' );
				}

				// if the mailchimp is deleted then delete the list id also
				if (empty($options['wpcf_mailchimp_api_key'])) {
					$mc_lists = null;
					update_option( 'wpcf_mailchimp_lists', '' );
					update_option( 'wpcf_mailchimp_list_id', '' );
				}

				echo('<span class="function-mailchimp"><h3>MailChimp Integration Settings</h3>
				<p>You can set the MailChimp Integration settings below.</p>

				<table class="form-table">

					<tbody>

					<tr class="function-mailchimp">
						<th scope="row"><label for="wpcf_mailchimp_api_key">MailChimp API Key</label></th>
						<td>
							<input type="text" class="regular-text" id="wpcf_mailchimp_api_key" name="wpcf_mailchimp_api_key" value="'.$options['wpcf_mailchimp_api_key'].'">
							<p class="description" id="wpcf_mailchimp_api_key-description">Please enter the MailChimp API Key. You can create one from <a href="https://admin.mailchimp.com/account/api/" target="_blank">here</a>.</p>
						</td>
					</tr>

					<tr class="function-mailchimp">
						<th scope="row"><label for="wpcf_mailchimp_list_id">Selected MailChimp List</label></th>
						<td>
							<select id="wpcf_mailchimp_list_id" name="wpcf_mailchimp_list_id">
								<option>- Select a List -</option>');

								foreach ($mc_lists as $mc_list) {
									if ($options['wpcf_mailchimp_list_id'] == $mc_list->id) { $selected='selected="selected"'; }
									echo '<option value="'.$mc_list->id.'" '.$selected.'>'.$mc_list->name.'</option>';
								}

							echo ('</select>

							<p class="description" id="wpcf_mailchimp_list_id-description">Please select a MailChimp List. First you have to enter the MailChimp API above.</p>
						</td>
					</tr>

					<tr class="function-woo function-mailchimp">
						<th scope="row"><label for="wpcf_subscribe_clients_to_mailchimp_on_checkout">Subscribe Clients on Checkout</label></th>
						<td>
							<input type="checkbox" id="wpcf_subscribe_clients_to_mailchimp_on_checkout" name="wpcf_subscribe_clients_to_mailchimp_on_checkout" value="on" '.$checked['wpcf_subscribe_clients_to_mailchimp_on_checkout'].'>
							<p class="description" id="wpcf_subscribe_clients_to_mailchimp_on_checkout-description">Enable the Auto-Subscribe of Clients to MailChimp when they place a New Order.</p>
						</td>
					</tr>


					</tbody>

				</table></span>');

				// outputs the WP submit button html
				@submit_button();

			echo('<input type="hidden" name="save_options" value="yes">

			</form>

		</div>');

	}


#nav-mark: 9. SETTINGS

	// 9.1
	function wpcf_register_options() {
		// plugin functions
		register_setting('wpcf_plugin_functions', 'wpcf_mailchimp_integration');
		// plugin options
		register_setting('wpcf_plugin_options', 'wpcf_subscribe_clients_to_mailchimp_on_checkout');
		register_setting('wpcf_plugin_options', 'wpcf_mailchimp_list_id');
		register_setting('wpcf_plugin_options', 'wpcf_mailchimp_api_key');
		register_setting('wpcf_plugin_options', 'wpcf_email_template');
		register_setting('wpcf_plugin_options', 'wpcf_newsletter_logo');
	}


#nav-mark: 10. MISCELLANEOUS
