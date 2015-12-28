<?php

namespace App\Model\Components;

interface IDatabaseBackupControlFactory
{
    /**
     * @return DatabaseBackupControl
     */
    public function create();
}