<?php

namespace App\Model\Components;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;

class FilterControl extends Control
{

    protected function createComponentForm()
    {
        $form = new Form();

        $form->addSelect('year', '', TimeUtils::generateYearsForSelect());

        $form->addSelect('month', '', TimeUtils::getMonths())
            ->setPrompt('Celý rok');

        $form->addSubmit('filter', 'Zobraz výčetky')
            ->setOmitted();

        $form->getElementPrototype()->id = 'form-filter';

        $form->onSuccess[] = $this->processFilter;

        return $form;
    }

    public function processFilter(Form $form, $values)
    {
        $this->presenter->redirect('Listing:overview', (array) $values);
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $template->render();
    }
}