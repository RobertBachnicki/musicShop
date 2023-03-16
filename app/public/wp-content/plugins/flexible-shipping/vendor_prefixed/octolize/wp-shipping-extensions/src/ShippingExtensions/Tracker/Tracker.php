<?php

namespace FSVendor\Octolize\ShippingExtensions\Tracker;

use Exception;
use FSVendor\Octolize\ShippingExtensions\Tracker\DataProvider\ShippingExtensionsDataProvider;
use FSVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FSVendor\WPDesk_Tracker;
/**
 * .
 */
class Tracker implements \FSVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * @var ViewPageTracker
     */
    private $tracker;
    /**
     * @param ViewPageTracker $tracker
     */
    public function __construct(\FSVendor\Octolize\ShippingExtensions\Tracker\ViewPageTracker $tracker)
    {
        $this->tracker = $tracker;
    }
    /**
     * Hooks.
     */
    public function hooks() : void
    {
        try {
            $tracker = $this->get_tracker();
            $tracker->add_data_provider(new \FSVendor\Octolize\ShippingExtensions\Tracker\DataProvider\ShippingExtensionsDataProvider($this->tracker));
        } catch (\Exception $e) {
            // phpcs:ignore
            // Do nothing.
        }
    }
    /**
     * @return WPDesk_Tracker
     * @throws Exception
     */
    protected function get_tracker() : \FSVendor\WPDesk_Tracker
    {
        $tracker = \apply_filters('wpdesk_tracker_instance', null);
        if ($tracker instanceof \FSVendor\WPDesk_Tracker) {
            return $tracker;
        }
        throw new \Exception('Tracker not found');
    }
}
