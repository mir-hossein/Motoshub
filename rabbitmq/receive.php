<?php

define('_OW_', true);

define('DS', DIRECTORY_SEPARATOR);

define('OW_DIR_ROOT', substr(dirname(__FILE__), 0, - strlen('rabbitmq')));

define('OW_CRON', true);

require_once(OW_DIR_ROOT . 'ow_includes' . DS . 'init.php');

OW::getRouter()->setBaseUrl(OW_URL_HOME);

date_default_timezone_set(OW::getConfig()->getValue('base', 'site_timezone'));
OW_Auth::getInstance()->setAuthenticator(new OW_SessionAuthenticator());

OW::getPluginManager()->initPlugins();
$event = new OW_Event(OW_EventManager::ON_PLUGINS_INIT);
OW::getEventManager()->trigger($event);

if (!defined('RABBIT_HOST') || !defined('RABBIT_PORT') && !defined('RABBIT_USER') || !defined('RABBIT_PASSWORD') ) {
    return;
}

$rabbitConnection = new \PhpAmqpLib\Connection\AMQPStreamConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASSWORD);
$channel = $rabbitConnection->channel();
$queueName = 'queue';
if (defined('RABBIT_QUEUE_NAME')) {
    $queueName = RABBIT_QUEUE_NAME;
}
$channel->queue_declare($queueName, false, false, false, false);

$channelCallback = function ($msg) {
    echo 'receive:' . strftime("%y/%m/%#d, %H:%M", time()) . "\n";
    OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_RABITMQ_QUEUE_RELEASE, array(), $msg));
};

echo 'listening on queue:' . $queueName . "\n";
$channel->basic_consume($queueName, '', false, true, false, false, $channelCallback);

while (count($channel->callbacks)) {
    $channel->wait();
}
