<?php
namespace EmailOctopus\GF\Feeds;
use EmailOctopus\GF\API\Helper\EOGF_API_Helper as EmailOctopusAPI;
\GFForms::include_addon_framework();
class EOGF_Feeds extends \GFFeedAddOn {
	protected $_version = '1.0.0';
	protected $_min_gravityforms_version = '2.3.0';
	protected $_slug = 'EOGF_API';
	protected $_path = 'emailoctopus-gravity-forms/emailoctopus-gravity-forms';
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
				'label'      => esc_html__( 'Email Address', 'emailoctopus-gravity-forms' ),
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
		return __( 'EmailOctopus Settings', 'emailoctopus-gravity-forms' );
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
				'title'  => esc_html__( 'EmailOctopus Feed Settings', 'emailoctopus-gravity-forms' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'emailoctopus-gravity-forms' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'emailoctopus-gravity-forms' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'emailoctopus-gravity-forms' )
						),
					),
					array(
						'name'     => 'emailoctopuslist',
						'label'    => esc_html__( 'EmailOctopus List', 'emailoctopus-gravity-forms' ),
						'type'     => 'emailoctopus_list',
						'required' => true,
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'EmailOctopus List', 'emailoctopus-gravity-forms' ),
							esc_html__( 'Select the EmailOctopus list you would like to add your contacts to.', 'emailoctopus-gravity-forms' )
						),
					),
				),
			),
			array(
				'dependency' => 'emailoctopuslist',
				'fields'     => array(
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'emailoctopus-gravity-forms' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'emailoctopus-gravity-forms' ),
							esc_html__( 'Associate your EmailOctopus merge tags to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'emailoctopus-gravity-forms' )
						),
					),
					array(
						'name'    => 'options',
						'label'   => esc_html__( 'Options', 'emailoctopus-gravity-forms' ),
						'type'    => 'emailoctopus_optin',
					),
					array(
						'name'    => 'optinCondition',
						'label'   => esc_html__( 'Conditional Logic', 'emailoctopus-gravity-forms' ),
						'type'    => 'feed_condition',
						'tooltip' => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'emailoctopus-gravity-forms' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to EmailOctopus when the conditions are met. When disabled all form submissions will be exported.', 'emailoctopus-gravity-forms' )
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
			$html .= sprintf( '<label><input type="checkbox" disabled="disabled" checked="checked" /> %s</label>', sprintf( __('Double opt-in is enabled on this list. Go to <a href="%s" target="_blank">your EmailOctopus dashboard</a> to make changes.','emailoctopus-gravity-forms'), esc_url( 'https://emailoctopus.com/lists/' . $list_id . '/info-and-settings/opt-in-email') ) );
		} else {
			$html .= sprintf( '<label><input type="checkbox" disabled="disabled" /> %s</label>', sprintf( __('Double opt-in is disabled on this list. Go to <a href="%s" target="_blank">your EmailOctopus dashboard</a> to make changes.','emailoctopus-gravity-forms'), esc_url( 'https://emailoctopus.com/lists/' . $list_id . '/info-and-settings/opt-in-email') ) );
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
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {
		// Get field map values.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Get mapped email address.
		$email = $this->get_field_value( $form, $entry, $field_map['EmailAddress'] );
		
		// If email address is invalid, log error and return.
		if ( GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'A valid Email address must be provided.', 'emailoctopus-gravity-forms' ), $feed, $entry, $form );
			return $entry;
		}

		// Loop through field map.
		foreach ( $field_map as $name => $field_id ) {

			// If no field is mapped, skip it.
			if ( rgblank( $field_id ) ) {
				continue;
			}

			// If this is the email field, skip it.
			if ( $name === 'EmailAddress' ) {
				continue;
			}

			// Get merge field.
			$merge_field = $this->get_list_merge_field( $feed['meta']['emailoctopuslist'], $name );
		}
	}

	public_function get_list_merge_field() {

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
					'label' => esc_html__( 'Select an EmailOctopus List', 'emailoctopus-gravity-forms' ),
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
			$html .= _( 'There are no lists to select from your account.', 'emailoctopus-gravity-forms');
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
			'feedName'            => esc_html__( 'Name', 'emailoctopus-gravity-forms' ),
			'emailoctopus_list_name' => esc_html__( 'EmailOctopus List', 'emailoctopus-gravity-forms' ),
		);
	}

}
