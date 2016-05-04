<?php

define('MODULE_PAYMENT_G2APAY_CONFIG_CREATE_QUOTE_URL_LIVE', 'https://checkout.pay.g2a.com/index/createQuote');
define('MODULE_PAYMENT_G2APAY_CONFIG_CREATE_QUOTE_URL_SANDBOX', 'https://checkout.test.pay.g2a.com/index/createQuote');

define('MODULE_PAYMENT_G2APAY_CONFIG_CHECKOUT_GATEWAY_URL_LIVE', 'https://checkout.pay.g2a.com/index/gateway');
define('MODULE_PAYMENT_G2APAY_CONFIG_CHECKOUT_GATEWAY_URL_SANDBOX', 'https://checkout.test.pay.g2a.com/index/gateway');

define('MODULE_PAYMENT_G2APAY_CONFIG_REST_URL_LIVE', 'https://pay.g2a.com/rest');
define('MODULE_PAYMENT_G2APAY_CONFIG_REST_URL_SANDBOX', 'https://www.test.pay.g2a.com/rest');

class g2apay_config
{
    public function g2apay_config()
    {
        $this->api_hash   = MODULE_PAYMENT_G2APAY_API_HASH;
        $this->api_secret = MODULE_PAYMENT_G2APAY_API_SECRET;
    }

    public function is_sandbox()
    {
        return strcasecmp(MODULE_PAYMENT_G2APAY_ENVIRONMENT, 'Sandbox') === 0;
    }

    public function get_api_hash()
    {
        return MODULE_PAYMENT_G2APAY_API_HASH;
    }

    public function get_api_secret()
    {
        return MODULE_PAYMENT_G2APAY_API_SECRET;
    }

    public function get_merchant_email()
    {
        return MODULE_PAYMENT_G2APAY_MERCHANT_EMAIL;
    }

    public function get_authorization_hash()
    {
        $string = $this->get_api_hash() . $this->get_merchant_email() . $this->get_api_secret();

        return g2apay_hash($string);
    }

    public function get_create_quote_url()
    {
        return $this->is_sandbox() ? MODULE_PAYMENT_G2APAY_CONFIG_CREATE_QUOTE_URL_SANDBOX : MODULE_PAYMENT_G2APAY_CONFIG_CREATE_QUOTE_URL_LIVE;
    }

    public function get_checkout_gateway_url($token)
    {
        $baseUrl = $this->is_sandbox() ? MODULE_PAYMENT_G2APAY_CONFIG_CHECKOUT_GATEWAY_URL_SANDBOX : MODULE_PAYMENT_G2APAY_CONFIG_CHECKOUT_GATEWAY_URL_LIVE;

        return "{$baseUrl}?token={$token}";
    }

    public function get_rest_url($path = '')
    {
        $baseUrl = $this->is_sandbox() ? MODULE_PAYMENT_G2APAY_CONFIG_REST_URL_SANDBOX : MODULE_PAYMENT_G2APAY_CONFIG_REST_URL_LIVE;
        $path    = ltrim($path, '/');

        return "{$baseUrl}/{$path}";
    }

    public function enable_log()
    {
        return tep_not_null(MODULE_PAYMENT_G2APAY_LOG_EMAIL) && tep_validate_email(MODULE_PAYMENT_G2APAY_LOG_EMAIL);
    }

    public function get_log_email()
    {
        return MODULE_PAYMENT_G2APAY_LOG_EMAIL;
    }

    public function has_ipn_secret()
    {
        return tep_not_null($this->get_ipn_secret());
    }

    public function get_ipn_secret()
    {
        return MODULE_PAYMENT_G2APAY_IPN_SECRET;
    }
}
