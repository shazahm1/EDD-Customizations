<?php
/*
Plugin Name: Easy Digital Downloads - Customizations
Plugin URI: http://easydigitaldownloads.com/
Description: My EDD Customizations
Version: 1.0
Author: Steven A Zahm
Author URI:  http://connections-pro.com
Contributors: shazahm1
*/

class EDD_SAZ_Customizations {

	private static $instance;

	/**
	 * Get active object instance
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {

		if ( ! self::$instance )
			self::$instance = new EDD_SAZ_Customizations();

		return self::$instance;
	}

	/**
	 * Class constructor.  Includes constants, includes and init method.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->init();

	}

	/**
	 * Run action and filter hooks.
	 *
	 * @since 1.0
	 *
	 * @access private
	 * @return void
	 */
	private function init() {

		// Enqueue the CSS
		add_action('wp_enqueue_scripts', array( __CLASS__, 'loadStyles' ) );

		// Add a new purchase button shortcode
		// add_shortcode( 'purchase_button', array('EDD_SAZ_Customizations', 'purchaseButton') );

		// Override the EDD purchase link output using a filter.
		// Filter is in includes/template-functions.php
		add_filter( 'edd_purchase_download_form', array( __CLASS__, 'overrideDownloadPurchaseForm' ), 10, 2 );

		// Add custom button colors
		add_filter( 'edd_button_colors', array( __CLASS__, 'buttonColors' ) );

		add_filter( 'widget_title', array( __CLASS__, 'widgetTitle' ), 10, 3 );

		// Change the date format on the "Purchase History" admin page.
		// add_filter( 'edd_payments_page_date_format', array( __CLASS__, 'purchaseHistoryDateFormat' ) );

		// Remove the payment icons from under the checkout purchase form.
		// remove_action('edd_before_purchase_form', 'edd_show_payment_icons');
		// Move it to under the "Purchase" button.
		// add_action('edd_purchase_form_after_submit', 'edd_show_payment_icons');
	}

	/**
	 * Enqueue the CSS.
	 *
	 * @access public
	 * @version 1.0
	 * @since 1.0
	 * @return void
	 */
	public static function loadStyles() {

		wp_enqueue_style( 'edd-customizations-css', plugins_url('css/edd-custom-user.css', __FILE__) , '' , '1.1' );

	}

	/**
	 * Add custom button colors.
	 *
	 * @access public
	 * @version 1.0
	 * @since 1.0
	 * @return string
	 */
	public static function buttonColors($colors) {


		$colors['royalblue'] = array(
			'label' => 'Royal Blue',
			'hex'   => '#428bca',
		);

		$colors['royalgreen'] = array(
			'label' => 'Royal Green',
			'hex'   => '#5cb85c',
		);

		$colors['royalred'] = array(
			'label' => 'Royal Red',
			'hex'   => '#d9534f',
		);

		ksort( $colors );

		return $colors;
	}

	/**
	 * Change the date format of the date on the "Purchase History" admin page.
	 *
	 * @access public
	 * @version 1.0
	 * @since 1.0
	 * @return string
	 */
	public static function purchaseHistoryDateFormat($format) {

		return 'M j, Y g:ia';
	}

	/**
	 * Override the default style of the purchase_link button and link.
	 *
	 * @access public
	 * @version 1.0
	 * @since 1.0
	 * @return string
	 */
	public static function overrideDownloadPurchaseForm($purchase_form, $args) {

		if ( $args[ 'style' ] === 'text' ) return $purchase_form;

		return EDD_SAZ_Customizations::purchaseButton( $args );
	}

	/**
	 * Callback for the `widget_title` filter.
	 *
	 * Checks to see if the widget is a download detail widget and if it is
	 * this will check if the price is `0` and if it is, empty the widget title.
	 *
	 * @param  string $title  The widget title
	 * @param  array  $widget The widget instance array.
	 * @param  string $id     The widget ID.
	 *
	 * @return string         The widget title.
	 */
	public static function widgetTitle( $title, $widget = array(), $id = '' ) {

		// Checkng for this is the closest that can be done to ensure targetting only the download deatils widget.
		if ( isset( $widget['purchase_button'] ) && isset( $widget['download_id'] ) ) {

			// set correct download ID
			if ( 'current' == $widget['download_id'] && is_singular( 'download' ) ) {
				$download_id = get_the_ID();
			} else {
				$download_id = absint( $widget['download_id'] );
			}

			$download = new EDD_Download( $download_id );

			if ( 0 == $download->price ) {
				$title = '';
			}

		}

		return $title;
	}

