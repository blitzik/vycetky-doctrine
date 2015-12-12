<?php

namespace App\Model\Components\Forms\Pdf;

use App\Model\Components\Forms\BaseFormContainer;

class UserPdfSettingsContainer extends BaseFormContainer
{
    /** @var array */
    private $companyParameters;



    public function __construct($companyParameters)
    {
        parent::__construct();

        $this->companyParameters = $companyParameters;
    }



    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/userSettingsContainer.latte');

        $template->_form = $this;

        $template->render();
    }



    public function configure()
    {
        $this->addText('employer', 'Zaměstnavatel:', 25, 70)
                ->setDefaultValue($this->companyParameters['name']);

        $this->addText('name', 'Jméno:', 25, 70);
    }
}