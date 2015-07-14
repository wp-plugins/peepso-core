<?php

class PeepSoLicense
{
    const PEEPSO_HOME = 'http://peepso.com';

    const OPTION_DATA = 'peepso_license_data';

	const PEEPSO_LICENSE_TRANS = 'peepso_license_';
    private static $_licenses = NULL;

    /**
     * Verifies the license key for an add-on by the plugin's slug
     * @param string $plugin_edd The PLUGIN_NAME constant value for the plugin being checked
     * @param string $plugin_slug The PLUGIN_SLUG constant value for the plugin being checked
     * @return boolean TRUE if the license is active and valid; otherwise FALSE.
     */
    public static function check_license($plugin_edd, $plugin_slug, $is_admin = FALSE)
    {
		if( FALSE == $is_admin ) {
			$is_admin = is_admin();
		}

        $license_data = self::_get_product_data($plugin_slug);

        if (NULL === $license_data) {
            // no license data exists; create it
            $license_data['slug'] = $plugin_slug;
            $license_data['name'] = $plugin_edd;
            $license_data['license'] = '';
			$license_data['state'] = 'invalid';
			$license_data['response'] = 'invalid';
            $license_data['expire'] = 0;
            // write the license data
        }

        if ($is_admin) {
            self::_set_product_data($plugin_slug, $license_data);
            self::activate_license($plugin_slug, $plugin_edd);
        } else {
			// Frontend will return "FALSE" only in some scenarios
			if (!self::is_valid_key($plugin_edd)) {

				/*
				 * $license_data['response']
				 *
				 * invalid				FALSE - key BAD
				 * inactive				FALSE - key OK, not active
				 * item_name_mismatch 	FALSE - key OK, wrong plugin
				 * site_inactive		TRUE  - key OK, wrong domain
				 * expired				TRUE  - key OK, license expired
				 */

				if(!array_key_exists('response', $license_data)) {
					$license_data['response'] = 'valid';
				}

				switch ($license_data['response']) {
					case 'invalid':
					case 'inactive':
					case 'item_name_mismatch':
						return FALSE;
						break;
					default:
						return TRUE;
						break;
				}
			}
		}

        // check to see if the license key is valid for the named plugin
        return (self::is_valid_key($plugin_edd));
    }

	public static function get_license($plugin_slug)
	{
		return self::_get_product_data($plugin_slug);
	}

	public static function delete_transient($plugin_slug)
	{
		$trans_key = self::trans_key($plugin_slug);
		delete_transient($trans_key);
	}

	private static function trans_key($plugin_slug)
	{
		return self::PEEPSO_LICENSE_TRANS . $plugin_slug;
	}

    /**
     * Activates the license key for a PeepSo add-on
     * @param string $plugin_slug The add-on's slug name
     * @param string $plugin_edd The add-on's full plugin name
     * @return boolean TRUE on successful activation; otherwise FALSE
     */
    public static function activate_license($plugin_slug, $plugin_edd)
    {
        // how long to keep the transient keys?
		$trans_lifetime = 24 * HOUR_IN_SECONDS;

        // get key stored from config pages
        $key = self::_get_key($plugin_slug);

        $license_data['license'] = $key;
        $license_data['name'] = $plugin_edd;

        if (FALSE === $key || 0 === strlen($key)) {
			return;
		}

        // when asking EDD API use "item_id" if plugin_edd is numeric, otherwise "item_name"
        $key_type = 'item_name';

        if(is_int($plugin_edd)) {
            $key_type = 'item_id';
        }

        $args = array(
            'edd_action' => 'check_license',
            'license' => $key,
            $key_type => $plugin_edd
        );

        // Use transient key to check for cached values
		$trans_key = self::trans_key($plugin_slug);

		// If there is no cached value, call home
        if (FALSE === ($validation_data = get_transient($trans_key))) {

            $resp = wp_remote_get(add_query_arg($args, self::PEEPSO_HOME),	// contact the home office
                array('timeout' => 15, 'sslverify' => FALSE));				// options

            if (is_wp_error($resp)) {
				// If PeepSo.com is down build a fake license for 1 hour

				$trans_lifetime = 1 * HOUR_IN_SECONDS;

				$validation_data = new stdClass();

				$validation_data->success = true;

				$validation_data->license 			= 'valid';
  				$validation_data->item_name 		= $plugin_slug;
  				$validation_data->expires			= '2099-01-01 00:00:00';
  				$validation_data->payment_id		= 0;
  				$validation_data->customer_name 	= 'temporary';
  				$validation_data->customer_email	= 'temporary@peepso.com';
  				$validation_data->license_limit 	= 0;
  				$validation_data->site_count		= 0;
  				$validation_data->activations_left 	= 'unlimited';
			} else {
				$response = wp_remote_retrieve_body($resp);
				$validation_data = json_decode($response);
			}
            set_transient($trans_key, $validation_data, $trans_lifetime);
        }
        
        if ('valid' === $validation_data->license) {
            // if parent site reports the license is active, update the stored data for this plugin
			$license_data['state'] = 'valid';
        } else {
            // if parent site reports the license as inactive, update the stored data as well
			$license_data['state'] = 'invalid';
        }

		// remaining options
		$license_data['response'] = $validation_data->license;
		$license_data['expire'] = $validation_data->expires;

		// save
		self::_set_product_data($plugin_slug, $license_data);
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

		if (!isset(self::$_licenses[$plugin_slug])) {
			return (FALSE);
		}

        $data = self::$_licenses[$plugin_slug];

        $str = $plugin_slug . '|' . esc_html($data['name']) .
            '~' . $data['license'] . ',' . $data['expire'] . $data['state'];

        $dt = new PeepSoDate($data['expire']);

        return (md5($str) === $data['checksum'] && 'valid' === $data['state'] && $dt->TimeStamp() > time());
    }

	public static function get_key_state($plugin)
	{
		self::_load_licenses();
		$plugin_slug = sanitize_key($plugin);
		if (!isset(self::$_licenses[$plugin_slug])) {
			return "unknown";
		}

		$data = self::$_licenses[$plugin_slug];

		return array_key_exists('response', $data) ? $data['response'] : 'unknown';
	}

    public static function dump_data()
    {
        self::_load_licenses();
        var_export(self::$_licenses);
    }
}

// EOF
