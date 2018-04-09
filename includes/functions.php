<?php
/**
 * Functions for Aweber for Caldera Forms
 *
 * @package   cf_aweber
 * @author    Josh Pollock for CalderaWP LLC (email : Josh@CalderaWP.com)
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP LLC
 */

/**
 * Load processor
 *
 * @since 0.1.0
 *
 * @uses "caldera_forms_pre_load_processors" action
 */
function cf_aweber_load(){
	if( ! class_exists( 'Caldera_Forms_Processor_Newsletter' ) ){
		return;
	}
	
	include_once CF_AWEBER_PATH . 'vendor/autoload.php';

	cf_aweber_register_autload();
	new CF_Aweber_Processor( cf_aweber_config(), cf_aweber_fields(), 'cf-aweber' );

}

/**
 * Aweber for Caldera Forms config
 *
 * @since 0.1.0
 *
 * @return array	Processor configuration
 */
function cf_aweber_config(){

	return array(
		"name"				=>	__( 'Aweber for Caldera Forms', 'cf-aweber'),
		"description"		=>	__( 'Aweber for Caldera Forms', 'cf-aweber'),
		"icon"				=>	CF_AWEBER_URL . "/icon.png",
		"author"			=>	'Josh Pollock for CalderaWP LLC',
		"author_url"		=>	'https://CalderaWP.com',
		"template"			=>	CF_AWEBER_PATH . "includes/config.php",

	);


}

/**
 * Get all lists for current account
 *
 * @since 0.1.0
 *
 * @param bool $skip_cache Optional. If false, results are cached. If true, caching is skipped. False is the default.
 *
 * @return array|void
 */
function cf_aweber_lists( $skip_cache = false ){
	if ( ! $skip_cache ) {
		if ( false != ( $lists = get_transient( 'cf_aweber_lists' ) ) ) {
			return $lists;
		}

	}

	$credentials = cf_aweber_main_credentials();
	$set = $credentials->all_set();
	if( ! $set ){
		$credentials->set_from_save();
	}

	$set = $credentials->all_set();
	if( ! $set ){
		$lists = array(
			0 => sprintf( '-- %s --', __( 'Checking Aweber authorization', 'cf-aweber' ) )
		);
		return $lists;
	}

	
	$client = new CF_Aweber_Client( $credentials );
	$_lists = $client->listLists();
	if( ! empty( $_lists ) ){
		$ids = wp_list_pluck( $_lists, 'id' );
		$names = wp_list_pluck( $_lists, 'name' );
		if( is_array( $names ) && is_array( $ids ) ){
			$lists = array_combine( wp_list_pluck( $_lists, 'id' ), wp_list_pluck( $_lists, 'name' ) );
			if ( ! $skip_cache ) {
				set_transient( 'cf_aweber_lists', $lists, 599 );
			}

		}

	}

	if( empty( $lists ) ){
		$lists = array(
			0 => sprintf( '-- %s --', __( 'No list found', 'cf-aweber' ) )
		);
	}

	return $lists;

}

/**
 * Config for lists field
 *
 * @since 0.1.0
 *
 * @return array
 */
function cf_aweber_lists_field_config(){
	return array(
		'id'       => 'cf-aweber-list',
		'label'    => __( 'List', 'cf-aweber' ),
		'desc'     => __( 'List to add subscriber to.', 'cf-aweber' ),
		'type'     => 'dropdown',
		'options' => cf_aweber_lists(),
		'required' => true,
		'extra_classes' => 'block-input',
		'magic' => false
	);
}

/**
 * Get UI fileds config
 *
 * @since 0.1.0
 *
 * @return array
 */
