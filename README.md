You'll find the integration documentation from our website: https://every-pay.com/documentation-overview/
### Composer

Once composer is [installed](https://getcomposer.org/doc/00-intro.md), add to your composer.json:

1. To repositories array:
```json
"repositories": [
        {"type": "git", "url": "https://github.com/UnifiedPaymentSolutions/everypay-integration"}
]
```
2. To require list:
```json
"require": {
        "UnifiedPaymentSolutions/everypay-integration": "~1.0"
    }
```

Then execute: 
```bash
composer install
```

NB
If you already have composer.lock file, then execute:
```bash
composer update
```