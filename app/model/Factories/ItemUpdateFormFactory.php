<?php

namespace App\Model\Components;

use Nette\Application\UI\Form;
use Nette\Utils\Json;

class ItemUpdateFormFactory
{
    const BTN_TIME_ADD_CLASS = 'btn-time-control-add';
    const BTN_TIME_SUB_CLASS = 'btn-time-control-sub';

    const OTHER_HOURS_ZERO_TIME_ERROR_MSG = 'Abyste mohli napsat komentář k
                                             ostatním hodinám, musíte nastavit
                                             pole "ostatní hodiny" na hodnotu
                                             větší než 0.';

    /**
     * @var array
     */
    private $defaultItemTime;

    public function __construct(array $defaultItemTime)
    {
        $this->defaultItemTime = $defaultItemTime;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getDefaultTimeValue($name)
    {
        return $this->defaultItemTime[$name];
    }

    public function create()
    {
        $form = new Form();

        $form->addText('workStart', 'Začátek', 3, 5)
                ->setRequired('Vyplňte pole "Začátek prac. doby".')
                ->setDefaultValue($this->defaultItemTime['workStart'])
                ->setHtmlId('workStart')
                ->setAttribute('class', 'input-time')
                ->addRule(
                    Form::PATTERN,
                    'Do pole "Začátek prac. doby" lze zadat pouze
                     čas v 24 hodinovém formátu po půlhodinách.',
                    '([01]?[0-9]|2[0-3]):(0|3)0'
                );

        $form->addText('workEnd', 'Konec', 3, 5)
                ->setRequired('Vyplňte pole "Konec prac. doby".')
                ->setDefaultValue($this->defaultItemTime['workEnd'])
                ->setHtmlId('workEnd')
                ->setAttribute('class', 'input-time')
                ->addRule(
                    Form::PATTERN,
                    'Do pole "Konec prac. doby" lze zadat pouze
                     čas v 24 hodinovém formátu po půlhodinách.',
                    '([01]?[0-9]|2[0-3]):(0|3)0'
                );

        $form->addText('lunch', 'Oběd', 3, 5)
                ->setRequired('Vyplňte pole "Oběd".')
                ->setDefaultValue($this->defaultItemTime['lunch'])
                ->setHtmlId('lunch')
                ->setAttribute('class', 'input-time')
                ->addRule(
                    Form::PATTERN,
                    'Do pole "Oběd" lze zadat pouze
                     čas v ve formátu s čárkou. (např. 1 nebo 1,5)',
                    '^(0|[1-9]|1[0-9]|2[0-3])(,(0|5))?$'
                );

        $form->addText('otherHours', 'Ostatní hod.')
                ->setDefaultValue($this->defaultItemTime['otherHours'])
                ->setHtmlId('otherHours')
                ->setAttribute('class', 'input-time')
                ->addCondition(Form::FILLED)
                ->addRule(
                    Form::PATTERN,
                    'Do pole "Ostaní hodiny" lze zadat pouze
                     čas ve formátu s čárkou.(např. 6 nebo 6,5)',
                    '^(0|[1-9]|1[0-9]|2[0-3])(,(0|5))?$'
                );

        $form->addText('locality', 'Místo pracoviště', 28, 40)
                ->setRequired('Vyplňte pole "Místo pracoviště".')
                ->setHtmlId('locality')
                ->getControlPrototype()->class = 'item-text-input';

        $form->addText('description', 'Popis práce', 28, 30)
                ->getControlPrototype()->class = 'item-text-input';

        $form->addText('descOtherHours', 'Komentář k ostat. hod. (např. svátek)', 28, 30)
                ->addConditionOn($form['otherHours'], Form::FILLED)
                ->addCondition(Form::FILLED)
                ->addRule(
                    function($item, $arg){
                        return (\InvoiceTime::processTime($arg) == '00:00:00') ?
                            false :
                            true;
                    },
                    self::OTHER_HOURS_ZERO_TIME_ERROR_MSG,
                    $form['otherHours']
                );

        $form['descOtherHours']->getControlPrototype()->class = 'item-text-input';

        // time control buttons

        $form->addButton('workStartAdd', '+')
                ->setAttribute('class', self::BTN_TIME_ADD_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['workStart']->control->attrs['id'],
                        'slider' => 'slider_range',
                        'pos' => 0,
                        'val' => -30
                    ])
                );

        $form->addButton('workStartSub', '-')
                ->setAttribute('class', self::BTN_TIME_SUB_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['workStart']->control->attrs['id'],
                        'slider' => 'slider_range',
                        'pos' => 0,
                        'val' => 30
                    ])
                );

        $form->addButton('workEndAdd', '+')
                ->setAttribute('class', self::BTN_TIME_ADD_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['workEnd']->control->attrs['id'],
                        'slider' => 'slider_range',
                        'pos' => 1,
                        'val' => 30
                    ])
                );

        $form->addButton('workEndSub', '-')
                ->setAttribute('class', self::BTN_TIME_SUB_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['workEnd']->control->attrs['id'],
                        'slider' => 'slider_range',
                        'pos' => 1,
                        'val' => -30
                    ])
                );

        $form->addButton('lunchAdd', '+')
                ->setAttribute('class', self::BTN_TIME_ADD_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['lunch']->control->attrs['id'],
                        'slider' => 'slider_lunch',
                        'val' => -30
                    ])
                );

        $form->addButton('lunchSub', '-')
                ->setAttribute('class', self::BTN_TIME_SUB_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['lunch']->control->attrs['id'],
                        'slider' => 'slider_lunch',
                        'val' => 30
                    ])
                );

        $form->addButton('otherHoursAdd', '+')
                ->setAttribute('class', self::BTN_TIME_ADD_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['otherHours']->control->attrs['id'],
                        'slider' => 'slider_time_other',
                        'val' => 0
                    ])
                );

        $form->addButton('otherHoursSub', '-')
                ->setAttribute('class', self::BTN_TIME_SUB_CLASS)
                ->setAttribute(
                    'data-time',
                    Json::encode([
                        'inputID' => $form['otherHours']->control->attrs['id'],
                        'slider' => 'slider_time_other',
                        'val' => 0
                    ])
                );

        $form->addSubmit('save', 'Uložit řádek');

        $form->getElementPrototype()->id = 'update-form';

        return $form;
    }
}