function cf_aweber_fields(){

	$fields = array(
		cf_aweber_lists_field_config(),
		array(
			'id'       => 'cf-aweber-list-hidden',
			'type'     => 'hidden',
			'required' => true,
			'magic' => false,
			'label' => __( 'List ID', 'cf-aweber' )
		),
		array(
			'id'       => 'cf-aweber-email',
			'label'    => __( 'Email Address', 'cf-aweber' ),
			'desc'     => __( 'Subscriber email address.', 'cf-aweber' ),
			'type'     => 'advanced',
			'allow_types' => array( 'email' ),
			'required' => true,
			'magic' => false
		),
		array(
			'id'            => 'cf-aweber-name',
			'label'         => __( 'Name', 'cf-aweber' ),
			'type'          => 'text',
			'desc'          => __( 'Subscriber name.', 'cf-aweber' ),
			'required'      => true,
			'allowed_types' => 'email',
		),
		array(
			'id'    => 'cf-aweber-tags',
			'label' => __( 'Tags', 'cf-aweber' ),
			'desc'  => __( 'Comma separated list of tags.', 'cf-aweber' ),
			'type'  => 'text',
			'required' => false,
		),
		array(
			'id'    => 'cf-aweber-misc_notes',
			'label' => __( 'Miscellaneous notes', 'cf-aweber' ),
			'type'  => 'text',
			'required' => false,
		),
		array(
			'id'   => 'cf-aweber-add_tracking',
			'label' => __( 'Add Tracking', 'cf-aweber' ),
			'type'  => 'text',
			'desc' => sprintf( '<a href="%s" target="_blank" title="%s">%s</a> %s.',
				'https://help.aweber.com/hc/en-us/articles/204028836-What-Is-Ad-Tracking-',
				esc_html__( 'Aweber ad tracking documentation', 'cf-aweber' ),
				esc_html__( 'Value for ad tracking field in Aweber.', 'cf-aweber' ),
				esc_html__( 'To pass UTM tags use {get:*} magic tags, such as {get:utm_campaign}', 'cf-aweber' )
			),
			'required' => false,
			'desc_escaped' => true
		)
	);

	/**
	 * Filter admin UI field configs
	 *
	 * @since 0.1.0
	 *
	 * @param array $fields The fields
	 */
	return apply_filters( 'cf_aweber_fields', $fields );
}



/**
 * Initializes the licensing system
 *
 * @uses "admin_init" action
 *
 * @since 0.1.0
 */
function cf_aweber_init_license(){
	if ( ! function_exists( 'caldera_warnings_dismissible_notice' ) ) {
		include_once CF_AWEBER_PATH . 'vendor/autoload.php';
	}
	$plugin = array(
		'name'		=>	'Aweber for Caldera Forms',
		'slug'		=>	'aweber-for-caldera-forms',
		'url'		=>	'https://calderawp.com/',
		'version'	=>	CF_AWEBER_VER,
		'key_store'	=>  'CF_AWEBER_license',
		'file'		=>  CF_AWEBER_CORE,
	);

	new \calderawp\licensing_helper\licensing( $plugin );

}


/**
 * Add our example form
 *
 * @uses "caldera_forms_get_form_templates"
 *
 * @since 0.1.0
 *
 * @param array $forms Example forms.
 *
 * @return array
 */
function cf_aweber_example_form( $forms ) {
	$forms['cf_aweber']	= array(
		'name'	=>	__( 'Contact form with Aweber signup.', 'cf-aweber' ),
		'template'	=>	include CF_AWEBER_PATH . 'includes/templates/example.php'
	);

	return $forms;

}



/**
 * Get the URL for login and get auth code
 *
 * @since 0.1.0
 *
 * @return string
 */
function cf_aweber_get_auth_url(){
	$appID = CF_AWEBER_APP_ID;
	return "https://auth.aweber.com/1.0/oauth/authorize_app/{$appID}";
}

/**
 * Save auth via AJAX
 *
 * @uses "wp_ajax_cf_aweber_auth_save" action
 *
 * @since 0.1.0
 */
