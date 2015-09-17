<?php

namespace App\Model\Components;

interface IDatabaseBackupControlFactory
{
    /**
     * @param array $emails
     * @return DatabaseBackupControl
     */
    public function create(array $emails);
}