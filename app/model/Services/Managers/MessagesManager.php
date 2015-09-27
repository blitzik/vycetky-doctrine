<?php

namespace App\Model\Services\Managers;

use App\Model\Services\Readers\MessagesReader;
use Nette\Object;

class MessagesManager extends Object
{
    /**
     * @var MessagesReader
     */
    private $messagesReader;

    public function __construct(
        MessagesReader $messagesReader
    ) {
        $this->messagesReader = $messagesReader;
    }
}