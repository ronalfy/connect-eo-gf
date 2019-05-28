<?php
namespace EmailOctopus\GF\Feeds;
use EmailOctopus\GF\API\Helper\EOGF_API_Helper as EmailOctopusAPI;
\GFForms::include_feed_addon_framework();
class EOGF_Feeds extends \GFFeedAddOn {
	protected $_version = '1.0.0';
	protected $_min_gravityforms_version = '2.3.0';
	protected $_slug = 'EOGF_API';
	protected $_path = 'connect-eo-gf/connect-eo-gf';
	protected $_full_path = __FILE__;
	protected $_title = 'EmailOctopus';
	protected $_short_title = 'EmailOctopus';
	// Members plugin integration
	protected $_capabilities = array( 'emailoctopus_gravity_forms', 'emailoctopus_gravity_forms_uninstall' );
	// Permissions
	protected $_capabilities_settings_page = 'emailoctopus_gravity_forms';
	protected $_capabilities_form_settings = 'emailoctopus_gravity_forms';
	protected $_capabilities_uninstall = 'emailoctopus_gravity_forms_uninstall';

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Feed starting point.
	 *
	 * @since  1.0
	 * @access public
	 *
	 */
	public function init() {

		parent::init();

	}

	/**
	 * Return an array of EmailOctopus list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function merge_vars_field_map() {

		// Initialize field map array.
		$field_map = array(
			'EmailAddress' => array(
				'name'       => 'EmailAddress',
				'label'      => esc_html__( 'Email Address', 'connect-eo-gf' ),
				'required'   => true,
				'field_type' => array( 'email', 'hidden' ),
			),
		);

		// Get current list ID.
		$list_id = $this->get_setting( 'emailoctopuslist' );
		if( empty( $list_id ) ) {
			return array();
		}

		// Get API List
		$lists = EmailOctopusAPI::get_list();
		$selected_list = false;
		foreach( $lists as $list ) {
			if( $list_id === $list->id ) {
				$selected_list = $list;
				break;
			}
		}

		$selected_list_fields = $selected_list->fields;
		foreach( $selected_list_fields as $field ) {

			// Define required field type.
			$field_type = null;

			if( 'EmailAddress' === $field->tag ) {
				$field_type = array( 'email', 'hidden' );
			}

			if( 'NUMBER' === $field->type ) {
				$field_type = array( 'number' );
			}

			$field_map[ $field->tag ] = array(
				'name'       => $field->tag,
				'label'      => $field->label,
				'required'   => 'EmailAddress' === $field->tag ? true : false,
				'field_type' => $field_type,
			);
		}

		return $field_map;
	}

	/**
	 * Form settings page title
	 *
	 * @since 1.0.0
	 * @return string Form Settings Title
	 */
	public function feed_settings_title() {
		return __( 'EmailOctopus Settings', 'connect-eo-gf' );
	}

