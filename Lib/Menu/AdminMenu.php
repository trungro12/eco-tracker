<?php

namespace EcoTracker\Lib\Menu;

class AdminMenu
{
    public static function init()
    {
        if(!is_admin()) return;
        self::initMenu();
        die;
    }


    private static function initMenu()
    {
        add_action("admin_menu", function () {
            add_submenu_page('wc-admin', ECOTRACKER_NAME, ECOTRACKER_NAME, 'administrator', 'ecotracker-admin', function () {
?>
                <div class="wrap">
                    <h1>My Custom WooCommerce Submenu Page</h1>
                    <p>Welcome to my custom WooCommerce submenu page!</p>
                </div>
<?php
            });
        });
    }
}
