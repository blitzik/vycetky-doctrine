<?php

//php vendor\nette\tester\src\tester.php -c w:subdom\vycetky-doctrine\tests\php.ini w:subdom\vycetky-doctrine\tests\unit\Types\InvoiceTime.phpt

require __DIR__ . '/../../../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

$configurator = new Nette\Configurator;

//$configurator->enableDebugger(__DIR__ . '/log');
$configurator->setTempDirectory(__DIR__ . '/temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/../app')
    ->addDirectory(__DIR__ . '/../libs')
    ->register();

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/config.test.local.neon');

$container = $configurator->createContainer();

\Tester\Helpers::purge(__DIR__ . '/log');


return $container;