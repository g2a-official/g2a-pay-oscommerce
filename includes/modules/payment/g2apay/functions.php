<?php


function g2apay_hash($string)
{
    return hash('sha256', $string);
}

function g2apay_json_decode($string)
{
    return json_decode($string, true);
}

function g2apay_round($amount)
{
    return round($amount, 2);
}

function g2apay_random_token()
{
    return md5(uniqid(rand(), true) . time());
}
