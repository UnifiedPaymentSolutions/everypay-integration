<?php

class Everypay
{
    const _VERIFY_SUCCESS = 1; // payment successful
    const _VERIFY_CANCEL = 2;  // payment cancelled
    const _VERIFY_FAIL = 3;    // payment failed

    private $api_username;
    private $api_secret;
    private $statuses = array(
        'completed' => self::_VERIFY_SUCCESS,
        'cancelled' => self::_VERIFY_CANCEL,
        'failed'    => self::_VERIFY_FAIL
    );

    public function init($api_username, $api_secret)
    {
        $this->api_username = $api_username;
        $this->api_secret = $api_secret;
    }

    /**
      * Populates and returns array of fields to be submitted
      * for payment.
      *
      * Expects following data as input:
      * array(
      *  'account_id' => account id,
      *  'amount' => amount to pay,
      *  'billing_address' => billing address street name,
      *  'billing_city' => billing address city name,
      *  'billing_country' => billing address 2 letter country code,
      *  'billing_postcode' => billing address postal code,
      *  'callback_url' => callback url,
      *  'customer_url' => return url,
      *  'delivery_address' => shipping address street name,
      *  'delivery_city' => shipping address city name,
      *  'delivery_country' => shipping address 2 letter country code,
      *  'delivery_postcode' => shipping address postal code,
      *  'email' => customer email address,
      *  'order_reference' => order reference number,
      *  'user_ip' => user ip address,
      *  'hmac_fields' => string which contains all fields (keys) that are going to be used in hmac calculation, separated by comma.
      * );
      *
      * @param $data array
      * @param $language string
      *
      * @return Array
    */

    public function getFields(array $data, $language = 'en', $hmac_fields = false)
    {
        $data['api_username'] = $this->api_username;
        $data['nonce'] = $this->getNonce();
        $data['timestamp'] = time();
        $data['transaction_type'] = 'authorisation';

        if ($hmac_fields == true) {
            $data['hmac_fields'] = implode(',', array_keys($data)) . ",hmac_fields";
        }

        $data['hmac'] = $this->signData($this->prepareData($data));
        $data['locale'] = $language;

        return $data;
    }

    /**
      * Verifies return data
      *
      * Expects following data as input:
      *
      * for successful and failed payments:
      *
      * array(
      *  'account_id' => account id in Everypay system
      *  'amount' => amount to pay,
      *  'api_username' => api username,
      *  'nonce' => return nonce
      *  'order_reference' => order reference number,
      *  'payment_reference' => payment reference number,
      *  'payment_state' => payment state,
      *  'timestamp' => timestamp,
      *  'transaction_result' => transaction result
      * );
      *
      * for cancelled payments:
      *
      * array(
      *  'api_username' => api username,
      *  'nonce' => return nonce
      *  'order_reference' => order reference number,
      *  'payment_state' => payment state,
      *  'timestamp' => timestamp,
      *  'transaction_result' => transaction result
      * );
      *

      * @param $data array
      *
      * @return int 1 - verified successful payment, 2 - verified cancelled payment, 3 - verified failed payment
      * @throws Exception
    */

    public function verify(array $data)
    {
        if ($data['api_username'] !== $this->api_username)
            throw new Exception('Invalid username.');

        $now = time();
        if (($data['timestamp'] > $now) || ($data['timestamp'] < ($now - 600)))
            throw new Exception('Response outdated.');

        //  if ($data['order_reference'] != $this->getOrderReference())
        //      throw new Exception('Order number doesn\'t match.');

        /**
          * Refer to the Integration Manual for more information about 'order_reference' validation.
        */

        if (!$this->verifyNonce($data['nonce']))
            throw new Exception('Nonce is already used.');

        /**
          * Refer to the Integration Manual for more information about 'nonce' uniqueness validation.
        */

        $status = $this->statuses[$data['transaction_result']];
        // common data
        $verify = array(
            'api_username'        => $data['api_username'],
            'nonce'               => $data['nonce'],
            'order_reference'     => $data['order_reference'],
            'payment_state'       => $data['payment_state'],
            'timestamp'           => $data['timestamp'],
            'transaction_result'  => $data['transaction_result'],
            'account_id'          => $data['account_id'],
            'amount'              => $data['amount'],
            'payment_reference'   => $data['payment_reference'],
            'cc_last_four_digits' => $data['cc_last_four_digits']
        );

        switch ($data['transaction_result'])
        {
            case 'completed':
            case 'failed':
                // only in automatic callback message
                if (isset($data['processing_errors'])) {
                    $verify['processing_errors'] = $data['processing_errors'];
                }

                if (isset($data['processing_warnings'])) {
                    $verify['processing_warnings'] = $data['processing_warnings'];
                }
                break;
            case 'cancelled':
                break;
        }

        $hmac = $this->signData($this->prepareData($verify));
        if ($data['hmac'] != $hmac)
            throw new Exception('Invalid HMAC.');

        return $status;
    }

    protected function getNonce()
    {
        return uniqid(true);
    }

    protected function verifyNonce($nonce)
    {
        return true;
    }

    //  abstract protected function getOrderReference();

    /**
      * Prepare data package for signing
      *
      * @param array $data
      * @return string
    */

    private function prepareData(array $data)
    {
        $arr = array();
        ksort($data);
        foreach ($data as $k => $v)
        {
            $arr[] = $k . '=' . $v;
        }
        return implode('&', $arr);
    }

    private function signData($data)
    {
        return hash_hmac('sha1', $data, $this->api_secret);
    }
}
