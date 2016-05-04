<?php

require_once dirname(__FILE__) . '/order.php';
require_once dirname(__FILE__) . '/refund.php';

function g2apay_draw_refund_button($order_id)
{
    $pay_order = new g2apay_order();
    if ($pay_order->load_by_id($order_id)) {
        $refund = new g2apay_refund();
        if ($refund->can_refund($pay_order)) {
            return tep_draw_button('Refund', 'arrowreturnthick-1-w', tep_href_link('ext/modules/payment/g2apay/refund.php', 'oID=' . (int) $order_id));
        }
    }

    return '';
}
