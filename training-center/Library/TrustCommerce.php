<?php

namespace AppBundle\Library;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class TrustCommerce
{
    private $url;

    private $custid;

    private $portal_password;

    private $api_password;

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;

        $this->url = $this->container->getParameter('tc_url');
        $this->custid = $this->container->getParameter('tc_custid');
        $this->portal_password = $this->container->getParameter('tc_portal_pass');
        $this->api_password = $this->container->getParameter('tc_api_pass');
    }

    public function generateToken()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://vault.trustcommerce.com/trustee/token.php?custid=' . $this->custid . '&password=' . $this->api_password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function verify($transid)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://vault.trustcommerce.com/query/?custid=' . $this->custid . '&password=' . $this->portal_password . '&querytype=summary&format=text&transid=' . $transid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $raw_results = curl_exec($ch);
        curl_close($ch);

        //print_r($raw_results);

        $results_as_array = explode("\n", $raw_results);

        //print_r($results_as_array);

        $keys = explode(',', $results_as_array[0]);
        $keys = array_map('trim', $keys);

        //print_r($keys);

        $values = explode(',', $results_as_array[1]);
        $values = array_map('trim', $values);

        //print_r($values);

        $combined = array_combine($keys, $values);

        //print_r($combined);

        return $combined;
    }

    public function send($amount, $cc, $exp_month, $exp_year)
    {
        $fields = [
            'custid'   => urlencode($this->custid),
            'password' => urlencode($this->api_password),
            'action'   => urlencode('sale'),
            'amount'   => urlencode($amount * 100),
            'cc'       => urlencode($cc),
            'exp'      => urlencode($exp_month . $exp_year),
            'demo'     => urlencode("y"),
        ];

        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        $result = str_replace(PHP_EOL, '&', $result);
        parse_str($result, $result);

        return $result;
    }
}
