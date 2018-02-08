<?php
/**
 * Processor config UI for Aweber for Caldera Forms
 *
 * @package   cf_aweber
 * @author    Josh Pollock Josh Pollock for CalderaWP LLC (email : Josh@CalderaWP.com)
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP LLC for CalderaWP LLC
 */
$credentials = cf_aweber_main_credentials();
$credentials->set_from_save();
$credentials_set = $credentials->all_set();

?>

<div id="cf-aweber-setup" <?php if( false == $credentials_set ) : ?> style="visibility: hidden;display: none;" aria-hidden="true" <?php endif; ?> >
	<?php
		$config_fields              = Caldera_Forms_Processor_UI::config_fields( cf_aweber_fields() );
		echo $config_fields;
	?>
</div>
<?php if( false == $credentials_set ) : ?>
<div id="cf-aweber-auth">
	<h3>
		<?php esc_html_e( 'Authorize Caldera Forms To Connect To Aweber', 'cf-aweber' ); ?>
	</h3>

		<div class="caldera-config-group">
			<label for="cf-aweber-auth-url" id="cf-aweber-auth-url-label">
				<?php esc_html_e('Get Authorization', 'cf-aweber'); ?>
			</label>
			<a href="<?php echo esc_url( cf_aweber_get_auth_url() ); ?>" id="cf-aweber-auth-url" target="_blank" class="button" aria-describedby="cf-aweber-auth-url-desc" title="<?php esc_attr_e( 'Click to get authorization code from Aweber', 'cf-aweber');?>">
				<?php esc_html_e('Get Authorization Code', 'cf-aweber' ); ?>
			</a>
			<p class="description" id="cf-aweber-auth-url-desc">
				<?php esc_html_e( 'Click this button to login to your Aweber account and get an authorization code. You will need to paste the code in the input below.', 'cf-aweber' ); ?>
			</p>
		</div>



		<div class="caldera-config-group">
			<label for="cf-aweber-code" id="cf-aweber-code-label">
				<?php esc_html_e('Authorization Code', 'cf-aweber'); ?>
			</label>
			<div class="caldera-config-field">
				<input type="text" class="block-input field-config" id="cf-aweber-code" aria-describedby="cf-aweber-code-desc" aria-labelledby="cf-aweber-code-label">
			</div>
			<p class="description" id="cf-aweber-code-desc">
				<?php esc_html_e( 'Paste your authorization code here and click the "Save Authorization Button".', 'cf-aweber' ); ?>
			</p>
		</div>

		<div class="caldera-config-group">
			<label for="cf-aweber-auth-save" id="cf-aweber-auth-save-label">
				<?php esc_html_e('Save Authorization', 'cf-aweber'); ?>
			</label>
			<div class="caldera-config-field">
				<button id="cf-aweber-auth-save" class="button button"  aria-labelledby="cf-aweber-auth-save-label">
					<?php esc_html_e( 'Save Authorization', 'cf-aweber'); ?>
				</button>
			</div>
			<p class="description" id="cf-aweber-code-desc">
				<?php esc_html_e( 'Once you have entered your authorization code, click here.', 'cf-aweber' ); ?>
				<span id="cf-aweber-save-auth-spinner" class="spinner" aria-hidden="true"></span>
			</p>
		</div>

</div>

<?php endif; ?>

<script type="text/javascript">
	jQuery(document).ready(function( $ ) {


		var pId = "{{_id}}";
		var tag, hid, sel;
		tag = 'config[processors][' + pId + '][config][cf-aweber-list]';

		sel = document.getElementsByName( tag );
		var $selectList = $( sel[0] );

		tag = 'config[processors][' + pId + '][config][cf-aweber-list-hidden]';
		var hid = document.getElementsByName( tag );
		var $hiddenList = $( hid[0] );
		if( '' != $hiddenList.val() ){
			$selectList.val( $hiddenList.val() );
		}

		$selectList.on( 'change', function(){
			$hiddenList.val( $selectList.val() );
		});

		$( "#cf-aweber-auth-save" ).on( 'click', function(e) {
			e.preventDefault();
			var spinnerEL = document.getElementById( 'cf-aweber-save-auth-spinner' );
			$( spinnerEL ).css( 'visibility', 'visible' ).attr( 'aria-hidden', 'false' ).show();
			var data = {
				action: 'cf_aweber_auth_save',
				code: $( '#cf-aweber-code' ).val(),
				nonce: "<?php echo wp_create_nonce(); ?>"
			};
			var xhr = $.post( ajaxurl, data );
			xhr.done(function() {
				$( spinnerEL ).css( 'visibility', 'hidden' ).attr( 'aria-hidden', 'true' ).hide();
				$( '#cf-aweber-auth' ).slideUp( "slow", function() {
					$( this ).attr( 'aria-hidden', 'true' ).css( 'visibility', 'hidden' );
					$( '#cf-aweber-setup' ).attr( 'aria-hidden', 'false' ).css( 'visibility', 'visible' ).show();
                            getLists();
				});
			});
			xhr.error(function(r) {
				alert( r.responseJSON.data.message );
				$( spinnerEL ).css( 'visibility', 'hidden' ).attr( 'aria-hidden', 'true' ).hide();
			});
		});

		$( '#cf-aweber-refresh-lists' ).on( 'click', function(e){
			e.preventDefault();
			getLists();
		});

		function getLists(){

			var spinnerEL = document.getElementById( 'cf-aweber-get-list-spinner' );
			var data = {
				action: 'cf_aweber_get_lists',
				nonce: "<?php echo wp_create_nonce(); ?>"
			};
			$( spinnerEL ).css( 'visibility', 'visible' ).attr( 'aria-hidden', 'false' ).show();

			var xhr = $.get( ajaxurl, data );
			xhr.done(function( r ) {
				if( 'object' == typeof  r ){


					$selectList.html( r.data.input );
				}
				$( spinnerEL ).css( 'visibility', 'hidden' ).attr( 'aria-hidden', 'true' ).hide();
			});
			xhr.error(function(r) {
				alert( r.statusText );
				$( spinnerEL ).css( 'visibility', 'hidden' ).attr( 'aria-hidden', 'true' ).hide();
			});
		}

	});

</script>
