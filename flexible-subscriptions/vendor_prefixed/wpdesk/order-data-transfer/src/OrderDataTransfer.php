<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\OrderDataTransfer;

class OrderDataTransfer
{
    /**
     * The default data keys that are excluded from the copy.
     *
     * @var string[]
     */
    private const EXCLUDED_META_KEYS = ['_paid_date', '_date_paid', '_completed_date', '_date_completed', '_edit_last', '_order_key', '_edit_lock', '_wc_points_earned', '_transaction_id', '_payment_method', '_payment_method_title', '_created_via', '_order_stock_reduced'];
    /**
     * Copy meta data along with order items (and their meta).
     * This method has some excludes, which are never transfered between orders, like {@see self::EXCLUDED_META_KEYS} and customer note, parent id
     *
     * @param \WC_Order $from
     * @param \WC_Order|null $to If null, a new order will be created.
     */
    /**
     * @param array{
     *     excluded_meta_keys?: string[],
     *     excluded_meta_keys_filter?: callable(string[], \WC_Order, \WC_Order): string[],
     *     data_filter?: callable(array<string, mixed>, \WC_Order, \WC_Order): array<string, mixed>
     * } $options
     */
    public function copy(\WC_Order $from, ?\WC_Order $to = null, array $options = []): \WC_Order
    {
        if ($to === null) {
            $to = $this->create_order();
        }
        $data = $this->get_meta_data($from);
        $data += $this->get_order_data($from);
        $data += $this->get_operational_data($from);
        $data += $this->get_address_data($from);
        // Payment token meta isn't accounted from in the above methods, so we need to add it separately.
        if (!isset($data['_payment_tokens'])) {
            $tokens = $from->get_payment_tokens();
            if (!empty($tokens)) {
                $data['_payment_tokens'] = $tokens;
            }
        }
        $this->copy_data($data, $from, $to, $options);
        foreach ($from->get_items(['line_item', 'fee', 'tax', 'shipping', 'coupon']) as $item) {
            $item_copy = $this->create_item_clone($item, $to);
            $this->copy_item_data($item, $item_copy);
            $to->add_item($item_copy);
        }
        $to->save();
        return $to;
    }
    /**
     * Copies custom meta only. This is useful when the destination is a WC order
     * subtype whose core order data must be managed by its owner.
     *
     * @param string[] $excluded_meta_keys
     * @param callable(array<string, mixed>, \WC_Order, \WC_Order): array<string, mixed>|null $data_filter
     * @param callable(string[], \WC_Order, \WC_Order): string[]|null $excluded_meta_keys_filter
     */
    public function copy_meta_data(\WC_Order $from, \WC_Order $to, array $excluded_meta_keys = [], ?callable $data_filter = null, ?callable $excluded_meta_keys_filter = null): void
    {
        $this->copy_data($this->get_meta_data($from), $from, $to, ['excluded_meta_keys' => $excluded_meta_keys, 'excluded_meta_keys_filter' => $excluded_meta_keys_filter, 'data_filter' => $data_filter]);
    }
    public function copy_item_data(\WC_Order_Item $from, \WC_Order_Item $to): void
    {
        foreach ($from->get_meta_data() as $meta_data) {
            if ('_reduced_stock' === $meta_data->key) {
                continue;
            }
            $to->update_meta_data($meta_data->key, $meta_data->value);
        }
        switch ($from->get_type()) {
            case 'line_item':
                /** @var \WC_Order_Item_Product $from */
                $to->set_props(['product_id' => $from->get_product_id(), 'variation_id' => $from->get_variation_id(), 'quantity' => $from->get_quantity(), 'tax_class' => $from->get_tax_class(), 'subtotal' => $from->get_subtotal(), 'total' => $from->get_total(), 'taxes' => $from->get_taxes()]);
                break;
            case 'shipping':
                /** @var \WC_Order_Item_Shipping $from */
                /** @var \WC_Order_Item_Shipping $to */
                $to->set_props(['method_id' => $from->get_method_id(), 'total' => $from->get_total(), 'taxes' => $from->get_taxes()]);
                $to->set_instance_id($from->get_instance_id());
                break;
            case 'tax':
                /**
                 * @var \WC_Order_Item_Tax $from
                 * @var \WC_Order_Item_Tax $to
                 */
                $to->set_props(['rate_id' => $from->get_rate_id(), 'label' => $from->get_label(), 'compound' => $from->get_compound(), 'tax_total' => $from->get_tax_total(), 'shipping_tax_total' => $from->get_shipping_tax_total()]);
                $to->set_rate_percent($from->get_rate_percent());
                break;
            case 'fee':
                /** @var \WC_Order_Item_Fee $from */
                $to->set_props(['tax_class' => $from->get_tax_class(), 'tax_status' => $from->get_tax_status(), 'total' => $from->get_total(), 'taxes' => $from->get_taxes()]);
                break;
            case 'coupon':
                /** @var \WC_Order_Item_Coupon $from */
                $to->set_props(['discount' => $from->get_discount(), 'discount_tax' => $from->get_discount_tax()]);
                break;
        }
    }
    /**
     * @param array<string, mixed> $data
     * @param array{
     *     excluded_meta_keys?: string[],
     *     excluded_meta_keys_filter?: callable(string[], \WC_Order, \WC_Order): string[],
     *     data_filter?: callable(array<string, mixed>, \WC_Order, \WC_Order): array<string, mixed>
     * } $options
     */
    private function copy_data(array $data, \WC_Order $from, \WC_Order $to, array $options): void
    {
        $excluded_meta_keys = array_merge(self::EXCLUDED_META_KEYS, $options['excluded_meta_keys'] ?? []);
        if (isset($options['excluded_meta_keys_filter'])) {
            $excluded_meta_keys = $options['excluded_meta_keys_filter']($excluded_meta_keys, $to, $from);
        }
        $data = array_filter($data, static fn(string $key): bool => !in_array($key, $excluded_meta_keys, \true), \ARRAY_FILTER_USE_KEY);
        if (isset($options['data_filter'])) {
            $data = $options['data_filter']($data, $to, $from);
        }
        foreach ($data as $key => $value) {
            $this->set_data($to, $key, maybe_unserialize($value));
        }
    }
    private function create_order(): \WC_Order
    {
        return wc_create_order();
    }
    private function create_item_clone(\WC_Order_Item $item, \WC_Order $to): \WC_Order_Item
    {
        $order_item_id = wc_add_order_item($to->get_id(), ['order_item_name' => $item->get_name(), 'order_item_type' => $item->get_type()]);
        return $to->get_item($order_item_id);
    }
    /**
     * @param mixed $value
     */
    private function set_data(\WC_Order $destination, string $key, $value): void
    {
        // WC will automatically set/update these keys when a shipping/billing address attribute changes so we can ignore these keys.
        if (in_array($key, ['_shipping_address_index', '_billing_address_index'], \true)) {
            return;
        }
        // The WC_Order setter for these keys will expect an array of values, return early if the value is not an array.
        if (in_array($key, ['_shipping_address', '_shipping', '_billing_address', '_billing'], \true) && !is_array($value)) {
            return;
        }
        // Special cases where properties with setters don't map nicely to their function names.
        $setter_map = ['_cart_discount' => 'set_discount_total', '_cart_discount_tax' => 'set_discount_tax', '_customer_user' => 'set_customer_id', '_order_tax' => 'set_cart_tax', '_order_shipping' => 'set_shipping_total', '_order_currency' => 'set_currency', '_order_shipping_tax' => 'set_shipping_tax', '_order_total' => 'set_total', '_order_version' => 'set_version'];
        $setter = isset($setter_map[$key]) ? $setter_map[$key] : 'set_' . ltrim($key, '_');
        if (is_callable([$destination, $setter])) {
            // Re-bool the value before setting it. Setters like `set_prices_include_tax()` expect a bool.
            if (is_string($value) && in_array($value, ['yes', 'no'], \true)) {
                $value = 'yes' === $value;
            }
            // @phpstan-ignore method.dynamicName
            $destination->{$setter}($value);
        } elseif ('_payment_tokens' === $key) {
            // Payment tokens don't have a setter and cannot be set via metadata so we need to set them via the datastore.
            $destination->get_data_store()->update_payment_token_ids($destination, $value);
        } else {
            $destination->update_meta_data($key, $value);
        }
    }
    /**
     * Gets the "from" object's meta data.
     *
     * @return array<string, mixed> The meta data.
     */
    private function get_meta_data(\WC_Order $source): array
    {
        $meta_data = [];
        foreach ($source->get_meta_data() as $meta) {
            $normalized_meta = $this->normalize_meta_data($meta);
            if ($normalized_meta === null) {
                continue;
            }
            $meta_data[$normalized_meta['key']] = $normalized_meta['value'];
        }
        return $meta_data;
    }
    /**
     * @param mixed $meta
     * @return array{key: string, value: mixed}|null
     */
    private function normalize_meta_data($meta): ?array
    {
        if (is_object($meta) && isset($meta->key)) {
            return ['key' => (string) $meta->key, 'value' => $meta->value ?? null];
        }
        if (is_array($meta) && isset($meta['key'])) {
            return ['key' => (string) $meta['key'], 'value' => $meta['value'] ?? null];
        }
        return null;
    }
    /**
     * Gets the "from" object's operational data that was previously stored in wp post meta.
     *
     * @return array<string, mixed> The operational data with the legacy meta key.
     */
    private function get_operational_data(\WC_Order $source): array
    {
        return ['_created_via' => $source->get_created_via('edit'), '_order_version' => $source->get_version('edit'), '_prices_include_tax' => wc_bool_to_string($source->get_prices_include_tax('edit')), '_recorded_coupon_usage_counts' => wc_bool_to_string($source->get_recorded_coupon_usage_counts('edit')), '_download_permissions_granted' => wc_bool_to_string($source->get_download_permissions_granted('edit')), '_cart_hash' => $source->get_cart_hash('edit'), '_new_order_email_sent' => wc_bool_to_string($source->get_new_order_email_sent('edit')), '_order_key' => $source->get_order_key('edit'), '_order_stock_reduced' => $source->get_order_stock_reduced('edit'), '_date_paid' => $source->get_date_paid('edit'), '_date_completed' => $source->get_date_completed('edit'), '_order_shipping_tax' => $source->get_shipping_tax('edit'), '_order_shipping' => $source->get_shipping_total('edit'), '_cart_discount_tax' => $source->get_discount_tax('edit'), '_cart_discount' => $source->get_discount_total('edit'), '_recorded_sales' => wc_bool_to_string($source->get_recorded_sales('edit'))];
    }
    /**
     * Gets the "from" object's core data that was previously stored in wp post meta.
     *
     * @return array<string, mixed> The core data with the legacy meta keys.
     */
    private function get_order_data(\WC_Order $source): array
    {
        return ['_order_currency' => $source->get_currency('edit'), '_order_tax' => $source->get_cart_tax('edit'), '_order_total' => $source->get_total('edit'), '_customer_user' => $source->get_customer_id('edit'), '_billing_email' => $source->get_billing_email('edit'), '_payment_method' => $source->get_payment_method('edit'), '_payment_method_title' => $source->get_payment_method_title('edit'), '_customer_ip_address' => $source->get_customer_ip_address('edit'), '_customer_user_agent' => $source->get_customer_user_agent('edit'), '_transaction_id' => $source->get_transaction_id('edit')];
    }
    /**
     * Gets the "from" object's address data that was previously stored in wp post meta.
     *
     * @return array<string, mixed> The address data with the legacy meta keys.
     */
    private function get_address_data(\WC_Order $source): array
    {
        return array_filter(['_billing_first_name' => $source->get_billing_first_name('edit'), '_billing_last_name' => $source->get_billing_last_name('edit'), '_billing_company' => $source->get_billing_company('edit'), '_billing_address_1' => $source->get_billing_address_1('edit'), '_billing_address_2' => $source->get_billing_address_2('edit'), '_billing_city' => $source->get_billing_city('edit'), '_billing_state' => $source->get_billing_state('edit'), '_billing_postcode' => $source->get_billing_postcode('edit'), '_billing_country' => $source->get_billing_country('edit'), '_billing_email' => $source->get_billing_email('edit'), '_billing_phone' => $source->get_billing_phone('edit'), '_shipping_first_name' => $source->get_shipping_first_name('edit'), '_shipping_last_name' => $source->get_shipping_last_name('edit'), '_shipping_company' => $source->get_shipping_company('edit'), '_shipping_address_1' => $source->get_shipping_address_1('edit'), '_shipping_address_2' => $source->get_shipping_address_2('edit'), '_shipping_city' => $source->get_shipping_city('edit'), '_shipping_state' => $source->get_shipping_state('edit'), '_shipping_postcode' => $source->get_shipping_postcode('edit'), '_shipping_country' => $source->get_shipping_country('edit'), '_shipping_phone' => $source->get_shipping_phone('edit')], static fn($value): bool => (bool) $value);
    }
}
