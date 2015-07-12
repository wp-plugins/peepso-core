<?php

class PeepSoLicense
{
    const PEEPSO_HOME = 'http://peepso.com';

    const OPTION_DATA = 'peepso_license_data';
    private static $_licenses = NULL;

    /**
     * Verifies the license key for an add-on by the plugin's slug
     * @param string $plugin_edd The PLUGIN_NAME constant value for the plugin being checked
     * @param string $plugin_slug The PLUGIN_SLUG constant value for the plugin being checked
     * @return boolean TRUE if the license is active and valid; otherwise FALSE.
     */
    public static function check_license($plugin_edd, $plugin_slug)
    {

        $license_data = self::_get_product_data($plugin_slug);

        if (NULL === $license_data) {
            // no license data exists; create it
            $license_data['slug'] = $plugin_slug;
            $license_data['name'] = $plugin_edd;
            $license_data['license'] = '';
            $license_data['state'] = 'invalid';
            $license_data['expire'] = 0;
            // write the license data
        }

        if(is_admin()) {

            self::_set_product_data($plugin_slug, $license_data);
            self::activate_license($plugin_slug, $plugin_edd);
        }

        // check to see if the license key is valid for the named plugin

        return (self::is_valid_key($plugin_edd));
    }

    /**
     * Activates the license key for a PeepSo add-on
     * @param string $plugin_slug The add-on's slug name
     * @param string $plugin_edd The add-on's full plugin name
     * @return boolean TRUE on successful activation; otherwise FALSE
     */
    public static function activate_license($plugin_slug, $plugin_edd)
    {
        // get key stored from config pages
        $key = self::_get_key($plugin_slug);

        $license_data['license'] = $key;
        $license_data['name'] = $plugin_edd;

        if (FALSE === $key || 0 === strlen($key))
            return;

        $args = array(
            'edd_action' => 'check_license',
            'license' => $key,
            'item_name' => urlencode($plugin_edd)
        );

        // use transient
        $trans_key = 'peepso_license_' . md5($key . $plugin_slug);
        if (FALSE === ($validation_data = get_transient($trans_key))) {
            $resp = wp_remote_get(add_query_arg($args, self::PEEPSO_HOME),	// contact the home office
                array('timeout' => 15, 'sslverify' => FALSE));				// options

            // if there was an error validating, bug out
            if (is_wp_error($resp))
                return (FALSE);

            /*			response={"license":"valid",
            *				"item_name":"PeepSo Friends",
            *				"expires":"2016-01-16 08:24:57",
            *				"payment_id":"0000",				// invoice number
            *				"customer_name":"Jon Doe",
            *				"customer_email":"jondoe@mail.com",
            *				"license_limit":"1",
            *				"site_count":1,
            *				"activations_left":0
            *			}
            */
            $response = wp_remote_retrieve_body($resp);
            $validation_data = json_decode($response);
            set_transient($trans_key, $validation_data, 12 * HOUR_IN_SECONDS);
        }
        // @todo it used to check "active"
        // @todo expiration date?
        if ('valid' === $validation_data->license) {

            // if parent site reports the license is active, update the stored data for this plugin
            $license_data['state'] = 'valid';
            $license_data['expire'] = $validation_data->expires;
            self::_set_product_data($plugin_slug, $license_data);
        } else {

            // OLD: else if ('inactive' === $validation_data->license)

            // if parent site reports the license as inactive, update the stored data as well
            $license_data['state'] = 'invalid';
            $license_data['expire'] = 0;
            self::_set_product_data($plugin_slug, $license_data);
        }
    }

    /**
     * Loads the license information from the options table
     */
    private static function _load_licenses()
    {
        if (NULL === self::$_licenses) {
            $lisc = get_option(self::OPTION_DATA, FALSE);
            if (FALSE === $lisc) {
                $lisc = array();
                add_option(self::OPTION_DATA, $lisc, FALSE, FALSE);
            }
            self::$_licenses = $lisc;
        }
    }

