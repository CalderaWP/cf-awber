<?php
/**
 * Class CF_Aweber_Client
 *
 * @package   cf_aweber
 * @author    Josh Pollock for CalderaWP LLC (email : Josh@CalderaWP.com)
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP LLC
 */
class CF_Aweber_Client extends  CF_Aweber_Base {
	/**
	 * Aweber application object
	 *
	 * @since 0.1.0
	 *
	 * @var \AWeberAPI
	 */
	public $application;

	/**
	 * Aweber account object
	 *
	 * @since 0.1.0
	 *
	 * @var  AWeberCollection
	 */
	public $account;

	/**
	 * Construct object
	 *
	 * @since 0.1.0
	 *
	 * @param \CF_Aweber_Credentials $credentials
	 */
	function __construct( CF_Aweber_Credentials $credentials ) {

		$this->consumerKey = $credentials->consumerKey;
		$this->consumerSecret = $credentials->consumerSecret;
		$this->accessKey = $credentials->accessKey;
		$this->accessSecret = $credentials->accessSecret;

		$this->application = new AWeberAPI($this->consumerKey, $this->consumerSecret);
		
	}

	/**
	 * Set account
	 *
	 * @since 0.1.0
	 */
	public function set_account( $accesKey = '', $accountSecret = '' ){
		try {
			$this->account = $this->application->getAccount($this->accessKey, $this->accessSecret );
		} catch ( AWeberAPIException $exc ) {
			return $exc->message;
		}

	}

	public function is_loaded(){
		return is_object(  $this->account  ) && is_object(  $this->application  );
	}

	/**
	 * List all lists of this account
	 *
	 * @since 0.1.0
	 *
	 * @return array|void
	 */
	function listLists(){
		if( empty( $this->account ) ){
			$this->set_account();
		}

		if( empty( $this->account ) ){
			return;
		}

		try {
			$url = $this->account->data[ 'lists_collection_link' ];
			$response = $this->application->adapter->request('GET', $url);

			if( is_array( $response ) && isset( $response[ 'entries' ] ) ){

				return $response[ 'entries' ];
			}


		} catch(AWeberAPIException $exc) {
			return $exc->message;
		}


	}


	/**
	 * Get a all list from this account
	 *
	 * @since 0.1.0
	 *
	 * @return array|void
	 */
	function findList($listName ) {
		try {
			$foundLists = $this->account->lists->find(array('name' => $listName));
			//must pass an associative array to the find method

			return $foundLists[0];
		}catch(AWeberAPIException $exc) {
			return $exc->message;
		}
	}

	/**
	 * Find a subscriber to to any list in this account
	 *
	 * @since 0.1.0
	 *
	 * @return array|void
	 */
	function findSubscriber($email) {
		try {
			$foundSubscribers = $this->account->findSubscribers(array('email' => $email));
			//must pass an associative array to the find method

			return $foundSubscribers[0];
		}catch(AWeberAPIException $exc) {
			return $exc->message;
		}
	}

	/**
	 * Add a subscriber to to any list in this account
	 *
	 * @since 0.1.0
	 *
	 * @return array|void
	 */
	function addSubscriber( $subscriber, $list ) {
		try {

			$listUrl = "/accounts/{$this->account->id}/lists/{$list}";
			$list    = $this->account->loadFromUrl( $listUrl );

			$newSubscriber = $list->subscribers->create( $subscriber );
			if ( is_object( $newSubscriber ) && property_exists( $newSubscriber, 'data' ) && is_array( $newSubscriber->data ) ) {
				return $newSubscriber->data;
			}
		} catch(AWeberAPIException $exc) {
			return $exc->message;
		}

	}
}