	/**
	* Purchase Link Shortcode
	*
	* Retrieves a download and displays the purchase form.
	*
	* @access      private
	* @since       1.0
	* @return      string
	*/
	public static function purchaseButton( $args ) {

		global $post, $edd_displayed_form_ids;

		$purchase_page = edd_get_option( 'purchase_page', false );
		if ( ! $purchase_page || $purchase_page == 0 ) {
			edd_set_error( 'set_checkout', sprintf( __( 'No checkout page has been configured. Visit <a href="%s">Settings</a> to set one.', 'edd' ), admin_url( 'edit.php?post_type=download&page=edd-settings' ) ) );
			edd_print_errors();
			return false;
		}

		$post_id = is_object( $post ) ? $post->ID : 0;
		$button_behavior = edd_get_download_button_behavior( $post_id );

		/**
		 * Not needed this is handled in the parent function.
		 */
		// $defaults = apply_filters( 'edd_purchase_link_defaults', array(
		// 	'download_id' => $post_id,
		// 	'price'       => (bool) true,
		// 	'price_id'    => isset( $args['price_id'] ) ? $args['price_id'] : false,
		// 	'direct'      => $button_behavior == 'direct' ? true : false,
		// 	'text'        => $button_behavior == 'direct' ? edd_get_option( 'buy_now_text', __( 'Buy Now', 'edd' ) ) : edd_get_option( 'add_to_cart_text', __( 'Purchase', 'edd' ) ),
		// 	'style'       => edd_get_option( 'button_style', 'button' ),
		// 	'color'       => edd_get_option( 'checkout_color', 'blue' ),
		// 	'class'       => 'edd_button_shell'
		// ) );

		// $args = wp_parse_args( $args, $defaults );

		// Override the stright_to_gateway if the shop doesn't support it
		if ( ! edd_shop_supports_buy_now() ) {
			$args['direct'] = false;
		}

		$download = new EDD_Download( $args['download_id'] );

		if( empty( $download->ID ) ) {
			return false;
		}

		if( 'publish' !== $download->post_status && ! current_user_can( 'edit_product', $download->ID ) ) {
			return false; // Product not published or user doesn't have permission to view drafts
		}

		// Override color if color == inherit
		$args['color'] = ( $args['color'] == 'inherit' ) ? '' : $args['color'];

		$variable_pricing = edd_has_variable_prices( $args['download_id'] );
		$data_variable    = $variable_pricing ? ' data-variable-price=yes' : 'data-variable-price=no';
		$type             = edd_single_price_option_mode( $args['download_id'] ) ? 'data-price-mode=multi' : 'data-price-mode=single';

		$options          = array();
		$variable_pricing = $download->has_variable_prices();
		$data_variable    = $variable_pricing ? ' data-variable-price="yes"' : 'data-variable-price="no"';
		$type             = $download->is_single_price_mode() ? 'data-price-mode=multi' : 'data-price-mode=single';

		$show_price       = $args['price'] && $args['price'] !== 'no';
		$data_price_value = 0;

		if ( $variable_pricing && false !== $args['price_id'] ) {

			$price_id            = $args['price_id'];
			$prices              = $download->prices;
			$options['price_id'] = $args['price_id'];
			$found_price         = isset( $prices[$price_id] ) ? $prices[$price_id]['amount'] : false;

			$data_price_value    = $found_price;

			if ( $show_price ) {
				$price = $found_price;
			}

		} elseif ( ! $variable_pricing ) {

			$data_price_value = $download->price;

			if ( $show_price ) {
				$price = $download->price;
			}

		}

		/**
		 * Do not output the button of the price is free.
		 */
		if ( isset( $price ) && false !== $price ) {
			if ( 0 == $price ) {
				return '';
			}
		}

		$data_price  = 'data-price="' . $data_price_value . '"';

		/**
		 * Not needed this is handled in the parent function.
		 */
		// $button_text = ! empty( $args['text'] ) ? '&nbsp;&ndash;&nbsp;' . $args['text'] : '';

		// if ( isset( $price ) && false !== $price ) {

		// 	if ( 0 == $price ) {
		// 		$args['text'] = __( 'Free', 'edd' ) . $button_text;
		// 	} else {
		// 		$args['text'] = edd_currency_filter( edd_format_amount( $price ) ) . $button_text;
		// 	}

		// }

		if ( edd_item_in_cart( $download->ID, $options ) && ( ! $variable_pricing || ! $download->is_single_price_mode() ) ) {
			$button_display   = 'style="display:none;"';
			$checkout_display = '';
		} else {
			$button_display   = '';
			$checkout_display = 'style="display:none;"';
		}

		// Collect any form IDs we've displayed already so we can avoid duplicate IDs
		if ( isset( $edd_displayed_form_ids[ $download->ID ] ) ) {
			$edd_displayed_form_ids[ $download->ID ]++;
		} else {
			$edd_displayed_form_ids[ $download->ID ] = 1;
		}

		$form_id = ! empty( $args['form_id'] ) ? $args['form_id'] : 'edd_purchase_' . $download->ID;

		// If we've already generated a form ID for this download ID, apped -#
		if ( $edd_displayed_form_ids[ $download->ID ] > 1 ) {
			$form_id .= '-' . $edd_displayed_form_ids[ $download->ID ];
		}

		$args = apply_filters( 'edd_purchase_link_args', $args );

		ob_start();
	?>
		<!--dynamic-cached-content-->
		<form id="<?php echo $form_id; ?>" class="edd_download_purchase_form edd_purchase_<?php echo absint( $download->ID ); ?>" method="post">

			<?php do_action( 'edd_purchase_link_top', $download->ID, $args ); ?>

			<div class="edd_purchase_submit_wrapper">

				<?php

					printf(
						'<span class="edd_button_shell edd_color_%1$s">',
						$args['color']
					);

					 if ( edd_is_ajax_enabled() ) {
						printf(
							'<a href="#" class="edd-add-to-cart %1$s" data-nonce="%7$s" data-action="edd_add_to_cart" data-download-id="%3$s" %4$s %5$s %6$s><span class="edd-add-to-cart-label">%2$s</span> <span class="edd-loading"><i class="edd-icon-spinner edd-icon-spin"></i></span></a>',
							implode( ' ', array( trim( $args['class'] ) ) ),
							esc_attr( $args['text'] ),
							esc_attr( $download->ID ),
							$data_variable,
							$type,
							$button_display,
							wp_create_nonce( 'edd-add-to-cart-' . $download->ID )
						);
					}

					printf(
						'<input type="submit" class="edd-add-to-cart edd-no-js %1$s" name="edd_purchase_download" value="%2$s" data-action="edd_add_to_cart" data-download-id="%3$s" %4$s %5$s %6$s/>',
						implode( ' ', array( trim( $args['class'] ) ) ),
						esc_attr( $args['text'] ),
						esc_attr( $download->ID ),
						$data_variable,
						$type,
						$button_display
					);

					printf(
						'<a href="%1$s" class="edd_go_to_checkout %2$s" %3$s>' . __( 'Checkout', 'edd' ) . '</a>',
						esc_url( edd_get_checkout_uri() ),
						implode( ' ', array( $args['style'] ) ),
						$checkout_display
					);

					echo '</span><!--end .edd_button_shell-->';
				?>

				<?php if ( edd_is_ajax_enabled() ) : ?>
					<span class="edd-cart-ajax-alert">
						<div><span class="edd-cart-added-alert" style="display: none;">
							<?php printf(
									__( '<i class="edd-icon-ok"></i> Item added to cart.', 'edd' ),
									'<a href="' . esc_url( edd_get_checkout_uri() ) . '" title="' . __( 'Go to Checkout', 'edd' ) . '">',
									'</a>'
								);
							?>
						</span></div>
					</span>
				<?php endif; ?>

				<?php if( ! $download->is_free( $args['price_id'] ) ): ?>
					<?php if ( edd_display_tax_rate() && edd_prices_include_tax() ) {
						echo '<span class="edd_purchase_tax_rate">' . sprintf( __( 'Includes %1$s&#37; tax', 'edd' ), edd_get_tax_rate() * 100 ) . '</span>';
					} elseif ( edd_display_tax_rate() && ! edd_prices_include_tax() ) {
						echo '<span class="edd_purchase_tax_rate">' . sprintf( __( 'Excluding %1$s&#37; tax', 'edd' ), edd_get_tax_rate() * 100 ) . '</span>';
					} ?>
				<?php endif; ?>

			</div><!--end .edd_purchase_submit_wrapper-->

			<input type="hidden" name="download_id" value="<?php echo esc_attr( $download->ID ); ?>">
			<?php if ( $variable_pricing && isset( $price_id ) && isset( $prices[$price_id] ) ): ?>
				<input type="hidden" name="edd_options[price_id][]" id="edd_price_option_<?php echo $download->ID; ?>_1" class="edd_price_option_<?php echo $download->ID; ?>" value="<?php echo $price_id; ?>">
			<?php endif; ?>
			<?php if( ! empty( $args['direct'] ) && ! $download->is_free( $args['price_id'] ) ) { ?>
				<input type="hidden" name="edd_action" class="edd_action_input" value="straight_to_gateway">
			<?php } else { ?>
				<input type="hidden" name="edd_action" class="edd_action_input" value="add_to_cart">
			<?php } ?>

			<?php if( apply_filters( 'edd_download_redirect_to_checkout', edd_straight_to_checkout(), $download->ID, $args ) ) : ?>
				<input type="hidden" name="edd_redirect_to_checkout" id="edd_redirect_to_checkout" value="1">
			<?php endif; ?>

			<?php do_action( 'edd_purchase_link_end', $download->ID, $args ); ?>

		</form><!--end #<?php echo esc_attr( $form_id ); ?>-->
		<!--/dynamic-cached-content-->
	<?php
		$purchase_form = ob_get_clean();

		return $purchase_form;
	}

}

new EDD_SAZ_Customizations();
