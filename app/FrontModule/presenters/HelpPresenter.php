<?php

namespace App\FrontModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Mail\Message;

class HelpPresenter extends SecurityPresenter
{

    /**
     * @inject
     * @var \Nette\Mail\IMailer
     */
    public $mailer;

    /**
     * @var array ['admin' => ... , 'system' => ...]
     */
    private $emails;

    public function setEmails(array $emails)
    {
        $this->emails = $emails;
    }

    public function renderDoc()
    {
    }

    public function actionContact()
    {
    }

    public function renderContact()
    {
    }

    /**
     * @Actions contact
     */
    protected function createComponentHelpForm()
    {
        $form = new Form;

        $form->addText('subject', 'Předmět:', 27, 80)
                ->setRequired('Vyplňte prosím předmět Vaší zprávy.')
                ->getControlPrototype()->class = 'w340';

        $form->addTextArea('text', 'Zpráva:', 40, 10)
                ->setRequired('Vyplňte prosím text Vaší zprávy.')
                ->getControlPrototype()->class = 'w360';

        $form->addSubmit('send', 'Odeslat dotaz')
                ->setOmitted();

        $form->onSuccess[] = $this->proccessHelpForm;

        return $form;
    }

    public function proccessHelpForm(Form $form)
    {
        $values = $form->getValues();

        $username = $this->getUser()->getIdentity()->data['username'];
        $email = $this->getUser()->getIdentity()->data['email'];

        $mail = new Message;
        $mail->setFrom('Výčetkový systém <' .$this->emails['system']. '>')
             ->addTo($this->emails['admin'])
             ->setSubject($username . ' [' .$email. '] - ' . $values['subject'])
             ->setBody($values['text']);

        try {
            $this->mailer->send($mail);
            $this->flashMessage(
                'Zpráva byla odeslána. Správce se Vám ozve co nejdříve.',
                'success'
            );

        } catch (\Nette\InvalidStateException $is) {
            \Tracy\Debugger::log($is, \Tracy\Debugger::ERROR);
            $this->flashMessage(
                'Zpráva nemohla být odeslána.
                 Zkuste to prosím později.',
                'error'
            );
        }

        $this->redirect('this');
    }
}