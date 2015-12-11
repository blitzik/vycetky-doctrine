<?php

namespace App\Model\Components;

interface IAnnualPDFGenerationControlFactory
{
    /**
     * @return AnnualPDFGenerationControl
     */
    public function create();
}