    /**
     * Retrieves product data for a given add-on by slug name
     * @param string $plugin_slug The plugin's slug name
     * @return mixed The data array stored for the plugin or NULL if not found
     */
    private static function _get_product_data($plugin_slug)
    {
        self::_load_licenses();
        $plugin_slug = sanitize_key($plugin_slug);

        if (isset(self::$_licenses[$plugin_slug])) {
            // check license data for validity
            $data = self::$_licenses[$plugin_slug];
            $str = md5($plugin_slug . '|' . esc_html($data['name']) .
                '~' . $data['license'] . ',' . $data['expire'] . $data['state']);

            // return data only if checksum validates
            if (isset($data['checksum']) && $str === $data['checksum'])
                return ($data);
        }
        return (NULL);
    }

    /**
     * Sets the stored license information per product
     * @param string $plugin_slug The plugin's slug
     * @param array $data The data array to store
     */
    private static function _set_product_data($plugin_slug, $data)
    {
        /*
         * data:
         *	['slug'] = plugin slug
         *	['name'] = plugin name
         *	['license'] = license key
         *	['state'] = license state
         *	['expire'] = license expiration
         *	['checksum'] = checksum
         */

        $plugin_slug = sanitize_key($plugin_slug);
        $data['slug'] = $plugin_slug;
        $str = $plugin_slug . '|' . esc_html($data['name']) .
            '~' . $data['license'] . ',' . $data['expire'] . $data['state'];
        $data['checksum'] = md5($str);
        self::_load_licenses();
        self::$_licenses[$plugin_slug] = $data;
        update_option(self::OPTION_DATA, self::$_licenses);
    }

    /**
     * Get the license key stored for the named plugin
     * @param string $plugin_slug The PLUGIN_SLUG constant value for the add-on to obtain the license key for
     * @return string The entered license key or FALSE if the named license key is not found
     */
    private static function _get_key($plugin_slug)
    {
        return (PeepSo::get_option('site_license_' . $plugin_slug, FALSE));
    }

    /**
     * Determines if a key is valid and active
     * @param string $plugin Plugin slug name
     * @return boolean TRUE if the key for the named plugin is valid; otherwise FALSE
     */
    public static function is_valid_key($plugin)
    {
        self::_load_licenses();
        $plugin_slug = sanitize_key($plugin);
        if (!isset(self::$_licenses[$plugin_slug]))
            return (FALSE);
        $data = self::$_licenses[$plugin_slug];

        $str = $plugin_slug . '|' . esc_html($data['name']) .
            '~' . $data['license'] . ',' . $data['expire'] . $data['state'];

        $dt = new PeepSoDate($data['expire']);
//echo ': md5: ', md5($str), PHP_EOL;
//echo ': exp: ', $dt->Timestamp(), PHP_EOL;
//echo ': ts : ', time(), PHP_EOL;
//echo ': returning...', PHP_EOL;
        return (md5($str) === $data['checksum'] && 'valid' === $data['state'] && $dt->TimeStamp() > time());
    }

    public static function dump_data()
    {
        self::_load_licenses();
        var_export(self::$_licenses);
    }
}

/*
function edd_sample_theme_check_license() {
	$store_url = 'http://yoursite.com';
	$item_name = 'Your Item Name';
	$license = '834bbb2d27c02eb1ac11f4ce6ffa20bb';
	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_name' => urlencode( $item_name )
	);
	$response = wp_remote_get( add_query_arg( $api_params, $store_url ), array( 'timeout' => 15, 'sslverify' => false ) );
  	if ( is_wp_error( $response ) )
		return false;
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );
	if( $license_data->license == 'valid' ) {
		echo 'valid';
		exit;
		// this license is still valid
	} else {
		echo 'invalid';
		exit;
		// this license is no longer valid
	}
}

function edd_sample_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_activate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'edd_sample_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( EDD_SL_ITEM_NAME ), // the name of our product in EDD,
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "active" or "inactive"

		update_option( 'edd_sample_license_status', $license_data->license );

	}
}
*/

// EOF
