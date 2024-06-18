<?php

namespace EcoTracker\Lib\Menu;

use EcoTracker\Lib\Service\EventService;
use EcoTracker\Lib\Service\OptionService;

class AdminMenu
{
    public static function init()
    {
        if (!is_admin()) return;
        self::initMenu();
    }


    private static function initMenu()
    {
        add_action("admin_menu", function () {
            add_submenu_page('woocommerce', ECOTRACKER_NAME, ECOTRACKER_NAME, 'administrator', 'ecotracker-admin', function () {
                $option = OptionService::create();

                if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], "update")) {
                    // check again, for sure
                    if (!current_user_can("administrator")) {
                        exit(__("You do not have permission to access this request!"));
                    }
                    // don't worry, data will sanitize when save to DB
                    $option->updateByData($_POST);
                }
?>
                <div class="wrap">
                    <h1><?php _e(ECOTRACKER_NAME . " Settings") ?></h1>
                    <form action="" method="post">
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="googleTagManagerID">Google Tag Manager ID</label></th>
                                    <td>
                                        <input placeholder="GTM-WBG12345" name="googleTagManagerID" type="text" id="googleTagManagerID" value="<?php echo sanitize_text_field($option->googleTagManagerID); ?>" class="regular-text code">
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="googleTagManagerID">Event for Tracking</label></th>
                                    <td>
                                        <p><input <?php checked(EventService::has(EventService::EVENT_TRACKING_PURCHASE)); ?> type="checkbox" name="arrEventTracking[]" value="<?php echo EventService::EVENT_TRACKING_PURCHASE; ?>"> PURCHASE</p>

                                        <p><input <?php checked(EventService::has(EventService::EVENT_TRACKING_ADD_TO_CART)); ?> type="checkbox" name="arrEventTracking[]" value="<?php echo EventService::EVENT_TRACKING_ADD_TO_CART; ?>"> ADD TO CART</p>

                                        <p><input <?php checked(EventService::has(EventService::EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED)); ?> type="checkbox" name="arrEventTracking[]" value="<?php echo EventService::EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED; ?>"> PRODUCT CONFIGURATION CHANGED</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php wp_nonce_field("update") ?>
                        <?php submit_button(); ?>
                    </form>
                </div>
<?php
            });
        });
    }
}
