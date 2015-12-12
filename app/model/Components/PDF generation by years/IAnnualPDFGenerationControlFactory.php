<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;

interface IAnnualPDFGenerationControlFactory
{
    /**
     * @param User $user
     * @return AnnualPDFGenerationControl
     */
    public function create(User $user);
}