<?php

class EveryPay
{
    const _VERIFY_SUCCESS = 1; // payment successful
    const _VERIFY_CANCEL = 2;  // payment cancelled
    const _VERIFY_FAIL = 3;    // payment failed

    private $api_username;
    private $api_secret;
    private $request_cc_token;
    private $cc_token;
    private $transaction_type;

    /**
      * Initiates a payment.
      *
      * Expects following data as input:
      * 'api_username' => api username,
      * 'api_secret => api secret,
      * array(
      *  'request_cc_token' => request to get cc_token 1,
      *  'cc_token' => token referencing a bank card
      * )
      *
      * @param $api_username string
      * @param $api_secret string
      * @param $data array
      * @example $everypay->init('api_username', 'api_secret', array('cc_token' => 'token'));
      *
      * @return EveryPay
    */

    public function init($api_username, $api_secret, $data = '')
    {
        $this->api_username = $api_username;
        $this->api_secret = $api_secret;

        if (isset($data['request_cc_token'])) {
          $this->request_cc_token = $data['request_cc_token'];
        }

        if (isset($data['cc_token'])) {
          $this->cc_token = $data['cc_token'];
        }

        if (isset($data['transaction_type'])) {
          $this->transaction_type = $data['transaction_type'];
        }
    }

    /**
      * Populates and returns array of fields to be submitted for payment.
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
      *  'hmac_fields' => string which contains all fields (keys) that are going to be used in hmac calculation, separated by comma,
      *  'request_cc_token' => request to get cc_token 1,
      *  'cc_token' => token referencing a bank card
      * );
      *
      * @param $data array
      * @param $language string
      *
      * @return array
    */

    public function getFields(array $data, $language = 'en')
    {
        $data['api_username'] = $this->api_username;
        $data['transaction_type'] = $this->transaction_type;
        $data['nonce'] = $this->getNonce();
        $data['timestamp'] = time();

        if (isset($this->request_cc_token)) {
            $data['request_cc_token'] = $this->request_cc_token;
        }

        if (isset($this->cc_token)) {
            $data['cc_token'] = $this->cc_token;
        }

        $keys = array_keys($data);
        // ensure that hmac_fields itself is mentioned in hmac_fields
        if (!in_array('hmac_fields', $keys)){
          $keys[] = 'hmac_fields';
        }
        asort($keys);
        $data['hmac_fields'] = implode(',', $keys);

        $data['hmac'] = $this->signData($this->serializeData($data));
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
      *  'account_id' => account id in EveryPay system,
      *  'amount' => amount to pay,
      *  'api_username' => api username,
      *  'nonce' => return nonce,
      *  'order_reference' => order reference number,
      *  'payment_reference' => payment reference number,
      *  'payment_state' => payment state,
      *  'timestamp' => timestamp,
      *  'transaction_result' => transaction result,
      *  'hmac_fields' => string which contains all fields (keys) that are going to be used in hmac calculation, separated by comma.
      * );
      *
      * for cancelled payments:
      *
      * array(
      *  'api_username' => api username,
      *  'nonce' => return nonce,
      *  'order_reference' => order reference number,
      *  'payment_state' => payment state,
      *  'timestamp' => timestamp,
      *  'transaction_result' => transaction result
      * );
      *

      * @param $data array
      *
      * @return int 1 - verified successful payment, 2 - cancelled, 3 - failed
      * @throws Exception
    */

    public function verify(array $data)
    {
        if ($data['api_username'] !== $this->api_username) {
            throw new Exception('Invalid username.');
        }

        $now = time();
        if (($data['timestamp'] > $now) || ($data['timestamp'] < ($now - 600))) {
            throw new Exception('Response outdated.');
        }

        /**
          * Refer to the Integration Manual for more information about 'order_reference' validation.
        */

        if (!$this->verifyNonce($data['nonce'])) {
            throw new Exception('Nonce is already used.');
        }

        /**
          * Refer to the Integration Manual for more information about 'nonce' uniqueness validation.
        */

        $status = $this->statuses($data['payment_state']);

        $verify = array();
        $hmac_fields = explode(',', $data["hmac_fields"]);

        foreach ($hmac_fields as $value) {
            $verify[$value] = isset($data[$value]) ? $data[$value] : '';
        }

        $hmac = $this->signData($this->serializeData($verify));
        if ($data['hmac'] != $hmac) {
            throw new Exception('Invalid HMAC.');
        }

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

    private function statuses($state) {
        switch ($state) {
            case 'settled':
            case 'authorised':
                return self::_VERIFY_SUCCESS;
            case 'cancelled':
            case 'waiting_for_3ds_response':
                return self::_VERIFY_CANCEL;
            case 'failed':
                return self::_VERIFY_FAIL;
        }
    }

    /**
      * Prepare data package for signing
      *
      * @param array $data
      * @return string
    */

    private function serializeData(array $data)
    {
        $arr = array();
        ksort($data);
        foreach ($data as $k => $v) {
            $arr[] = $k . '=' . $v;
        }
        return implode('&', $arr);
    }

    private function signData($data)
    {
        return hash_hmac('sha1', $data, $this->api_secret);
    }
}
