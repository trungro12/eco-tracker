<?php

namespace EcoTracker\Lib\Service;


class OptionService
{

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * Google Tag Manager ID
     * @var string
     */
    public $googleTagManagerID;


    /**
     * Event for Tracking
     * @var array
     */
    public $arrEventTracking = [
        EventService::EVENT_TRACKING_PURCHASE,
        EventService::EVENT_TRACKING_ADD_TO_CART,
        EventService::EVENT_TRACKING_PRODUCT_CONFIGURATION_CHANGED,
    ];



    public static function init()
    {
    }

    public static function create()
    {
        return !empty(self::$instance) ? self::$instance : new self();
    }

    public function __construct()
    {
        $arrOption = json_decode(trim(get_option(ECOTRACKER_NAMESPACE . '_option')), true) ?? [];
        foreach ($arrOption as $key => $option) {
            $this->$key = $option;
        }
        self::$instance = $this;
    }

    public function update()
    {

        $arrEventTracking = [];
        foreach ((array) $this->arrEventTracking as $event) {
            $event = (int) $event;
            if (!in_array($event, $this->arrEventTracking)) continue;

            $arrEventTracking[] = $event;
        }

        $this->arrEventTracking = $arrEventTracking;


        $arrOption = [
            'googleTagManagerID' => sanitize_text_field($this->googleTagManagerID),
            'arrEventTracking' => $this->arrEventTracking,
        ];
        return update_option(ECOTRACKER_NAMESPACE . '_option', json_encode($arrOption));
    }

    public function updateByData($arrData = [])
    {
        foreach ($arrData as $key => $data) {
            $this->$key = ($data);
        }
        return $this->update();
    }
}
