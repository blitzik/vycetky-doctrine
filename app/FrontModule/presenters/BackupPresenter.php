<?php

namespace App\FrontModule\Presenters;

use App\Model\Authorization\Authorizator;
use App\Model\Components\IDatabaseBackupControlFactory;
use App\Model\Components\IManualDatabaseBackupControlFactory;
use Nette\Application\UI\Presenter;

class BackupPresenter extends Presenter
{
    /**
     * @var IManualDatabaseBackupControlFactory
     * @inject
     */
    public $manualBackupFactory;

    /**
     * @var IDatabaseBackupControlFactory
     * @inject
     */
    public $backupControlFactory;

    /** @var Authorizator */
    private $authorizator;


    public function setAuthorizator(Authorizator $authorizator)
    {
        $this->authorizator = $authorizator;
    }


    public function renderDatabaseBackup()
    {
    }


    public function actionDatabaseBackup()
    {
    }


    /**
     * @Actions databaseBackup
     */
    protected function createComponentDatabaseBackup()
    {
        $comp = $this->backupControlFactory->create();

        return $comp;
    }

}