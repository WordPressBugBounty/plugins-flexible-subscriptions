// External dependencies
const { __ } = wp.i18n;

jQuery(($) => {
	/********************************************************************
	 * INITIAL SETUP
	 ********************************************************************/

	reorderFields();

	if ($('.options_group.pricing').length > 0) {
		togglePricingFieldsGroupTitle();
		displaySubscriptionFields();
		showHideVariableSubscriptionMeta()
// 		updateSubscriptionCyclesUnit();
		updateSubscriptionTotalCyclesMessage();
		updateVariableSubscriptionTotalCyclesMessage();
		showHideSubscriptionsPanels();
// 		updateVariableSubscriptionCyclesUnit();
	}

	/********************************************************************
	 * EVENT HANDLERS
	 ********************************************************************/

	/*********** General product type **********/

	$('body').on('woocommerce-product-type-change', () => {
		displaySubscriptionFields()
		showHideVariableSubscriptionMeta()
		togglePricingFieldsGroupTitle();
		showHideSubscriptionsPanels();
	});

	$('input#_downloadable, input#_virtual').on('change', () => {
		displaySubscriptionFields();
		showHideVariableSubscriptionMeta()
	});

	// Make sure the "Used for variations" checkbox is visible when adding attributes to a variable subscription
	$('body').on('woocommerce_added_attribute woocommerce_attributes_saved', function() {
		showHideVariableSubscriptionMeta();
		showHideSubscriptionsPanels(false);
	});

	// Update subscription ranges when subscription period or interval is changed
	$('#woocommerce-product-data').on(
		'change',
		'[name^="_fsb_subscription_period"], [name^="_fsb_total_billing_cycles"], [name^="_fsb_subscription_interval"], [name^="fsb_variable_subscription_period"], [name^="fsb_variable_subscription_period_interval"], [name^="fsb_variable_total_billing_cycles"]',
		function() {
reorderVariationFields();
// 			updateSubscriptionCyclesUnit();
			updateSubscriptionTotalCyclesMessage();
		updateVariableSubscriptionTotalCyclesMessage();
// 			updateVariableSubscriptionCyclesUnit();
		}
	);

	/*********** Variable type **********/

	// Move the subscription variation pricing section to a better location in the DOM on load
	if (
		$('#variable_product_options .fsb_variable_subscription_pricing').length >
		0
	) {
reorderVariationFields();
	}

	$('#woocommerce-product-data').on(
		'woocommerce_variations_added woocommerce_variations_loaded',
		function() {
reorderVariationFields();
			showHideVariableSubscriptionMeta();
// 			updateSubscriptionCyclesUnit();
			updateSubscriptionTotalCyclesMessage();
		updateVariableSubscriptionTotalCyclesMessage();
// 			updateVariableSubscriptionCyclesUnit();
		}
	);

	/********************************************************************
	 * FUNCTION DECLARATIONS
	 ********************************************************************/

	/*********** jQuery dependant **********/

	/**
	 * Show all the fields that are required for our subscription product.
	 *
	 * We need this hack to display all subscriptions options inherited from WC_Product_Simple.
	 * It is impossible to do it with PHP, because 'show_if_simple' is scattered across the code.
	 */
	function displaySubscriptionFields() {
		if (isSubscriptionProduct()) {
			$('.show_if_simple').show()
			$('.grouping_options').hide()
			$('#fsb-sale-price-period').show()
			$('.hide_if_fsb-subscription').hide()
			$('input#_manage_stock').trigger('change')
		} else {
			$('#fsb-sale-price-period').hide();
		}
	}

	function showHideVariableSubscriptionMeta() {
		// In order for WooCommerce not to show the stock_status_field on variable subscriptions, make sure it has the hide if variable subscription class.
		$('p.stock_status_field').addClass(
			'hide_if_fsb-variable-subscription'
		);

		/**
		 * WC core will hide and show product specific fields in show_and_hide_panels(), however that function only runs on specific events, but not
		 * when variations are added or loaded. To make sure our subscription-related fields aren't shown by default when a variation is added, we set
		 * subscription pricing elements "base" cases here.
		 *
		 * Note: show() being called on the 'hide_if_' fields and vice versa is intentional. All fields are set in their inverse state first, and
		 * then shown/hidden by product type afterwards.
		 */
		$('.hide_if_fsb-variable-subscription').show();
		$('.show_if_fsb-variable-subscription').hide();

		if (isVariableSubscriptionProduct()) {
			$('input#_downloadable').prop('checked', false);
			$('input#_virtual').prop('checked', false);

			$('.show_if_variable').show();
			$('.hide_if_variable').hide();
			$('.show_if_fsb-variable-subscription').show();
			$('.hide_if_fsb-variable-subscription').hide();
			//$.showOrHideStockFields();
		} else {
			if ('variable' === currentProductType()) {
				$('.show_if_fsb-variable-subscription').hide();
				$('.show_if_variable').show();
				$('.hide_if_variable').hide();
				//$.showOrHideStockFields();
			}

			if (isSubscriptionProduct()) {
				$('.show_if_fsb-subscription').show();
				$('.hide_if_fsb-subscription').hide();
			}
		}
	}

	/**
	 * WC hides panels, if content is empty, but... for evaluating variable subscription panel, it's
	 * empty content first, and only then the visibility is toggled for general settings, so we must
	 * flush the visibility logic ourselves, after WC has hidden the panel.
	 *
	 * @param focus {boolean}
	 **/
	function showHideSubscriptionsPanels(focus = true) {
		const tab = $( 'div.panel-wrap' )
				.find( 'ul.wc-tabs li' )
				.eq( 0 )
				.find( 'a' );

		const panel = tab.attr( 'href' );

		const visible = $( panel )
			.children( '.options_group' )
			.filter( (_,el) => 'none' != $(el).css( 'display' ) )

		if ( 0 != visible.length ) {
			tab.parent().show();
			if ( focus ) {
				tab.trigger( 'click' )
			}
		}
	}

})

