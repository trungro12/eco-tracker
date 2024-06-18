<?php

namespace EcoTracker\Lib\Service;

class InitService
{

    private static $isInit = false;
    private static $isAdminInit = false;
    private static $arrServiceClass = [
        OptionService::class
    ];

    public static function init()
    {

        // check for is init or not
        if (self::$isInit) return;
        self::$isInit = true;

        // check WooCommerce exists, if not will return
        if (!self::isWooCommerceActive()) return;

        self::initAll("init");
    }

    public static function adminInit()
    {
        // check for is init or not
        if (self::$isAdminInit) return;
        self::$isAdminInit = true;

        if (!self::isWooCommerceActive()) {
            add_action("admin_notices", function () {
?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e(ECOTRACKER_NAME . ': WooCommerce is not installed or activated. Please install and activate WooCommerce.', 'check-woocommerce'); ?></p>
                </div>
<?php
            });

            return;
        }

        self::initAll("adminInit");
    }

    /**
     * Check for WooCommerce Plugin is Active
     */
    private static function isWooCommerceActive()
    {
        return class_exists('WooCommerce');
    }

    /**
     * run all init function 
     */
    private static function initAll($initFunctionName = "init")
    {
        foreach (self::$arrServiceClass as $class) {
            if ($class === __CLASS__) continue;

            if (!method_exists($class, $initFunctionName)) continue;

            call_user_func($class . "::" . $initFunctionName);
        }
    }
}
