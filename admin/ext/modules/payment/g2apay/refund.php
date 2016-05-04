<?php

chdir('../../../../');

require 'includes/application_top.php';
require DIR_WS_CLASSES . 'currencies.php';

$g2apay_base = DIR_FS_CATALOG . 'includes/modules/payment/g2apay';
require_once $g2apay_base . '/order.php';
require_once $g2apay_base . '/refund.php';

include DIR_FS_CATALOG . '/includes/languages/' . $language . '/modules/payment/g2apay.php';

$currencies = new currencies();

$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : 'form');
$pass   = false;

$order_id = (int) tep_db_prepare_input($HTTP_GET_VARS['oID']);
$amount   = tep_db_prepare_input($HTTP_POST_VARS['amount']);

$pay_order = new g2apay_order();
if (!$pay_order->load_by_id($order_id)) {
    $messageStack->add_session(MODULE_PAYMENT_G2APAY_ERROR_REFUND_NOT_FOUND, 'warning');
    tep_redirect(tep_href_link(FILENAME_ORDERS,
        tep_get_all_get_params(['action', 'oID']) . 'action=edit'));
}

$refund = new g2apay_refund();

switch ($action) {
    case 'form':
        if (!$refund->can_refund($pay_order)) {
            $messageStack->add_session(MODULE_PAYMENT_G2APAY_ERROR_REFUND_NOT_ALLOWED, 'warning');
            tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(['oID', 'action']) . 'oID=' . $orders['orders_id'] . '&action=edit'));
        }
        $g2apay_refund_token = g2apay_random_token();
        tep_session_register('g2apay_refund_token');
        break;
    case 'refund':

        if (!isset($g2apay_refund_token) || !isset($HTTP_POST_VARS['_g2apay_refund_token'])
            || empty($HTTP_POST_VARS['_g2apay_refund_token']) || $g2apay_refund_token !== $HTTP_POST_VARS['_g2apay_refund_token']) {
            $messageStack->add_session(MODULE_PAYMENT_G2APAY_ERROR_INVALID_TOKEN, 'warning');
            tep_redirect(tep_href_link('ext/modules/payment/g2apay/refund.php',
                tep_get_all_get_params(['action'])));
        }

        tep_session_unregister('g2apay_refund_token');

        if (isset($HTTP_POST_VARS['online']) && ($HTTP_POST_VARS['online'] == 'on')) {
            if ($refund->can_refund_amount($pay_order, $amount)
                && $refund->can_refund_online($pay_order, $amount)
                && $refund->online($pay_order, $amount)
            ) {
                $messageStack->add_session(MODULE_PAYMENT_G2APAY_TEXT_REFUND_SUCCESS_ONLINE, 'success');
                tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(['oID', 'action']) . 'oID=' . $order_id . '&action=edit'));
            } else {
                $messageStack->add_session($refund->get_error(), 'warning');
                tep_redirect(tep_href_link('ext/modules/payment/g2apay/refund.php',
                    tep_get_all_get_params(['action'])));
            }
        } else {
            if ($refund->can_refund_amount($pay_order, $amount)
                && $refund->offline($pay_order, $amount)
            ) {
                $messageStack->add_session(MODULE_PAYMENT_G2APAY_TEXT_REFUND_SUCCESS_OFFLINE, 'success');
                tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(['oID', 'action']) . 'oID=' . $order_id . '&action=edit'));
            } else {
                $messageStack->add_session($refund->get_error(), 'warning');
                tep_redirect(tep_href_link('ext/modules/payment/g2apay/refund.php',
                    tep_get_all_get_params(['action'])));
            }
        }
        break;
    default:
        $messageStack->add_session(MODULE_PAYMENT_G2APAY_ERROR_REFUND_UNKNOWN_ACTION, 'warning');
        tep_redirect(tep_href_link(FILENAME_ORDERS));
}

require DIR_WS_INCLUDES . 'template_top.php';
?>

<?php echo tep_draw_form('refund', 'ext/modules/payment/g2apay/refund.php',
    tep_get_all_get_params(['action']) . 'action=refund'); ?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr>
            <td width="100%">
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="pageHeading"><?php echo sprintf(MODULE_PAYMENT_G2APAY_TEXT_REFUND_TITLE, $pay_order->get_id()); ?></td>
                        <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif',
    HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
    <td class="smallText" align="right"><?php echo tep_draw_button(IMAGE_BACK, 'triangle-1-w', tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(['action'])) . '&action=edit'); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="main">
                <br/>
                <strong><?php echo MODULE_PAYMENT_G2APAY_TEXT_REFUND_INPUT; ?></strong>
            </td>
        </tr>
        <tr>
            <td class="main">
                <?php echo tep_draw_input_field('amount', '', '', true); ?>
                <br/>
                <strong><?php echo MODULE_PAYMENT_G2APAY_TEXT_REFUND_ONLINE_CHECKBOX; ?></strong>
                <?php echo tep_draw_checkbox_field('online', '', true); ?>
            </td>
        </tr>
        <tr>
            <td class="smallText" valign="top"><?php echo tep_draw_button(MODULE_PAYMENT_G2APAY_TEXT_REFUND_SUBMIT, 'disk', null, 'primary'); ?></td>
        </tr>
    </table>
    <?php echo tep_draw_hidden_field('_g2apay_refund_token', $g2apay_refund_token) ?>
</form>

<?php
require DIR_WS_INCLUDES . 'template_bottom.php';
require DIR_WS_INCLUDES . 'application_bottom.php';
?>