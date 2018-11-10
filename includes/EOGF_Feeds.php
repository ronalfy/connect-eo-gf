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
	 * Form settings page title
	 *
	 * @since 1.0.0
	 * @return string Form Settings Title
	 */
	public function feed_settings_title() {
		return __( 'EmailOctopus Settings', 'emailoctopus-gravity-forms' );
	}

	public function feed_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'MailChimp Feed Settings', 'gravityformsmailchimp' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityformsmailchimp' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformsmailchimp' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsmailchimp' )
						),
					),
					array(
						'name'     => 'mailchimpList',
						'label'    => esc_html__( 'MailChimp List', 'gravityformsmailchimp' ),
						'type'     => 'mailchimp_list',
						'required' => true,
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'MailChimp List', 'gravityformsmailchimp' ),
							esc_html__( 'Select the MailChimp list you would like to add your contacts to.', 'gravityformsmailchimp' )
						),
					),
				),
			),
			array(
				'dependency' => 'mailchimpList',
				'fields'     => array(
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'gravityformsmailchimp' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformsmailchimp' ),
							esc_html__( 'Associate your MailChimp merge tags to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformsmailchimp' )
						),
					),
					array(
						'name'       => 'interestCategories',
						'label'      => esc_html__( 'Groups', 'gravityformsmailchimp' ),
						'dependency' => array( $this, 'has_interest_categories' ),
						'type'       => 'interest_categories',
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Groups', 'gravityformsmailchimp' ),
							esc_html__( 'When one or more groups are enabled, users will be assigned to the groups in addition to being subscribed to the MailChimp list. When disabled, users will not be assigned to groups.', 'gravityformsmailchimp' )
						),
					),
					array(
						'name'    => 'options',
						'label'   => esc_html__( 'Options', 'gravityformsmailchimp' ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'name'          => 'double_optin',
								'label'         => esc_html__( 'Double Opt-In', 'gravityformsmailchimp' ),
								'default_value' => 1,
								'onclick'       => 'if(this.checked){jQuery("#mailchimp_doubleoptin_warning").hide();} else{jQuery("#mailchimp_doubleoptin_warning").show();}',
								'tooltip'       => sprintf(
									'<h6>%s</h6>%s',
									esc_html__( 'Double Opt-In', 'gravityformsmailchimp' ),
									esc_html__( 'When the double opt-in option is enabled, MailChimp will send a confirmation email to the user and will only add them to your MailChimp list upon confirmation.', 'gravityformsmailchimp' )
								),
							),
							array(
								'name'  => 'markAsVIP',
								'label' => esc_html__( 'Mark subscriber as VIP', 'gravityformsmailchimp' ),
							),
						),
					),
					array(
						'name'  => 'tags',
						'type'  => 'text',
						'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'label' => esc_html__( 'Tags', 'gravityformsmailchimp' ),
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Tags', 'gravityformsmailchimp' ),
							esc_html__( 'Associate tags to your MailChimp contacts with a comma separated list. (e.g. new lead, Gravity Forms, web source)', 'gravityformsmailchimp' )
						),
					),
					array(
						'name'  => 'note',
						'type'  => 'textarea',
						'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'label' => esc_html__( 'Note', 'gravityformsmailchimp' ),
					),
					array(
						'name'    => 'optinCondition',
						'label'   => esc_html__( 'Conditional Logic', 'gravityformsmailchimp' ),
						'type'    => 'feed_condition',
						'tooltip' => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformsmailchimp' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to MailChimp when the conditions are met. When disabled all form submissions will be exported.', 'gravityformsmailchimp' )
						),
					),
					array( 'type' => 'save' ),
				),
			),
		);

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

		false;

	}

}
