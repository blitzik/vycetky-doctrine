<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IDatabaseBackupControlFactory;
use App\Model\Authorization\Authorizator;
use Nette\Application\UI\Presenter;

class BackupPresenter extends SecurityPresenter
{
    /**
     * @var IDatabaseBackupControlFactory
     * @inject
     */
    public $backupControlFactory;

    /**
     * @var Authorizator
     * @inject
     */
    public $authorizator;

    /** @var array ['admin' => ... , 'system' => ...] */
    private $emails;

    public function setEmails(array $emails)
    {
        $this->emails = $emails;
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
        $comp = $this->backupControlFactory->create($this->emails);

        return $comp;
    }
}