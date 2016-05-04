<?php

chdir('../../../../');
require 'includes/application_top.php';
require DIR_WS_CLASSES . 'order.php';

$g2apay_base = 'includes/modules/payment/g2apay';
require_once $g2apay_base . '/ipn.php';
require_once $g2apay_base . '/log.php';

include DIR_FS_CATALOG . '/includes/languages/' . $language . '/modules/payment/g2apay.php';

$secret = isset($HTTP_GET_VARS['secret']) ? $HTTP_GET_VARS['secret'] : '';
$data   = $HTTP_POST_VARS;

$log = new g2apay_log();
$ipn = new g2apay_ipn();

if ($ipn->validate_secret($secret)
    && $ipn->validate_data($data)
    && $ipn->process_data($data)) {
    echo 'ok';
} else {
    $message = $ipn->get_error();
    $log->error($message, [
        'request' => $HTTP_POST_VARS,
    ]);
    echo $message;
}

tep_session_destroy();
