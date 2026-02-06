jQuery(($) => {
	if ( $( '#parent-order-id' ).is( 'select' ) ) {
		update_parent_order_options();

		$( '#customer_user' ).on( 'change', update_parent_order_options );
	}

	function update_parent_order_options() {
		// Get user ID to load orders for
		var user_id = $( '#customer_user' ).val();

		if ( ! user_id ) {
			$( '#parent-order-id' ).select2({
				// todo: i18n
				placeholder: 'Select customer first',
				disabled: true,
			});
			return false;
		}

		var data = {
			user_id: user_id,
			action: 'fsb_get_customer_orders',
			security: wcs_admin_meta_boxes.get_customer_orders_nonce,
		};

		$( '#parent-order-id' )
			.siblings( '.select2-container' )
			.block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

		$.ajax( {
			url: window.ajaxurl,
			data: data,
			type: 'POST',
			success: function ( response ) {
				if ( response ) {
					var $orderlist = $( '#parent-order-id' );

					$( '#parent-order-id' ).select2({
						disabled: false
					});

					$orderlist.empty(); // remove old options

					$orderlist.append(
						$( '<option></option>' )
							.attr( 'value', '' )
							.text( 'Select an order' )
					);

					$.each( response, function ( order_id, order_number ) {
						$orderlist.append(
							$( '<option></option>' )
								.attr( 'value', order_id )
								.text( order_number )
						);
					} );

					$( '#parent-order-id' )
						.siblings( '.select2-container' )
						.unblock();
				}
			},
		} );
		return false;
	}
})