	/**
	 * Feed settings
	 *
	 * @since 1.0.0
	 * @return array feed settings
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'EmailOctopus Feed Settings', 'connect-eo-gf' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'connect-eo-gf' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'connect-eo-gf' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'connect-eo-gf' )
						),
					),
					array(
						'name'     => 'emailoctopuslist',
						'label'    => esc_html__( 'EmailOctopus List', 'connect-eo-gf' ),
						'type'     => 'emailoctopus_list',
						'required' => true,
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'EmailOctopus List', 'connect-eo-gf' ),
							esc_html__( 'Select the EmailOctopus list you would like to add your contacts to.', 'connect-eo-gf' )
						),
					),
				),
			),
			array(
				'dependency' => 'emailoctopuslist',
				'fields'     => array(
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'connect-eo-gf' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'connect-eo-gf' ),
							esc_html__( 'Associate your EmailOctopus merge tags to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'connect-eo-gf' )
						),
					),
					array(
						'name'    => 'options',
						'label'   => esc_html__( 'Options', 'connect-eo-gf' ),
						'type'    => 'emailoctopus_optin',
					),
					array(
						'name'    => 'optinCondition',
						'label'   => esc_html__( 'Conditional Logic', 'connect-eo-gf' ),
						'type'    => 'feed_condition',
						'tooltip' => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'connect-eo-gf' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to EmailOctopus when the conditions are met. When disabled all form submissions will be exported.', 'connect-eo-gf' )
						),
					),
					array( 'type' => 'save' ),
				)
			),
		);
	}

	/**
	 * Define the markup for the opt-in checkbox.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $field The field properties.
	 * @param bool  $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_emailoctopus_optin( $field, $echo = true ) {
		$html = '';

		$lists = EmailOctopusAPI::get_list();

		// Get current list ID.
		$list_id = $this->get_setting( 'emailoctopuslist' );
		$selected_list = false;
		foreach( $lists as $list ) {
			if( $list_id === $list->id ) {
				$selected_list = $list;
				break;
			}
		}

		$double_opt_in = $selected_list->double_opt_in;
		if( $double_opt_in ) {
			$html .= sprintf( '<label><input type="checkbox" disabled="disabled" checked="checked" /> %s</label>', sprintf( __('Double opt-in is enabled on this list. Go to <a href="%s" target="_blank">your EmailOctopus dashboard</a> to make changes.','connect-eo-gf'), esc_url( 'https://emailoctopus.com/lists/' . $list_id . '/info-and-settings/opt-in-email') ) );
		} else {
			$html .= sprintf( '<label><input type="checkbox" disabled="disabled" /> %s</label>', sprintf( __('Double opt-in is disabled on this list. Go to <a href="%s" target="_blank">your EmailOctopus dashboard</a> to make changes.','connect-eo-gf'), esc_url( 'https://emailoctopus.com/lists/' . $list_id . '/info-and-settings/opt-in-email') ) );
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Process the feed, subscribe the user to the list.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {

		// Get field map values.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Get mapped email address.
		$email = $this->get_field_value( $form, $entry, $field_map['EmailAddress'] );

		// If email address is invalid, log error and return.
		if ( \GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'A valid Email address must be provided.', 'connect-eo-gf' ), $feed, $entry, $form );
			return $entry;
		}

		// Initialize array to store merge vars.
		$merge_vars = array();

		// Loop through field map.
		$body = array();
		foreach ( $field_map as $name => $field_id ) {

			// If no field is mapped, skip it.
			if ( rgblank( $field_id ) ) {
				continue;
			}

			if( 'EmailAddress' === $name ) {
				$body['email_address'] = $this->get_field_value( $form, $entry, $field_id );
				continue;
			}

			// Set merge var name to current field map name.
			$this->merge_var_name = $name;

			// Get field object.
			$field = \GFFormsModel::get_field( $form, $field_id );

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			if ( empty( $field_value ) ) {
				continue;
			}

			$merge_vars[ $name ] = $field_value;
		}

		// Get API Endpoint
		$api = sprintf( 'https://emailoctopus.com/api/1.5/lists/%s/contacts', sanitize_text_field( $feed['meta']['emailoctopuslist'] ) );

		// Get API Key
		$gforms_api_options = get_option('emailoctopus_gf_settings', false);
		$saved_api_key = isset( $gforms_api_options['api_key'] ) ? $gforms_api_options['api_key'] : false;
		$saved_connect_status = isset( $gforms_api_options['connected'] ) ? $gforms_api_options['connected'] : false;
		$body['api_key'] = $saved_api_key;

		// Add field values to body
		$body['fields'] = $merge_vars;

		// Perform Sending Data to EmailOctopus API
		$response = wp_remote_post($api, array('body' => $body));
        $response_body = wp_remote_retrieve_body($response);
		$response_body = json_decode($response_body);

		// Error handling
        if (isset($response_body->error)) {
			// Log that the subscriber could not be added.
			$this->add_feed_error( sprintf( esc_html__( 'Unable to add subscriber: %s', 'connect-eo-gf' ), $response_body->error->message ), $feed, $entry, $form );
		}

		// Otherwise smooth sailing!
		return;
	}

	public function get_list_merge_fields( $list_id = '' ) {

		// If merge fields have already been retrieved, return.
		if ( isset( $this->merge_fields[ $list_id ] ) ) {
			return $this->merge_fields[ $list_id ];
		}
	}

	/**
	 * Define the markup for the emailoctopus_list type field.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $field The field properties.
	 * @param bool  $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_emailoctopus_list( $field, $echo = true ) {
		$html = '';

		$lists = EmailOctopusAPI::get_list();

		if( ! empty( $lists ) ) {
			// Initialize select options.
			$options = array(
				array(
					'label' => esc_html__( 'Select an EmailOctopus List', 'connect-eo-gf' ),
					'value' => '',
				),
			);

			// Loop through EmailOctopus lists.
			foreach ( $lists as $list ) {

				// Add list to select options.
				$options[] = array(
					'label' => esc_html( $list->name ),
					'value' => esc_attr( $list->id ),
				);
			}

			// Add select field properties.
			$field['type']     = 'select';
			$field['choices']  = $options;
			$field['onchange'] = 'jQuery(this).parents("form").submit();';

			// Generate select field.
			$html = $this->settings_select( $field, false );

		} else {
			$html .= _( 'There are no lists to select from your account.', 'connect-eo-gf');
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Prevent feeds being listed or created if the API key isn't valid.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return bool
	 */
	public function can_create_feed() {
		return EmailOctopusAPI::is_connected();
	}

	/**
	 * Returns the value to be displayed in the EmailOctopus List column.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_emailoctopus_list_name( $feed ) {
		$lists = EmailOctopusAPI::get_list();
		$list_id = $feed['meta']['emailoctopuslist'];
		foreach( $lists as $list ) {
			if( $list_id === $list->id ) {
				return $list->name;
			}
		}
		return '';
	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'            => esc_html__( 'Name', 'connect-eo-gf' ),
			'emailoctopus_list_name' => esc_html__( 'EmailOctopus List', 'connect-eo-gf' ),
		);
	}

}
