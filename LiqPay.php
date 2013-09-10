<?php

class LiqPay extends CApplicationComponent
{

    /**
     * ID мерчанта
     * @var string
     */
    public $merchant = 'i0000000000';

    /**
     * Подпись для операции send money API LiqPay
     * @var string
     */
    public $sendSign = '';

    /**
     * Подпись для остальных операций
     * @var string
     */
    public $otherSign = '';

    /**
     * Версия API
     * @var string
     */
    public $version = '1.2';

    /**
     * Перевод денег
     * @param string $kind Тип перевода (phone|card)
     * @param string $orderId Номер счета
     * @param string $recipient Получатель
     * @param double $amount Сумма перевода
     * @param string $currency Валюта перевода
     * @param string $description Описание
     * @return SimpleXMLElement
     * @see https://liqpay.com/?do=pages&p=api
     */
    public function sendMoney($kind, $orderId, $recipient, $amount, $currency, $description)
    {
        return $this->_send($this->sendSign, array(
                    'action' => 'send_money',
                    'kind' => $kind,
                    'order_id' => $orderId,
                    'to' => $recipient,
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => $description
        ));
    }

    public function sendToPhone($orderId, $recipient, $amount, $currency, $description)
    {
        return $this->sendMoney('phone', $orderId, $recipient, $amount, $currency, $description);
    }

    public function sendToCard($orderId, $recipient, $amount, $currency, $description)
    {
        return $this->sendMoney('card', $orderId, $recipient, $amount, $currency, $description);
    }

    /**
     * Текущий баланс
     * @return SimpleXMLElement
     * @see https://liqpay.com/?do=pages&p=api
     */
    public function viewBalance()
    {
        return $this->_send($this->otherSign, array(
                    'action' => 'view_balance'
        ));
    }

    /**
     * Просмотр данных транзакции
     * @param int $transactionId Идентификатор транзакции
     * @param string $transactionOrderId Номер счета
     * @return SimpleXMLElement
     * @see https://liqpay.com/?do=pages&p=api
     */
    public function viewTransaction($transactionId, $transactionOrderId)
    {
        return $this->_send($this->otherSign, array(
                    'action' => 'view_transaction',
                    'transaction_id' => $transactionId,
                    'transaction_order_id' => $transactionOrderId
        ));
    }

    /**
     * Пополнение оператора мобильной связи
     * @param string $orderId
     * @param string $phone
     * @param double $amount
     * @param string $currency
     * @return type
     */
    public function phoneCredit($orderId, $phone, $amount, $currency)
    {
        return $this->_send($this->otherSign, array(
                    'action' => 'phone_credit',
                    'amount' => $amount,
                    'currency' => $currency,
                    'phone' => $phone,
                    'order_id' => $orderId
        ));
    }

    /**
     * Отправка запроса к LiqPay
     * @param string $sign
     * @param array $params
     * @return SimpleXMLElement
     * @throws CException
     */
    private function _send($sign, $params = array())
    {
        $url = "https://www.liqpay.com/?do=api_xml";
        $xml = $this->_buildRequest($params);
        $sign = base64_encode(sha1($sign . $xml . $sign, 1));
        $xmlEncoded = base64_encode($xml);

        $operationEnvelope = '
            <operation_envelope>
                <operation_xml>' . $xmlEncoded . '</operation_xml>
                <signature>' . $sign . '</signature>
            </operation_envelope>';

        $post = '<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <request>
               <liqpay>' . $operationEnvelope . '</liqpay>
            </request>';

        $headers = array("POST /?do=api_xml HTTP/1.0",
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Content-length: " . strlen($post));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new CException(curl_error($ch), curl_errno($ch));
        }

        curl_close($ch);

        $responseXml = simplexml_load_string($response);
        $operationXml = simplexml_load_string(base64_decode($responseXml->liqpay->operation_envelope->operation_xml));

        if ($operationXml->status != 'success') {
            throw new CException($operationXml->response_description);
        }

        return $operationXml;
    }

    private function _buildRequest($params)
    {
        $params['version'] = $this->version;
        $params['merchant_id'] = $this->merchant;

        $xml = '<request>';
        foreach ($params as $param => $value) {
            $xml .= '<' . $param . '>' . $value . '</' . $param . '>';
        }
        $xml .= '</request>';

        return $xml;
    }

}
