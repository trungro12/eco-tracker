<?php

namespace EcoTracker\Lib\Service;

/**
 * Event for Tracking with GTM (Google Tag Manager)
 */
class EventService
{

    const EVENT_TRACKING_PURCHASE = 1;
    const EVENT_TRACKING_ADD_TO_CART = 2;
    const EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED = 3;

    /**
     * Init event
     */
    public static function init()
    {
        if (self::has(self::EVENT_TRACKING_PURCHASE)) {
            add_action("wp_head", function () {
                echo self::generatePurchaseTrackingScripts(71);
            }, 9999);
        }

        if (self::has(self::EVENT_TRACKING_ADD_TO_CART)) {
        }

        if (self::has(self::EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED)) {
        }
    }

    /**
     * Check is event exists in Option
     * @param int $event
     */
    public static function has($event)
    {
        return in_array((int) $event, OptionService::create()->arrEventTracking);
    }


    private static function generatePurchaseTrackingScripts($order_id)
    {
        $order = wc_get_order($order_id);
        if (empty($order)) return "";

        $taxTotal = 0;

        foreach ($order->get_tax_totals() as $code => $tax) {
            $taxTotal += $tax['amount'];
        }


        $index = 0;
        $arrData = [
            "transaction_id" => $order->get_transaction_id(),
            // Sum of (price * quantity) for all items.
            "value" => $order->get_total(),
            "tax" => $taxTotal,
            "shipping" => $order->get_shipping_tax(),
            "currency" => $order->get_currency(),
            "coupon" => $order->get_coupon_codes()[0] ?? "",
            "items" => array_map(function (\WC_Order_Item $orderItem) use (&$index) {

                $selected_color_slug = wc_get_order_item_meta($orderItem->get_id(), 'pa_color', true);
                // Get color term name from slug
                $term = get_term_by('slug', $selected_color_slug, 'pa_color');
                $color = $term ? $term->name : "";

                /**
                 * @var \WC_Product
                 */
                $product = $orderItem->get_product();

                $arrData = [
                    "item_id" => $orderItem->get_id(),
                    "item_name" => $orderItem->get_name(),
                    "index" => $index++,
                    "item_brand" => "Google",
                    "item_variant" => $color,
                    "price" => $product->get_price(),
                    "quantity" => $orderItem->get_quantity()
                ];

                $productParentId = $product->get_id();
                if ($product->is_type('variation')) {
                    $productParentId = $product->get_parent_id();
                }
                $terms = get_the_terms($productParentId, 'product_cat');
                if (!is_wp_error($terms)) {
                    foreach ($terms as $index => $term) {
                        $arrData['item_category' . ($index + 1 > 1 ? $index + 1 : "")] = $term->name;
                    }
                }

                return $arrData;
            }, $order->get_items())
        ];

        $arrData = json_encode($arrData, JSON_PRETTY_PRINT);

        $scripts = <<<HTML
<script>
dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
dataLayer.push({
  event: "purchase",
  ecommerce: $arrData
});
</script>
HTML;
        return $scripts;
    }
}
