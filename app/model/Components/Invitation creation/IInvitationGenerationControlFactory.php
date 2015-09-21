<?php

namespace App\Model\Components;

interface IInvitationGenerationControlFactory
{
    /**
     * @return InvitationGenerationControl
     */
    public function create();
}