/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { getSetting } from '@woocommerce/settings';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	TotalsItem,
	Panel,
	TotalsWrapper,
	Subtotal,
	TotalsTaxes,
	ExperimentalOrderMeta,
	registerCheckoutFilters,
} from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

const displayCartPricesIncludingTax = getSetting( 'displayCartPricesIncludingTax', false);

/**
 * Renders the recurring totals section in the checkout block.
 *
 * @param {Object} props             Component props.
 * @param {Object} props.cart        The cart object from the checkout data store.
 * @param {Object} props.extensions  The extensions object from the checkout data store.
 */
const OrderMetaForSubscriptions = ( { extensions, cart } ) => {
	const { future_subscriptions: futureSubscriptions } =
		extensions[ 'flexibleSubscriptions' ] || {};

	if ( ! futureSubscriptions || futureSubscriptions.length === 0 ) {
		return null;
	}

	// Right now we only support one shipping method per full order.
	const selectedShippingMethod = cart.shippingRates[0]?.shipping_rates?.find(
		( rate ) => rate.selected === true
	);

	return futureSubscriptions.map( ( subscription ) => {
		const {
			key,
			first_payment_date: firstPaymentDate,
			billing_frequency_readable: billingFrequencyReadable,
			expiration_readable: expirationReadable,
			totals,
		} = subscription;

		const currency = getCurrencyFromPriceResponse( totals );

		const recurringTotalLabel = sprintf(
			// translators: %s: recurring interval string (e.g. "month" or "every 2 weeks").
			__( 'Recurring total (every %s)', 'flexible-subscriptions' ),
			billingFrequencyReadable
		);

		const expirationString =
			expirationReadable && expirationReadable.length > 0
				? sprintf(
						// translators: %s: expiration period (e.g. "for 6 months").
						__( 'for %s', 'flexible-subscriptions' ),
						expirationReadable
				  )
				: '';

		const firstPaymentDateString = sprintf(
			// translators: %s: formatted date.
			__( 'First renewal: %s', 'flexible-subscriptions' ),
			new Date( firstPaymentDate ).toLocaleDateString( undefined, {
				year: 'numeric',
				month: 'long',
				day: 'numeric',
			} )
		);

		const shippingAmount = displayCartPricesIncludingTax
			? parseInt( totals.total_shipping, 10 ) + parseInt( totals.total_shipping_tax, 10)
			: parseInt( totals.total_shipping, 10 );

		const shippingValue = shippingAmount === 0
			? <strong>{ __( 'Free', 'flexible-subscriptions' ) }</strong>
			: shippingAmount;

		return (
			<div className="fsb-recurring-totals-panel" key={ key }>
				<TotalsItem
					className="fsb-recurring-totals-panel__title"
					currency={ currency }
					label={ recurringTotalLabel }
					value={ parseInt( totals.total_price, 10 ) }
					description={ `${ firstPaymentDateString } ${ expirationString }` }
				/>
				<Panel
					className="fsb-recurring-totals-panel__details"
					initialOpen={ false }
					title={ __( 'Details', 'flexible-subscriptions' ) }
				>
					<TotalsWrapper>
						<Subtotal currency={ currency } values={ totals } />
					</TotalsWrapper>
					{ cart.cartNeedsShipping && cart.cartHasCalculatedShipping &&
						<TotalsWrapper className="wc-block-components-totals-shipping">
							<TotalsItem
								value={shippingValue}
								currency={currency}
								label={ __( 'Shipping', 'flexible-subscriptions' ) }
								description={
									!!selectedShippingMethod &&
									sprintf(
										__(
											'Via %s',
											'flexible-subscriptions'
										),
										selectedShippingMethod.name
									)
								}
							/>
						</TotalsWrapper>
					}
					{ ! displayCartPricesIncludingTax && (
						<TotalsWrapper>
							<TotalsTaxes
								currency={ currency }
								values={ totals }
							/>
						</TotalsWrapper>
					) }
					<TotalsWrapper>
						<TotalsItem
							className="fsb-recurring-totals-panel__details-total"
							currency={ currency }
							label={ __( 'Total', 'flexible-subscriptions' ) }
							value={ parseInt( totals.total_price, 10 ) }
						/>
					</TotalsWrapper>
				</Panel>
			</div>
		);
	} );
};

/**
 * Register the plugin.
 */
registerPlugin( 'flexible-subscriptions', {
	render: () => (
		<Fragment>
			<ExperimentalOrderMeta>
				<OrderMetaForSubscriptions />
			</ExperimentalOrderMeta>
		</Fragment>
	),
	scope: 'woocommerce-checkout',
} );

/**
 * Register checkout filters.
 */
registerCheckoutFilters( 'flexible-subscriptions', {
	totalLabel: ( originalLabel, { flexibleSubscriptions } ) => {
		const { future_subscriptions: futureSubscriptions } =
			flexibleSubscriptions || {};

		return futureSubscriptions?.length > 0
			? __( 'Total due today', 'flexible-subscriptions' )
			: originalLabel;
	},
	placeOrderButtonLabel: ( originalLabel ) => {
		const fsbData = getSetting( 'flexible-subscriptions_data', {} );
		return fsbData?.place_order_override || originalLabel;
	},
	cartItemPrice: ( originalPriceHtml, {flexibleSubscriptions} ) => {
		if ( flexibleSubscriptions?.signup_fee > 0 ) {
			return sprintf(
				// translators: %s is the subscription price to pay immediately (e.g. "$10").
				__( '%s due today', 'flexible-subscriptions' ),
				originalPriceHtml
			);
		}
		return originalPriceHtml;
	},
} );
