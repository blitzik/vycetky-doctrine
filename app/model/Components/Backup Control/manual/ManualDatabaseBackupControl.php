<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 28.12.2015
 */

namespace App\Model\Components;

use App\Model\Database\Backup\DatabaseBackup;
use App\Model\Database\Backup\Handlers\IDatabaseBackupHandler;
use App\Model\Subscribers\Results\ResultObject;
use Kdyby\Monolog\Logger;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class ManualDatabaseBackupControl extends Control
{
    /** @var array */
    public $onBeforeManualBackup;

    /** @var DatabaseBackup */
    private $databaseBackup;

    /** @var Logger */
    private $logger;


    public function __construct(
        DatabaseBackup $databaseBackup,
        Logger $logger
    ) {
        $this->databaseBackup = $databaseBackup;

        $this->logger = $logger->channel('manualDatabaseBackup');
    }


    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/manualBackup.latte');


        $template->render();
    }


    public function addBackupHandler(IDatabaseBackupHandler $backupHandler)
    {
        $this->databaseBackup->addHandler($backupHandler);
    }


    protected function createComponentBackupDatabaseForm()
    {
        $form = new Form();

        $form->addSubmit('backup', 'Provést zálohu')
            ->getControlPrototype()
            ->onClick = 'return confirm(\'Skutečně chcete provést zálohu databáze?\');';

        $form->onSuccess[] = [$this, 'processBackup'];

        $form->addProtection();

        return $form;
    }


    public function processBackup(Form $form, $values)
    {
        $this->onBeforeManualBackup();

        try {
            $results = $this->databaseBackup->backup('manual', true);

            $errorOccurred = false;
            /** @var ResultObject $result */
            foreach ($results as $result) {
                if (!$result->hasNoErrors()) {
                    foreach ($result->getAllErrors() as $error) {
                        $this->presenter->flashMessage($error['message'], $error['type']);
                    }
                    $errorOccurred = true;
                }
            }

            if ($errorOccurred === false) {
                $this->presenter->flashMessage('Databáze byla úspěšně zazálohována', 'success');
            }

        } catch (\Exception $e) {
            $this->logger->addError(sprintf('Manual database backup failure. %s', $e));
            $this->presenter->flashMessage('Databázi se nepodařilo zazálohovat. Zkontrolujte logy.', 'error');
        }

        $this->redirect('this');
    }
}


interface IManualDatabaseBackupControlFactory
{
    /**
     * @return ManualDatabaseBackupControl
     */
    public function create();
}