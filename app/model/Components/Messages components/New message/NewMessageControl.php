<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use Exceptions\Runtime\MessageLengthException;
use App\Model\Facades\UsersFacade;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class NewMessageControl extends Control
{
    /**
     * @var UsersFacade
     */
    private $usersFacade;

    /**
     * @var User
     */
    private $user;

    public function __construct(
        User $user,
        UsersFacade $usersFacade
    ) {
        $this->user = $user;
        $this->usersFacade = $usersFacade;
    }

    protected function createComponentNewMessageForm()
    {
        $form = new Form();

        $form->addText('subject', 'Předmět', 35, 80)
            ->setRequired('Vyplňte prosím předmět zprávy.');

        $form->addTextArea('message', 'Zpráva', 50, 12)
            ->setRequired('Vyplňte prosím text zprávy.')
            ->addRule(Form::MAX_LENGTH, 'Zpráva může obsahovat maximálně %d znaků.', 2000);

        $form->addMultiSelect('receivers', 'Příjemci', $this->fillSelectWithUsers(), 13)
            ->setRequired('Vyberte alespoň jednoho příjemce.');

        $form->addCheckbox('isSystemMessage', 'Odeslat jako systémovou zprávu');

        $form->addSubmit('send', 'Odeslat');

        $form->getElementPrototype()->id = 'new-message-form';

        $form->onSuccess[] = $this->processNewMessageForm;

        return $form;
    }

    private function fillSelectWithUsers()
    {

    }

    public function processNewMessageForm(Form $form)
    {
        $values = $form->getValues();

        $texy = new \Texy();
        $texy->setOutputMode(\Texy::HTML4_TRANSITIONAL);
        $texy->encoding = 'utf-8';
        $texy->allowedTags = \Texy::ALL;

        $text = $texy->process($values->message);

        // 0 == system account
        $author = $values['isSystemMessage'] == true ? 0 : $this->user->id;
        try {
            $this->messagesFacade
                ->sendMessage(
                    $values->subject,
                    $text,
                    $author,
                    $values->receivers
                );

        } catch (MessageLengthException $ml) {
            $form->addError('Zprávu nelze uložit, protože je příliš dlouhá.');
            return;

        } catch (\DibiException $e) {
            $this->flashMessage('Zpráva nemohla být odeslána. Zkuste akci opakovat později.', 'errror');
            $this->redirect('this');
        }

        $this->flashMessage('Zpráva byla úspěšně odeslána', 'success');
        $this->redirect('MailBox:sent');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');



        $template->render();
    }
}