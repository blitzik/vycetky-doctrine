<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IDatabaseBackupControlFactory;
use Nette\Application\UI\Presenter;

class BackupPresenter extends Presenter
{
    /**
     * @var IDatabaseBackupControlFactory
     * @inject
     */
    public $backupControlFactory;

    /**
     * @var array ['admin' => ... , 'system' => ...]
     */
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

    protected function createComponentDatabaseBackup()
    {
        $comp = $this->backupControlFactory->create($this->emails);

        return $comp;
    }
}