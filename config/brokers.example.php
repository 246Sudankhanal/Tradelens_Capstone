<?php
// Copy this file to brokers.php and paste your MetaApi token.
define('BROKER_SYNC_ENABLED', true);
define('BROKER_SYNC_SECRET',  'YOUR_BROKER_SYNC_CRON_SECRET');
define('BROKER_SYNC_LOOKBACK_DAYS', 90);

$BROKER_CREDENTIALS = [
    'metatrader5' => [
        'label'     => 'MetaTrader 5',
        'enabled'   => true,
        'api_token' => 'YOUR_METAAPI_TOKEN',
    ],
    'metatrader4' => [
        'label'     => 'MetaTrader 4',
        'enabled'   => true,
        'api_token' => 'YOUR_METAAPI_TOKEN',
    ],
];
