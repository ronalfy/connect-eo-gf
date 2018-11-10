<?php
namespace EmailOctopus\GF\API\Helper;
class EOGF_API_Helper {
	/**
	 * Checks for a valid API key
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $api_key The API key to check
	 *
	 * @return bool Whether the API key is valid or not
	 */
	public static function is_valid_api_key($api_key) {
		if (!$api_key) {
			return false;
		}

		$api_key = sanitize_text_field( $api_key );

		// Check option for api key status
		$gforms_api_options = get_option('emailoctopus_gf_settings', false);
		$saved_api_key = isset( $forms_api_options['api_key'] ) ? $forms_api_options['api_key'] : false;
		$saved_connect_status = isset( $forms_api_options['connected'] ) ? $forms_api_options['connected'] : false;

		if ($saved_api_key && $saved_connect_status === 'connected') {
			return true;
		}

		// Format API call
		$api_endpoint = 'https://emailoctopus.com/api/1.5/lists';
		$api_url = add_query_arg(
			array(
				'api_key' => $api_key,
				'limit' => 100,
				'page' => 1,
			),
			$api_endpoint
		);
		$api_response = wp_remote_get($api_url);
		if (!is_wp_error($api_response)) {
			$body = json_decode(wp_remote_retrieve_body($api_response));
			if (isset($body->error)) {
				return false;
			}
			$gforms_api_options = get_option('emailoctopus_gf_settings', false);
			$gforms_api_options['api_key'] = $api_key;
			$gforms_api_options['connected'] = 'connected';
			update_option('emailoctopus_gf_settings', $gforms_api_options);
			return true;
		}
		return false;
	}

	/**
	 * Gets a list from the EmailOctopus API
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array List content if successful
	 */
	public static function get_list()
	{
		$gforms_api_options = get_option('emailoctopus_gf_settings', false);
		$saved_api_key = isset( $gforms_api_options['api_key'] ) ? $gforms_api_options['api_key'] : false;
		$saved_connect_status = isset( $gforms_api_options['connected'] ) ? $gforms_api_options['connected'] : false;

		if ($saved_api_key && $saved_connect_status === 'connected') {
			// Format API call
			$api_endpoint = 'https://emailoctopus.com/api/1.5/lists';
			$api_url = add_query_arg(
				array(
					'api_key' => $saved_api_key,
					'limit' => 100,
					'page' => 1,
				),
				$api_endpoint
			);
			$api_response = wp_remote_get($api_url);
			if (!is_wp_error($api_response)) {
				$body = json_decode(wp_remote_retrieve_body($api_response));
				if (isset($body->error)) {
					return array();
				}

				return $body->data;
			}
		} else {
			return array();
		}
	}

	/**
	 * Checks for a valid connected API key
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return bool Whether the API key is valid or not
	 */
	public static function is_connected() {
		// Check option for api key status
		$gforms_api_options = get_option('emailoctopus_gf_settings', false);
		$saved_api_key = isset( $gforms_api_options['api_key'] ) ? $gforms_api_options['api_key'] : false;
		$saved_connect_status = isset( $gforms_api_options['connected'] ) ? $gforms_api_options['connected'] : false;
		if ($saved_api_key && 'connected' === $saved_connect_status) {
			return true;
		}
		return false;
	}
}
