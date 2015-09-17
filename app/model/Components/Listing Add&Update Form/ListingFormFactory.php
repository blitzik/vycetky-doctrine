<?php

namespace App\Model\Components;

use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;
use Nette\Object;

class ListingFormFactory extends Object
{

    public function create()
    {
        $form = new Form();

        $form->addText('description', 'Popis výčetky:', 25, 40);

        $form->addText('hourlyWage', 'Základní mzda:', null, 4)
                ->addCondition(Form::FILLED)
                ->addRule(
                    Form::PATTERN,
                    'Do pole "základní mzda" lze vyplnit pouze kladná celá čísla.',
                    '\d+'
                );

        $form->addSelect('month', 'Měsíc:', TimeUtils::getMonths());

        $form->addSelect('year', 'Rok:', TimeUtils::generateYearsForSelect());

        $form->addSubmit('save', 'Vytvořit výčetku')
                ->setOmitted();

        $form->addProtection();

        return $form;
    }

}