<?php

namespace App\Model\Components;

use MessagesLoaders\IMessagesLoader;

interface IMessagesTableControlFactory
{
    /**
     * @param IMessagesLoader $loader
     * @return MessagesTableControl
     */
    public function create(IMessagesLoader $loader);
}