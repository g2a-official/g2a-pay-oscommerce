<?php

class g2apay_session
{
    /**
     * @param g2apay_order $order
     * @param $token
     */
    public function store_order($order, $token)
    {
        global $g2apay_token, $g2apay_order_id;

        $g2apay_token    = $token;
        $g2apay_order_id = $order->get_id();

        tep_session_register('g2apay_token');
        tep_session_register('g2apay_order_id');
    }

    public function validate_data($data)
    {
        global $g2apay_token, $g2apay_order_id;

        if (!tep_session_is_registered('g2apay_token') || !tep_session_is_registered('g2apay_order_id')) {
            return false;
        }

        $data = (array) $data;

        if (!isset($data['token']) || $data['token'] !== $g2apay_token) {
            return false;
        }

        if (!isset($data['order_id']) || $data['order_id'] != $g2apay_order_id) {
            return false;
        }

        return true;
    }

    public function get_order_id()
    {
        global $g2apay_order_id;

        return $g2apay_order_id;
    }

    public function clear()
    {
        global $g2apay_token, $g2apay_order_id;

        tep_session_unregister('g2apay_token');
        tep_session_unregister('g2apay_order_id');

        $g2apay_token    = null;
        $g2apay_order_id = null;
    }
}