/*********** Vanilla JS **********/

function togglePricingFieldsGroupTitle() {
	const groupTitle = document.querySelector('#fsb-pricing-group-title')
	if ( isSubscriptionProduct() && groupTitle === null ) {
		const p = document.createElement('p')
		p.id = 'fsb-pricing-group-title'
		const strong = document.createElement('strong')
		strong.appendChild(document.createTextNode(__('Subscription pricing', 'flexible-subscriptions')))
		p.appendChild(strong)
		p.appendChild(document.createElement('br'))
		const docsLink = document.createElement('a')
		docsLink.appendChild(document.createTextNode(__('Learn more about subscription pricing', 'flexible-subscriptions')))
		docsLink.target = '_blank'
		docsLink.href = __('https://wpdesk.net/sk/flexible-subscriptions-docs-sub-product-en', 'flexible-subscriptions')
		p.appendChild(docsLink)
		document.querySelector('.options_group.pricing').prepend(p)
	} else if ( ! isSubscriptionProduct() && groupTitle !== null ) {
		document.querySelector('.options_group.pricing').removeChild(document.querySelector('#fsb-pricing-group-title'));
	}
}

function updateSubscriptionTotalCyclesMessage() {
	const frequencyUnit = document.querySelector('#_fsb_subscription_period').value
	const frequencyInterval = document.querySelector('#_fsb_subscription_interval').value
	const totalCycles = parseInt(document.querySelector('#_fsb_total_billing_cycles').value) || 0

	if ( totalCycles === 0 ) {
		document.querySelector('.js-fsb-total-cycles').parentElement.style.display = 'none'
		return
	}

	document.querySelector('.js-fsb-total-cycles').parentElement.style.display = ''
	document.querySelector('.js-fsb-total-cycles').innerText = `${frequencyInterval * totalCycles} ${window.fsb.frequencyUnits[frequencyUnit]}`
}

function updateVariableSubscriptionTotalCyclesMessage() {
	document.querySelectorAll('[name^="fsb_variable_total_billing_cycles"]').forEach(el => {
		const container = el.closest('.woocommerce_variation')
		// debugger;
		const frequencyUnit = container.querySelector('[name^="fsb_variable_subscription_period\["]').value
		const frequencyInterval = container.querySelector('[name^="fsb_variable_subscription_period_interval"]').value
		const totalCycles = parseInt(el.value) || 0

		if ( totalCycles === 0 ) {
			container.querySelector('.js-fsb-total-cycles').parentElement.style.display = 'none'
			return
		}

		container.querySelector('.js-fsb-total-cycles').parentElement.style.display = ''
		container.querySelector('.js-fsb-total-cycles').innerText = `${frequencyInterval * totalCycles} ${window.fsb.frequencyUnits[frequencyUnit]}`
	})
}

function updateSubscriptionCyclesUnit() {
	const frequencyUnit = document.querySelector('#_fsb_subscription_period').value

	document.querySelector('.js-fsb-expiration-unit').innerText = window.fsb.frequencyUnits[frequencyUnit]
}

function updateVariableSubscriptionCyclesUnit() {
	document.querySelectorAll('[name^="fsb_variable_subscription_period"]').forEach(el => {
		el.closest('.js-fsb-variable-billing-schedule').querySelector('.js-fsb-expiration-unit').innerText = window.fsb.frequencyUnits[el.value]
	})
}

function reorderVariationFields() {
	document.querySelectorAll('#variable_product_options .woocommerce_variation')
		.forEach(container => {
			if (container.classList.contains('js-fsb-moved')) {
				return;
			}

			const target = container.querySelector('.variable_pricing')
			target.after(
				container.querySelector('.js-fsb-variable-billing-schedule'),
				container.querySelector('.js-fsb-variable-subscription-trial')
			)

			container.classList.add('js-fsb-moved')
	})
}

/**
 * Default order of fields is a mess. Make it more usable for a reseller. Namely, append our billing
 * schedule settings right after pricing section.
 */
function reorderFields() {
	document.querySelector('.options_group.pricing')
		.after(document.querySelector('.options_group.js-fsb-billing-schedule'));
}

//// Utilities

function currentProductType() {
	return document.querySelector('select#product-type').value
}

function isSubscriptionProduct() {
	return currentProductType() === 'fsb-subscription';
}

function isVariableSubscriptionProduct() {
	return currentProductType() === 'fsb-variable-subscription';
}
