<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 27.12.2015
 */

namespace App\Model\Database\Backup\Handlers;

use App\Model\Database\Backup\DatabaseBackupFile;

interface IDatabaseBackupHandler
{
    public function process(DatabaseBackupFile $file);
}