<?php

chdir('../../../../');
require 'includes/application_top.php';
require DIR_WS_CLASSES . 'order.php';

include DIR_FS_CATALOG . '/includes/languages/' . $language . '/modules/payment/g2apay.php';
require_once 'includes/modules/payment/g2apay.php';
require_once 'includes/modules/payment/g2apay/session.php';

$action = isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '';
$data   = (array) $HTTP_GET_VARS;

$g2apay      = new g2apay();
$pay_session = new g2apay_session();

if (!$pay_session->validate_data($data)) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,
        'error_message=' . MODULE_PAYMENT_G2APAY_ERROR_GATEWAY_INVALID_DATA, 'SSL'));
}

$order_id = $pay_session->get_order_id();

$pay_order = new g2apay_order();

if (!$pay_order->load_by_id($order_id)) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,
        'error_message=' . MODULE_PAYMENT_G2APAY_ERROR_GATEWAY_ORDER_NOT_FOUND, 'SSL'));
}

if ((!isset($customer_id)) || ($pay_order->get_customer_id() != $customer_id)) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,
        'error_message=' . MODULE_PAYMENT_G2APAY_ERROR_GATEWAY_FORBIDDEN, 'SSL'));
}

switch ($action) {
    case 'success':
        $pay_session->clear();
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS,
            'info_message=' . MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_SUCCESS, 'SSL'));
        break;

    case 'cancel':
        $comment = MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_STATUS_CANCEL;
        $pay_order->update_status(MODULE_PAYMENT_G2APAY_CANCELED_STATUS, $comment);
        $pay_session->clear();
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,
            'info_message=' . MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_CANCEL, 'SSL'));
        break;

    default:
        tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,
            'error_message=' . MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_INVALID_ACTION, 'SSL'));
}
