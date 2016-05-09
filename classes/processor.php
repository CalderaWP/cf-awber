<?php

/**
 * Class CF_Awber_Processor
 *
 * @package   cf_awber
 * @author    Josh Pollock for CalderaWP LLC (email : Josh@CalderaWP.com)
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP LLC
 */
class CF_Awber_Processor extends Caldera_Forms_Processor_Newsletter {

	/**
	 * Set Awber client in the client property
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function set_up_awber_client(){
		CF_Awber_Credentials::get_instance()->set_from_save();

		if( CF_Awber_Credentials::get_instance()->all_set() ){
			$this->client = new CF_Awber_Client(CF_Awber_Credentials::get_instance() );
			return true;
		}

		return false;

	}

	/**
	 * Validate the process if possible, and if not return errors.
	 *
	 * @since 0.1.0
	 *
	 * @param array $config Processor config
	 * @param array $form Form config
	 * @param string $proccesid Unique ID for this instance of the processor
	 *
	 * @return array Return if errors, do not return if not
	 */
	public function pre_processor( array $config, array $form, $proccesid ){
		$client_set = $this->set_up_awber_client();
		if( ! $client_set ){
			return array(
				'type' => 'error',
				'note' => esc_html__( 'Aweber is not authorized', 'cf-aweber' )
			);

		}

		$this->set_data_object_initial( $config, $form );

		$errors = $this->data_object->get_errors();
		if ( ! empty( $errors ) ) {
			return $errors;

		}
		$this->setup_transata( $proccesid );


		$subscriber_data = $this->subscriber_data();
		$subscribed = $this->subscribe( $subscriber_data, $this->data_object->get_value( 'list' ) );

		if( is_array( $subscribed ) ){
			Caldera_Forms::set_submission_meta('awber', $subscribed, $form, $proccesid );
		}



	}

	protected function subscriber_data(){
		$subscriber_data = array();
		foreach( $this->subscriber_fields() as $field ){
			if( 'ip_address' == $field ){
				$subscriber_data[ $field ] = caldera_forms_get_ip();
			}else{
				$subscriber_data[ $field ] = $this->data_object->get_value( $field );
			}
		}

		return $subscriber_data;
	}

	protected function subscriber_fields(){
		$fields = array(
			'email',
			'name',
			'ad_tracking',
			'ip_address',
			'tags',
			'misc_notes'
		);

		return apply_filters( 'cf_awber_subscriber_fields', $fields );
	}

	/**
	 * Add a subscriber to a list
	 *
	 * @since 1.3.6
	 *
	 * @param array $subscriber_data Data for new subscriber
	 * @param string $list_name Name of list
	 *
	 * @return mixed
	 */
	public function subscribe( array $subscriber_data, $list_name ){
		return $this->client->addSubscriber( $subscriber_data, $list_name );
	}

	/**
	 * If validate do processing
	 *
	 * @since 0.1.0
	 *
	 * @param array $config Processor config
	 * @param array $form Form config
	 * @param string $proccesid Process ID
	 *
	 * @return array Return meta data to save in entry
	 */
	public function processor( array $config, array $form, $proccesid ){

	}

	/**
	 * Get fields for processor
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function fields(){
		return cf_awber_fields();
	}
	
	
}