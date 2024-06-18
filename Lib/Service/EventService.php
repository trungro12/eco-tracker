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
        $option = OptionService::create();
        $arrEvent = $option->arrEventTracking;

        if (empty($arrEvent)) return;

        $gtmId = $option->googleTagManagerID;
        // install GTM js 
        add_action("wp_head", function () use ($gtmId) {
?>
            <!-- Google Tag Manager -->
            <script>
                (function(w, d, s, l, i) {
                    w[l] = w[l] || [];
                    w[l].push({
                        'gtm.start': new Date().getTime(),
                        event: 'gtm.js'
                    });
                    var f = d.getElementsByTagName(s)[0],
                        j = d.createElement(s),
                        dl = l != 'dataLayer' ? '&l=' + l : '';
                    j.async = true;
                    j.src =
                        'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                    f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', '<?php echo $gtmId; ?>');
            </script>
            <!-- End Google Tag Manager -->
        <?php
        }, 1);
        add_action("wp_body_open", function () use ($gtmId) {
        ?>
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmId; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
            <?php
        }, 1);





        if (self::has(self::EVENT_TRACKING_PURCHASE)) {
            add_action("woocommerce_before_thankyou", function ($order_id) {
                if (defined(ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_PURCHASE")) return;
                define(ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_PURCHASE", 1);
                add_action("wp_footer", function () use ($order_id) {
                    echo "<script>" . self::generatePurchaseTrackingScripts($order_id) . "</script>";
                });
            }, 9999);
        }

        if (self::has(self::EVENT_TRACKING_ADD_TO_CART)) {

            // no ajax
            add_action('woocommerce_add_to_cart', function () {
                add_action("wp_footer", function () {
                    if (defined(ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_ADD_TO_CART")) return;
                    define(ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_ADD_TO_CART", 1);
                    echo "<script>" . self::generateAddToCartTrackingScripts() . "</script>";
                });
            }, 9999);

            // ajax
            add_action("wp_ajax_nopriv_AddToCartTrackingEventAjax", __NAMESPACE__ . "\AddToCartTrackingEventAjax");
            add_action("wp_ajax_AddToCartTrackingEventAjax", __NAMESPACE__ . "\AddToCartTrackingEventAjax");
            function AddToCartTrackingEventAjax()
            {
                check_ajax_referer("updateCart", '_ajax_nonce');
                wp_send_json_success([
                    "js" => EventService::generateAddToCartTrackingScripts()
                ]);
            }

            add_action("wp_footer", function () {
                if (defined(ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_ADD_TO_CART")) return;
                define(ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_ADD_TO_CART", 1);
            ?>
                <script id="appendAddToCartEvent"></script>
                <script>
                    jQuery(document).ready(function($) {
                        $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
                            $.ajax({
                                url: `<?php echo admin_url('admin-ajax.php'); ?>`,
                                method: 'POST',
                                data: {
                                    action: 'AddToCartTrackingEventAjax',
                                    _ajax_nonce: '<?php echo wp_create_nonce("updateCart"); ?>'
                                },
                                success: function(res) {
                                    $('#appendAddToCartEvent').html(res.data.js);
                                },
                                error: function() {}
                            });
                        });
                    });
                </script>
                <?php
            });
        }
    }

    public static function adminInit()
    {
        if (self::has(self::EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED)) {
            $optionName = ECOTRACKER_NAMESPACE . "_EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED";
            $productEventIsFired = (int) get_option($optionName, 0);

            if ($productEventIsFired) {



                $gtmId = OptionService::create()->googleTagManagerID;
                add_action("admin_head", function () use ($gtmId) {
                ?>
                    <!-- Google Tag Manager -->
                    <script>
                        (function(w, d, s, l, i) {
                            w[l] = w[l] || [];
                            w[l].push({
                                'gtm.start': new Date().getTime(),
                                event: 'gtm.js'
                            });
                            var f = d.getElementsByTagName(s)[0],
                                j = d.createElement(s),
                                dl = l != 'dataLayer' ? '&l=' + l : '';
                            j.async = true;
                            j.src =
                                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                            f.parentNode.insertBefore(j, f);
                        })(window, document, 'script', 'dataLayer', '<?php echo $gtmId; ?>');
                    </script>
                    <!-- End Google Tag Manager -->
                <?php
                }, 1);
                add_action("admin_footer", function () use ($gtmId) {
                ?>
                    <!-- Google Tag Manager (noscript) -->
                    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmId; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
                    <!-- End Google Tag Manager (noscript) -->
<?php
                }, 1);

                add_action("admin_footer", function () use ($productEventIsFired) {
                    echo "<script>" . self::generateProductConfigTrackingScripts($productEventIsFired) . "</script>";
                });
                delete_option($optionName);
            }

            add_action('woocommerce_update_product', function ($product_id) use ($optionName) {
                update_option($optionName, $product_id);
            });
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
            "items" => array_map(function ($orderItem) use (&$index) {

                $selected_color_slug = wc_get_order_item_meta($orderItem->get_id(), 'pa_color', true);
                // Get color term name from slug
                $term = get_term_by('slug', $selected_color_slug, 'pa_color');
                $color = $term ? $term->name : "";

                /**
                 * @var \WC_Product
                 */
                $product = $orderItem->get_product();

                $arrData = [
                    "item_id" => $product->get_sku(), // SKU
                    "item_name" => $orderItem->get_name(),
                    "index" => $index++,
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

        $scripts = <<<JAVASCRIPT
dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
dataLayer.push({
  event: "purchase",
  ecommerce: $arrData
});
JAVASCRIPT;
        return $scripts;
    }



    public static function generateAddToCartTrackingScripts()
    {

        if (WC()->cart->is_empty()) return "";

        $arrItem = [];
        $cart = WC()->cart;
        $index = 0;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            /**
             * @var \WC_Product
             */
            $_product = $cart_item['data'];

            $arrItem[] =  [
                "item_id" => $_product->get_sku(), // SKU
                "item_name" => $_product->get_name(),
                "index" => $index++,
                "item_variant" => $cart_item['variation']['attribute_pa_color'],
                "price" => $_product->get_price(),
                "quantity" => (int) $cart_item['quantity']
            ];
        }


        $index = 0;
        $arrData = [
            "value" => $cart->get_cart_contents_total() + $cart->get_cart_contents_tax(),
            "currency" => get_woocommerce_currency(),
            "items" => $arrItem
        ];

        $arrData = json_encode($arrData, JSON_PRETTY_PRINT);

        $scripts = <<<JAVASCRIPT
dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
dataLayer.push({
  event: "add_to_cart",
  ecommerce: $arrData
});
JAVASCRIPT;
        return $scripts;
    }


    /**
     * @var int $productId
     */

    private static function generateProductConfigTrackingScripts($productId)
    {

        $product = wc_get_product($productId);

        if (empty($product)) return "";

        $arrItem =  [
            [
                "item_id" => $product->get_sku(), // SKU
                "item_name" => $product->get_name(),
                "index" => 0,
                "price" => $product->get_price(),
            ]
        ];
        $arrData = [
            "items" => $arrItem
        ];
        foreach (wc_get_attribute_taxonomies() as $values) {
            $term_names = get_terms(array('taxonomy' => 'pa_' . $values->attribute_name, 'fields' => 'names'));
            $arrData[$values->attribute_label] = implode(', ', $term_names);
        }

        $arrData = json_encode($arrData, JSON_PRETTY_PRINT);
        $scripts = <<<JAVASCRIPT
dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
dataLayer.push({
  event: "product_configuration_changed",
  ecommerce: $arrData
});
JAVASCRIPT;
        return $scripts;
    }
}
