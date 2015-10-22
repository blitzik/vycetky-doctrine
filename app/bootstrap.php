<?php

require __DIR__ . '/../../../vendor/autoload.php'; // localhost
//require __DIR__ . '/../../../ven-new/autoload.php'; // temporary production

$configurator = new Nette\Configurator;

$configurator->enableDebugger(__DIR__ . '/../log');

//$configurator->setDebugMode(true);
//Tracy\Debugger::enable(Tracy\Debugger::DEVELOPMENT);

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../libs')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
