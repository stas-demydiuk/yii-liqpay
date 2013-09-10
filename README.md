LiqPay API extension for Yii Framework
==================

This extension allows you to use [LiqPay API](https://liqpay.com/?do=pages&p=api) in Yii.

###Resources
* [LiqPay](https://liqpay.com/)
* [Report a bug](https://github.com/4you4ever/yii-liqpay/issues)

###Requirements
* Yii 1.0 or above

###Installation
* Extract the release file under `protected/extensions`.
* Add the following to your config file 'components' section:

```php
<?php
    'liqpay' => array(
        'class' => 'ext.LiqPay',
        
        // All parameters below are optional, change them to your needs
        'merchant' => 'i0000000000',
        'sendSign' => '',
        'otherSign' => ''
    ),
```

###Usage example

```php
<?php
    Yii::app()->liqpay->sendToPhone('ORDER_1', '380661234567', '10', 'UAH', 'Payment description');
    $balance = Yii::app()->liqpay->viewBalance();
```
