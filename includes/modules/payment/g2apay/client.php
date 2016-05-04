<?php

$g2apay_base = dirname(__FILE__);
require_once $g2apay_base . '/functions.php';

/**
 * G2A Pay request client.
 */
class g2apay_client
{
    public $url, $method, $_headers;

    public function g2apay_client($url)
    {
        $this->url    = $url;
        $this->method = 'POST';

        $this->_headers = [];
    }

    public function add_header($name, $value)
    {
        $this->_headers[] = "{$name}:{$value}";
    }

    public function send_request($data = null)
    {
        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if (strcasecmp($this->method, 'POST') === 0) {
            curl_setopt($curl, CURLOPT_POST, true);
        } elseif (strcasecmp($this->method, 'GET') !== 0 && !empty($this->method)) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        }

        if (count($this->_headers) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
        }

        if (!empty($data)) {
            $post_params = is_array($data) ? $this->build_query_string($data) : $data;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_params);
        }

        if (strcasecmp(MODULE_PAYMENT_G2APAY_VERIFY_SSL, 'true') === 0) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $response = curl_exec($curl);

        $result = g2apay_json_decode($response);

        if (is_array($result)) {
            return $result;
        } else {
            return $response;
        }
    }

    public function build_query_string($data, $prefix = '')
    {
        if (function_exists('http_build_query')) {
            return http_build_query($data);
        }

        if (is_array($data)) {
            $output = [];
            foreach ($data as $key => $value) {
                $fullKey     = strlen($prefix) ? "{$prefix}[{$key}]" : $key;
                $stringValue = $this->build_query_string($value, $fullKey);
                $output[]    = "{$fullKey}={$stringValue}";
            }

            return implode('&', $output);
        } else {
            return urlencode(utf8_encode(trim($data)));
        }
    }
}
