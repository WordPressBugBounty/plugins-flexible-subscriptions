/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Fragment, useEffect, useMemo, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
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
	ExperimentalOrderShippingPackages,
	ExperimentalOrderLocalPickupPackages,
	registerCheckoutFilters,
} from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

const displayCartPricesIncludingTax = getSetting( 'displayCartPricesIncludingTax', false);
const collectableMethodIds = getSetting( 'collectableMethodIds', [] );
const localPickupEnabled = getSetting( 'localPickupEnabled', false );

const hasShippingRates = ( packages ) =>
	packages?.some( ( pkg ) => !!pkg.shipping_rates?.length );

const isCollectableRate = ( rate ) => {
	if ( ! localPickupEnabled ) {
		return false;
	}
	if ( Array.isArray( rate.method_id ) ) {
		return rate.method_id.some( ( id ) => collectableMethodIds.includes( id ) );
	}
	return collectableMethodIds.includes( rate.method_id );
};

const getSelectedRateName = ( packages ) =>
	packages?.flatMap( ( pkg ) => pkg.shipping_rates || [] )
		.find( ( rate ) => rate.selected )?.name;

const getSelectedShippingValue = ( totals ) =>
	displayCartPricesIncludingTax
		? parseInt( totals.total_shipping, 10 ) + parseInt( totals.total_shipping_tax, 10)
		: parseInt( totals.total_shipping, 10 );

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

	return futureSubscriptions.map( ( subscription ) => {
		const {
			key,
			first_payment_date: firstPaymentDate,
			billing_frequency_readable: billingFrequencyReadable,
			expiration_readable: expirationReadable,
			totals,
			shipping_rates: shippingRates = [],
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
			firstPaymentDate
		);

		const shippingAmount = getSelectedShippingValue( totals );

		const shippingValue = shippingAmount === 0
			? <strong>{ __( 'Free', 'flexible-subscriptions' ) }</strong>
			: shippingAmount;

		const selectedShippingMethod = getSelectedRateName( shippingRates );

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
										selectedShippingMethod
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

const SubscriptionShippingPackages = ( {
	extensions,
	collapsible,
	collapse,
	showItems,
	noResultsMessage,
	renderOption,
	components,
	context,
} ) => {
	const { future_subscriptions: futureSubscriptions = [] } =
		extensions[ 'flexibleSubscriptions' ] || {};
	const { ShippingRatesControlPackage } = components;

	const allRates = useMemo(
		() => futureSubscriptions.map( ( subscription ) => subscription.shipping_rates ).filter( Boolean ).flat(),
		[ futureSubscriptions ]
	);

	const shouldCollapse = useMemo(
		() => allRates.length > 1 || collapse,
		[ allRates.length, collapse ]
	);
	const shouldShowItems = useMemo(
		() => allRates.length > 1 || showItems,
		[ allRates.length, showItems ]
	);

	return allRates
		.filter( ( pkg ) => ! pkg.match_initial_rates && pkg.needs_shipping )
		.map( ( { package_id: packageId, ...packageData } ) => {
			packageData.shipping_rates = ( packageData.shipping_rates || [] ).filter(
				( rate ) => ! isCollectableRate( rate )
			);

			return (
				<ShippingRatesControlPackage
					key={ packageId }
					packageId={ packageId }
					packageData={ packageData }
					collapsible={ collapsible }
					collapse={ shouldCollapse }
					showItems={ shouldShowItems }
					noResultsMessage={ noResultsMessage }
					renderOption={ renderOption }
					highlightChecked={ context === 'woocommerce/checkout' }
				/>
			);
		} );
};

const LocalPickupSelectWrapper = ( {
	packageId,
	packageData,
	showItems,
	renderPickupLocation,
	pickupLocations,
	packageCount,
	LocalPickupSelect,
} ) => {
	const { selectShippingRate } = useDispatch( window.wc.wcBlocksData.cartStore );
	const initialRate =
		packageData.shipping_rates.find( ( rate ) => rate.selected )?.rate_id ??
		packageData.shipping_rates[0]?.rate_id;
	const [ selectedOption, setSelectedOption ] = useState( initialRate );

	useEffect( () => {
		if ( selectedOption ) {
			selectShippingRate( selectedOption, packageId );
		}
	}, [] );

	return (
		<LocalPickupSelect
			title={ packageData.name }
			packageData={ packageData }
			selectedOption={ selectedOption ?? '' }
			showItems={ showItems }
			renderPickupLocation={ renderPickupLocation }
			pickupLocations={ pickupLocations }
			packageCount={ packageCount }
			onChange={ ( newRateId ) => {
				setSelectedOption( newRateId );
				selectShippingRate( newRateId, packageId );
			} }
		/>
	);
};

const SubscriptionLocalPickupPackages = ( {
	extensions,
	showItems,
	renderPickupLocation,
	components,
} ) => {
	const { future_subscriptions: futureSubscriptions = [] } =
		extensions[ 'flexibleSubscriptions' ] || {};
	const { LocalPickupSelect } = components;

	if ( ! localPickupEnabled ) {
		return null;
	}

	const allRates = useMemo(
		() => futureSubscriptions.map( ( subscription ) => subscription.shipping_rates ).filter( Boolean ).flat(),
		[ futureSubscriptions ]
	);

	const shouldShowItems = useMemo(
		() => allRates.length > 1 || showItems,
		[ allRates.length, showItems ]
	);

	return allRates
		.filter( ( pkg ) => ! pkg.match_initial_rates )
		.map( ( { package_id: packageId, ...packageData } ) => {
			packageData.shipping_rates = ( packageData.shipping_rates || [] ).filter(
				( rate ) => isCollectableRate( rate )
			);

			if ( packageData.shipping_rates.length === 0 ) {
				return null;
			}

			return (
				<LocalPickupSelectWrapper
					key={ packageId }
					packageId={ packageId }
					packageData={ packageData }
					showItems={ shouldShowItems }
					renderPickupLocation={ renderPickupLocation }
					pickupLocations={ packageData.shipping_rates }
					packageCount={ allRates.length }
					LocalPickupSelect={ LocalPickupSelect }
				/>
			);
		} );
};

/**
 * Register the plugin.
 */
registerPlugin( 'flexible-subscriptions', {
	render: () => (
		<Fragment>
			<ExperimentalOrderShippingPackages>
				<SubscriptionShippingPackages />
			</ExperimentalOrderShippingPackages>
			<ExperimentalOrderLocalPickupPackages>
				<SubscriptionLocalPickupPackages />
			</ExperimentalOrderLocalPickupPackages>
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
