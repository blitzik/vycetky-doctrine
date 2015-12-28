<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 28.12.2015
 */

namespace App\Model\Database\Backup;

use Nette\Object;
use Nette\Utils\Strings;

class DatabaseBackupFile extends Object
{
    /** @var string */
    private $storagePath;

    /** @var string */
    private $namePrefix;

    private $date;

    public function __construct($storagePath)
    {
        $this->storagePath = $storagePath;
        $this->date = new \DateTimeImmutable('now');
    }


    public function setNamePrefix($prefix)
    {
        $this->namePrefix = Strings::webalize($prefix);
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        $p = $this->namePrefix !== null ? $this->namePrefix.'-' : null;
        return $p . $this->date->format('Y-m-d-H-m-s') . '.sql';
    }


    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->storagePath .'/'. $this->getFileName();
    }


    /**
     * @return \DateTimeImmutable
     */
    public function getBackupDate()
    {
        return $this->date;
    }
}