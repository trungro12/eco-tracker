

# eco-tracker - A Wordpress Plugin
Wordpress Plugin tracks specific events and pushes data to the dataLayer for use with Google Tag Manager (GTM)

# Install

## Google Analytics

* you need add **Custom Events** into GA4: `purchase`, `add_to_cart` and `product_configuration_changed`

## Google Tag Manager (GTM)

* you need add **Trigger** into GTM (with Event Type: *Custom Event*): `purchase`, `add_to_cart` and `product_configuration_changed`


## Website Options

Go to EcoTracker Options (http://localhost/wp-admin/admin.php?page=ecotracker-admin)

* Insert your **Google Tag Manager ID** to enable Tracking
* You can enable or disable any tracking event at **Event for Tracking**.

* `purchase` : Fire when customer place order
* `add_to_cart` : Fire when customer Add To Cart Product
* `product_configuration_changed` : Fire when Admin Update Product


All Event will stored like this

![Event](https://i.imgur.com/arsMW5z.png)

*Enjoy! :)*