<?php

namespace App\Model\Components\Forms\Pdf;

use App\Model\Components\Forms\BaseFormContainer;

class ListingsPdfSettingsContainer extends BaseFormContainer
{
    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/listingsPdfSettings.latte');

        $template->_form = $this;

        $template->render();
    }



    public function configure()
    {
        $this->addCheckbox('isWageVisible', 'Zobrazit "Základní mzdu"')
                ->setDefaultValue(true);

        $this->addCheckbox('areOtherHoursVisible', 'Zobrazit "Ostatní hodiny"');
        $this->addCheckbox('areWorkedHoursVisible', 'Zobrazit "Odpracované hodiny"');
        $this->addCheckbox('areLunchHoursVisible', 'Zobrazit hodiny strávené obědem');
    }

}