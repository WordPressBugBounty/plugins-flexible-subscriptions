<?php

/**
 * @var Subscription $subscription
 * @var \WPDesk\FlexibleSubscriptions\Subscription\SubscriptionLifecycleManager $lifecycle
 * @var array<string, mixed> $billing_fields
 * @var array<string, mixed> $shipping_fields
 * @var array<string, mixed> $payment_methods
 * @var array<string, array<string, mixed>> $payment_meta_fields
 */

use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;

defined( 'ABSPATH' ) || exit;

wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

$subscription_title = $subscription->get_data_store()->get_title( $subscription );
?>
<style type="text/css">
#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
</style>
<div class="panel-wrap woocommerce">
	<input name="post_title" type="hidden" value="<?php echo empty( $subscription_title ) ? esc_attr( get_post_type_object( $subscription->get_type() )->labels->singular_name ) : esc_attr( $subscription_title ); ?>" />
	<input name="post_status" type="hidden" value="<?php echo esc_attr( 'wc-' . $subscription->get_status() ); ?>" />
	<div id="order_data" class="panel">

		<h2>
			<?php
			// translators: placeholder is the ID of the subscription.
			printf( esc_html_x( 'Subscription #%s details', 'edit subscription header', 'flexible-subscriptions' ), esc_html( $subscription->get_order_number() ) );
			?>
		</h2>

		<div class="order_data_column_container">
			<div class="order_data_column">
				<h3><?php esc_html_e( 'General', 'flexible-subscriptions' ); ?></h3>

				<p class="form-field form-field-wide wc-customer-user">
					<!-- customer_user -->
					<label for="customer_user">
						<?php esc_html_e( 'Customer:', 'flexible-subscriptions' ); ?>
					<?php if ( $subscription->get_user_id() ) { ?>
						<?php
						$args = [
							'post_status'    => 'all',
							'post_type'      => Subscription::OBJECT_TYPE,
							'_customer_user' => absint( $subscription->get_user_id() ),
						];
						printf(
							'<a href="%s">%s</a>',
							esc_url( add_query_arg( $args, admin_url( 'edit.php' ) ) ),
							esc_html__( 'View other subscriptions &rarr;', 'flexible-subscriptions' )
						);
						?>
					<span>
						<a href="<?php echo esc_url( add_query_arg( 'user_id', $subscription->get_user_id(), admin_url( 'user-edit.php' ) ) ); ?>">
							<?php esc_html_e( 'Profile &rarr;', 'flexible-subscriptions' ); ?>
						</a>
					</span>
						<?php } ?>
					</label>
					<select name="customer_user"
						id="customer_user"
						class="wc-customer-search"
						placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'flexible-subscriptions' ); ?>"
					>
						<?php if ( $user = $subscription->get_user() ) { ?>
						<option value="<?php echo esc_attr( (string) $user->ID ); ?>">
							<?php echo esc_html( sprintf( '%s (#%d &ndash; %s)', $user->display_name, $user->ID, $user->user_email ) ); ?>
						</option>

						<?php } ?>
					</select>
				<!--- end customer user -->

				<p class="form-field form-field-wide">
					<label for="order_status"><?php esc_html_e( 'Subscription status:', 'flexible-subscriptions' ); ?></label>
					<select id="order_status" name="order_status" class="wc-enhanced-select">
						<?php
						$statuses = Status::get_statuses();
						foreach ( $statuses as $status => $status_name ) {
							if ( $lifecycle->can_transition( $subscription, $status ) || $subscription->has_status( $status ) ) {
								echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'wc-' . $subscription->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
							}
						}
						?>
					</select>
				</p>
				<?php
				$parent_order = $subscription->get_parent();
				if ( $parent_order ) {
					?>
				<p class="form-field form-field-wide">
					<?php echo esc_html__( 'Initial order: ', 'flexible-subscriptions' ); ?>
					<a href="<?php echo esc_url( $subscription->get_parent()->get_edit_order_url() ); ?>">
						<?php
						// translators: placeholder is an order number.
						printf( esc_html__( '#%1$s', 'flexible-subscriptions' ), esc_html( $parent_order->get_order_number() ) );
						?>
					</a>
				</p>
					<?php
				} else {
					?>
				<p class="form-field form-field-wide">
					<label for="parent-order-id"><?php esc_html_e( 'Initial order:', 'flexible-subscriptions' ); ?> </label>
					<select name="parent-order-id"
						id="parent-order-id"
						class="wc-enhanced-select"
						placeholder="<?php esc_attr_e( 'Select an order&hellip;', 'flexible-subscriptions' ); ?>"
					>
					</select>
				</p>
					<?php
				}
				do_action( 'woocommerce_admin_order_data_after_order_details', $subscription );
				?>

			</div>
			<div class="order_data_column">
				<h3>
					<?php esc_html_e( 'Billing', 'flexible-subscriptions' ); ?>
					<a href="#" class="edit_address"><?php esc_html_e( 'Edit', 'flexible-subscriptions' ); ?></a>
					<span>
						<a href="#" class="load_customer_billing" style="display:none;"><?php esc_html_e( 'Load billing address', 'flexible-subscriptions' ); ?></a>
					</span>
				</h3>
				<?php
				// Display values.
				echo '<div class="address">';

				if ( $subscription->get_formatted_billing_address() ) {
					echo '<p><strong>' . esc_html__( 'Address', 'flexible-subscriptions' ) . ':</strong>' . wp_kses( $subscription->get_formatted_billing_address(), [ 'br' => [] ] ) . '</p>';
				} else {
					echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'flexible-subscriptions' ) . ':</strong> ' . esc_html__( 'No billing address set.', 'flexible-subscriptions' ) . '</p>';
				}

				foreach ( $billing_fields as $key => $field ) {

					if ( isset( $field['show'] ) && false === $field['show'] ) {
						continue;
					}

					$function_name = 'get_billing_' . $key;

					if ( is_callable( [ $subscription, $function_name ] ) ) {
						// @phpstan-ignore method.dynamicName
						$field_value = $subscription->$function_name( 'edit' );
					} else {
						$field_value = $subscription->get_meta( '_billing_' . $key );
					}

					echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( make_clickable( esc_html( $field_value ) ) ) . '</p>';
				}

				echo '<p' . ( ( '' !== $subscription->get_payment_method() ) ? ' class="' . esc_attr( $subscription->get_payment_method() ) . '"' : '' ) . '><strong>' . esc_html__( 'Payment Method', 'flexible-subscriptions' ) . ':</strong>' . wp_kses_post( nl2br( $subscription->get_payment_method_to_display() ) ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

				// Display help tip.
				if ( '' !== $subscription->get_payment_method() && ! $subscription->is_manual() ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					// translators: %s: gateway ID.
					echo wc_help_tip( sprintf( _x( 'Gateway ID: [%s]', 'The gateway ID displayed on the Edit Subscriptions screen when editing payment method.', 'flexible-subscriptions' ), $subscription->get_payment_method() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				echo '</p>';

				echo '</div>';

				// Display form.
				echo '<div class="edit_address">';

				foreach ( $billing_fields as $key => $field ) {
					if ( ! isset( $field['type'] ) ) {
						$field['type'] = 'text';
					}
					if ( ! isset( $field['id'] ) ) {
						$field['id'] = '_billing_' . $key;
					}

					switch ( $field['type'] ) {
						case 'select':
							woocommerce_wp_select( $field, $subscription );
							break;
						default:
							if ( is_callable( [ $subscription, 'get_billing_' . $key ] ) ) {
								// @phpstan-ignore method.dynamicName
								$field['value'] = $subscription->{"get_billing_$key"}();
							} else {
								$field['value'] = $subscription->get_meta( $field['id'] );
							}

							woocommerce_wp_text_input( $field );
							break;
					}
				}

				echo '</div>';

				if ( ! empty( $payment_meta_fields[ $subscription->get_payment_method() ]['post_meta'] ) ) {
					echo '<div class="payment-meta-fields">';
					foreach ( $payment_meta_fields[ $subscription->get_payment_method() ]['post_meta'] as $meta_key => $meta ) {
						?>
						<p class="form-field form-field-wide">
							<label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $meta['label'] ); ?>:</label>
							<input type="text" class="short" name="<?php echo esc_attr( $meta_key ); ?>" id="<?php echo esc_attr( $meta_key ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" />
						</p>
						<?php
					}
					echo '</div>';
				}

				do_action( 'woocommerce_admin_order_data_after_billing_address', $subscription );

				// Display a link to the customer's add/change payment method screen.
				if ( $subscription->can_change_payment_method() ) {

					if ( $subscription->has_payment_gateway() ) {
						$link_text = __( 'Customer change payment method page &rarr;', 'flexible-subscriptions' );
					} else {
						$link_text = __( 'Customer add payment method page &rarr;', 'flexible-subscriptions' );
					}
				}
				?>
			</div>
			<div class="order_data_column">

				<h3>
					<?php esc_html_e( 'Shipping', 'flexible-subscriptions' ); ?>
					<a href="#" class="edit_address"><?php esc_html_e( 'Edit', 'flexible-subscriptions' ); ?></a>
					<span>
						<a href="#" class="load_customer_shipping" style="display:none;"><?php esc_html_e( 'Load shipping address', 'flexible-subscriptions' ); ?></a>
						<a href="#" class="billing-same-as-shipping" style="display:none;"><?php esc_html_e( 'Copy billing address', 'flexible-subscriptions' ); ?></a>
					</span>
				</h3>
				<?php
				// Display values.
				echo '<div class="address">';

				if ( $subscription->get_formatted_shipping_address() ) {
					echo '<p><strong>' . esc_html__( 'Address', 'flexible-subscriptions' ) . ':</strong>' . wp_kses( $subscription->get_formatted_shipping_address(), [ 'br' => [] ] ) . '</p>';
				} else {
					echo '<p class="none_set"><strong>' . esc_html__( 'Address', 'flexible-subscriptions' ) . ':</strong> ' . esc_html__( 'No shipping address set.', 'flexible-subscriptions' ) . '</p>';
				}

				if ( ! empty( $shipping_fields ) ) {
					foreach ( $shipping_fields as $key => $field ) {
						if ( isset( $field['show'] ) && false === $field['show'] ) {
							continue;
						}

						$function_name = 'get_shipping_' . $key;

						if ( is_callable( [ $subscription, $function_name ] ) ) {
							// @phpstan-ignore method.dynamicName
							$field_value = $subscription->$function_name( 'edit' );
						} else {
							$field_value = $subscription->get_meta( '_shipping_' . $key );
						}

						echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( make_clickable( esc_html( $field_value ) ) ) . '</p>';
					}
				}

				if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $subscription->get_customer_note() ) {
					echo '<p><strong>' . esc_html__( 'Customer Provided Note', 'flexible-subscriptions' ) . ':</strong> ' . wp_kses_post( nl2br( $subscription->get_customer_note() ) ) . '</p>';
				}

				echo '</div>';

				// Display form.
				echo '<div class="edit_address">';

				if ( ! empty( $shipping_fields ) ) {
					foreach ( $shipping_fields as $key => $field ) {
						if ( ! isset( $field['type'] ) ) {
							$field['type'] = 'text';
						}
						if ( ! isset( $field['id'] ) ) {
							$field['id'] = '_shipping_' . $key;
						}

						switch ( $field['type'] ) {
							case 'select':
								woocommerce_wp_select( $field, $subscription );
								break;
							default:
								if ( is_callable( [ $subscription, 'get_shipping_' . $key ] ) ) {
									// @phpstan-ignore method.dynamicName
									$field['value'] = $subscription->{"get_shipping_$key"}();
								} else {
									$field['value'] = $subscription->get_meta( $field['id'] );
								}

								woocommerce_wp_text_input( $field );
								break;
						}
					}
				}

				if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) {
					?>
				<p class="form-field form-field-wide"><label for="excerpt"><?php esc_html_e( 'Customer Provided Note', 'flexible-subscriptions' ); ?>:</label>
					<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php esc_attr_e( 'Customer\'s notes about the order', 'flexible-subscriptions' ); ?>"><?php echo wp_kses_post( $subscription->get_customer_note() ); ?></textarea>
				</p>
					<?php
				}

				// -- Payment method

				$payment_method = $subscription->get_payment_method();

				echo '<p class="form-field form-field-wide">';

				if ( count( $payment_methods ) > 1 ) {

					$found_method = false;
					echo '<label>' . esc_html__( 'Payment Method', 'flexible-subscriptions' ) . ':</label>';
					echo '<select class="wcs_payment_method_selector" name="_payment_method" id="_payment_method" class="first">';

					foreach ( $payment_methods as $gateway_id => $gateway_title ) {

						echo '<option value="' . esc_attr( $gateway_id ) . '" ' . selected( $payment_method, $gateway_id, false ) . '>' . esc_html( $gateway_title ) . '</option>';
						if ( $payment_method === $gateway_id ) {
							$found_method = true;
						}
					}
					echo '</select>';

				} elseif ( count( $payment_methods ) === 1 ) {
					echo '<strong>' . esc_html__( 'Payment Method', 'flexible-subscriptions' ) . ':</strong><br/>' . esc_html( current( $payment_methods ) );
					// translators: %s: gateway ID.
					echo wc_help_tip( sprintf( _x( 'Gateway ID: [%s]', 'The gateway ID displayed on the Edit Subscriptions screen when editing payment method.', 'flexible-subscriptions' ), key( $payment_methods ) ) );
					echo '<input type="hidden" value="' . esc_attr( key( $payment_methods ) ) . '" id="_payment_method" name="_payment_method">';
				}

				echo '</p>';

				// -- Payment method end

				echo '</div>';

				do_action( 'woocommerce_admin_order_data_after_shipping_address', $subscription );
				?>
			</div>
		</div>
		<div class="clear"></div>
	</div>
</div>
