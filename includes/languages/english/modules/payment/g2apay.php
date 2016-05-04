<?php

define('MODULE_PAYMENT_G2APAY_TEXT_TITLE', 'G2A Pay Payment Gateway');
define('MODULE_PAYMENT_G2APAY_TEXT_PUBLIC_TITLE', 'G2A Pay');
define('MODULE_PAYMENT_G2APAY_TEXT_DESCRIPTION', '<img src="images/icon_info.gif" border="0" />&nbsp;<a href="https://pay.g2a.com/" target="_blank" style="text-decoration: underline; font-weight: bold;">G2A Pay</a> Payment Gateway');
define('MODULE_PAYMENT_G2APAY_ERROR_ADMIN_CURL', 'cURL is required.');
define('MODULE_PAYMENT_G2APAY_ERROR_ADMIN_API_CREDENTIALS', 'Missing API credentials');
define('MODULE_PAYMENT_G2APAY_ERROR_ADMIN_MERCHANT_EMAIL', 'Invalid merchant email');
define('MODULE_PAYMENT_G2APAY_ERROR_ADMIN_CONFIGURATION', 'Missing required configuration options.');

define('MODULE_PAYMENT_G2APAY_ERROR_INVALID_TOKEN', 'Invalid security token. Please try again');

define('MODULE_PAYMENT_G2APAY_TEXT_CREATE_QUOTE_SUCCESS', 'We are processing your payment.');
define('MODULE_PAYMENT_G2APAY_TEXT_CREATE_QUOTE_CANCEL', 'Your order will be cancelled.');
define('MODULE_PAYMENT_G2APAY_ERROR_CREATE_QUOTE', 'Error processing payment.');

define('MODULE_PAYMENT_G2APAY_ERROR_GATEWAY_INVALID_DATA', 'Invalid data provided.');
define('MODULE_PAYMENT_G2APAY_ERROR_GATEWAY_ORDER_NOT_FOUND', 'Order not found.');
define('MODULE_PAYMENT_G2APAY_ERROR_GATEWAY_FORBIDDEN', 'Forbidden access.');

define('MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_SUCCESS', 'Order payment success');
define('MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_CANCEL', 'Order cancelled');
define('MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_INVALID_ACTION', 'Invalid action');
define('MODULE_PAYMENT_G2APAY_TEXT_GATEWAY_STATUS_CANCEL', 'Order cancelled by user');

define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_TITLE', 'Refund order #%s');
define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_INPUT', 'Refund');
define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_SUBMIT', 'Refund');
define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_ONLINE_CHECKBOX', 'Make online refund');
define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_SUCCESS_ONLINE', 'Order refunded online');
define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_SUCCESS_OFFLINE', 'Order refunded offline');
define('MODULE_PAYMENT_G2APAY_TEXT_REFUND_ORDER_TOTAL', 'Refund (%s):');

define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_UNKNOWN_ACTION', 'Action not found');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_NOT_FOUND', 'Order not found');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_NOT_ALLOWED', 'This order cannot be refunded');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_ONLINE_NOT_STORED', 'Refund was send but not stored locally');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_ONLINE_FAILED', 'Refund online failed');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_MISSING_TRANSACTION', 'Cannot refund order. Transaction not found.');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_OFFLINE_FAILED', 'Refund failed');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_REQUIRED_AMOUNT', 'Refund amount is required');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_INVALID_AMOUNT', 'Refund amount must be positive number');
define('MODULE_PAYMENT_G2APAY_ERROR_REFUND_TOO_BIG_AMOUNT', '%.2f cannot be refunded. Maximum allowed amount is %.2f');

define('MODULE_PAYMENT_G2APAY_ERROR_IPN_NOT_FOUND', 'Order not found');
define('MODULE_PAYMENT_G2APAY_ERROR_IPN_INVALID_HASH', 'Invalid request hash');
define('MODULE_PAYMENT_G2APAY_ERROR_IPN_INVALID_DATA', 'Invalid request data');
define('MODULE_PAYMENT_G2APAY_ERROR_IPN_NOT_ACTIVE', 'Payment method is not active');
define('MODULE_PAYMENT_G2APAY_ERROR_IPN_EMPTY_DATA', 'Empty data provided');
define('MODULE_PAYMENT_G2APAY_ERROR_IPN_INVALID_SECRET', 'Invalid secret provided');

define('MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_COMPLETED', 'IPN order completed with transaction id: %s');
define('MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_PENDING', 'IPN order pending with transaction id: %s');
define('MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_REJECTED', 'IPN order rejected with transaction id: %s');
define('MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_CANCELLED', 'IPN order cancelled with transaction id: %s');
define('MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_REFUNDED', 'IPN order refunded by %.2f with transaction id: %s');
define('MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_PARTIALLY_REFUNDED', 'IPN order partially refunded by %.2f with transaction id: %s');

define('MODULE_PAYMENT_G2APAY_LOG_TITLE', 'G2A Pay Log Message');
define('MODULE_PAYMENT_G2APAY_LOG_MESSAGE', 'MESSAGE');
define('MODULE_PAYMENT_G2APAY_LOG_TIME', 'TIME');
define('MODULE_PAYMENT_G2APAY_LOG_IP', 'IP');
define('MODULE_PAYMENT_G2APAY_LOG_DATA', 'DATA');