function cf_aweber_auth_save_ajax_cb(){
	if( current_user_can( Caldera_Forms::get_manage_cap( 'admin' ) ) && isset( $_POST[ 'code' ] ) && isset( $_POST[ 'nonce' ] ) && wp_verify_nonce( $_POST[ 'nonce' ] )  ){
		cf_aweber_register_autload();
		$code = trim( $_POST[ 'code' ] );
		$response = cf_aweber_convert_code( $code );
		if( ! $response ){
			status_header( 500 );
			wp_send_json_error( array( 'message' => esc_html__( 'Unknown error', 'cf-aweber' ) ) );
		}elseif( ! is_wp_error( $response ) ){
			wp_send_json_success();
		}else{
			status_header( 500 );
			wp_send_json_error( $response );
		}
	}

}

/**
 * Convert auth code to keys
 *
 * @since 0.1.0
 *
 * @param string $code Authroization code
 *
 * @return bool
 */
function cf_aweber_convert_code( $code ){

	include_once dirname( __FILE__  ) . '/aweber_api/aweber.php';
	cf_aweber_register_autload();
	try {
		$credentials = AWeberAPI::getDataFromAweberID($code);
	} catch(AWeberAPIException $exc) {
		return new WP_Error( 'cf-aweber-auth-fail', $exc->message );
	}



	if ( is_array( $credentials ) ) {
		$credentials_object = cf_aweber_main_credentials();
		$credentials_object->consumerKey    = $credentials[ 0 ];
		$credentials_object->consumerSecret = $credentials[ 1 ];
		$credentials_object->accessKey      = $credentials[ 2 ];
		$credentials_object->accessSecret   = $credentials[ 3 ];
		return $credentials_object->store();
	}
}


/**
 * Get aweber lists via AJAX
 *
 * @uses "wp_ajax_cf_aweber_get_lists" action
 *
 * @since 0.1.0
 */
function cf_aweber_get_lists_ajax_cb(){

	if( current_user_can( Caldera_Forms::get_manage_cap( 'admin' ) ) && isset( $_GET[ 'nonce' ] ) && wp_verify_nonce( $_GET[ 'nonce' ] ) ){
		cf_aweber_register_autload();
		$credentials = cf_aweber_main_credentials();
		$credentials->set_from_save();
		if( cf_aweber_main_credentials()->all_set() ){
			$client = new CF_Aweber_Client( $credentials );
			$lists = $client->listLists();
			if( is_array( $lists ) && ! empty( $lists ) ) {
				wp_send_json_success( array( 'input' => Caldera_Forms_Processor_UI::config_field( cf_aweber_lists_field_config() ) ) );
			}
		}

		wp_send_json_error();

	}
	status_header( 404 );
	die();

}

/**
 * Get main credentials object
 *
 * @since 0.1.0
 *
 * @return \CF_Aweber_Credentials
 */
function cf_aweber_main_credentials(){
	global $cf_aweber_main_cred;
	if( ! is_object( $cf_aweber_main_cred ) ){
		$cf_aweber_main_cred = new CF_Aweber_Credentials();
	}

	return $cf_aweber_main_cred;

}

/**
 * Add refresh lists button to list input
 *
 * @uses "caldera_forms_processor_ui_input_html" filter
 *
 * @param string $field Field HTML
 * @param string $type Field type
 * @param string $id ID attribute for field
 *
 * @return string
 */
function cf_aweber_processor_ui_input_html( $field, $type, $id ){
	if( 'cf-aweber-list' == $id ){
		$field .= sprintf( ' <button class="button" id="cf-aweber-refresh-lists">%s</button>', esc_html__( 'Refresh Lists', 'cf-aweber' ) );
		$field .= '<span id="cf-aweber-get-list-spinner" class="spinner" aria-hidden="true"></span>';
	}

	return $field;
}

/**
 * Load up classess
 *
 * @since 0.1.0
 */
function cf_aweber_register_autload(){
	include_once CF_AWEBER_PATH . 'includes/aweber_api/aweber_api.php';
	Caldera_Forms_Autoloader::add_root( 'CF_Aweber', CF_AWEBER_PATH . 'classes' );
}
