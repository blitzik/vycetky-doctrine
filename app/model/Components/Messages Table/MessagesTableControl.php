<?php

namespace App\Model\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use Nette\Forms\Controls\SubmitButton;
use MessagesLoaders\IMessagesLoader;
use Nette\Application\UI\Control;
use Components\IPaginatorFactory;
use App\Model\Entities\Message;
use Nette\Application\UI\Form;
use Nette\Security\User;

class MessagesTableControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var IPaginatorFactory
     */
    private $paginatorFactory;

    /**
     * @var User
     */
    private $user;

    /**
     * @var IMessagesLoader
     */
    private $loader;


    public function __construct(
        IMessagesLoader $loader,
        IPaginatorFactory $pf,
        User $user
    ) {
        $this->paginatorFactory = $pf;
        $this->loader = $loader;
        $this->user = $user;
    }

    protected function createComponentPaginator()
    {
        $vp = $this->paginatorFactory->create();
        $vp->getPaginator()->setItemsPerPage(15);

        return $vp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        switch ($this->loader->getMessagesType()) {
            case Message::RECEIVED:
                $template->setFile(__DIR__ . '/templates/receivedMessagesTable.latte');
                break;

            case Message::SENT:
                $this->template->setFile(__DIR__ . '/templates/sentMessagesTable.latte');
                break;
        }

        $paginator = $this['paginator']->getPaginator();
        $numberOfMessages = $this->loader->getNumberOfMessages();
        $paginator->setItemCount($numberOfMessages);

        $template->messages = $this->loader
                                   ->findMessages(
                                       $paginator->getOffset(),
                                       $paginator->getLength()
                                   );

        $template->numberOfMessages = $numberOfMessages;

        $template->render();
    }

    protected function createComponentMessagesActions()
    {
        $form = new Form();

        $form->addCheckbox('checkAll', '')
                ->setHtmlId('checkAll');

        $form->addSubmit('delete', 'Odstranit označené')
                ->setAttribute('class', 'ajax')
                ->onClick[] = $this->processDeleteMessages;

        $form['delete']->getControlPrototype()
                       ->onClick = 'return confirm(\'Skutečně chcete odstranit všechny označené zprávy?\');';

        $form->addProtection();

        return $form;
    }

    public function processDeleteMessages(SubmitButton $button)
    {
        $messagesIDs = $button->getForm()->getHttpData(Form::DATA_TEXT, 'msg[]');

        if (!empty($messagesIDs)) {
            try {
                $this->loader->removeMessages($messagesIDs);
                $this->flashMessage('Vybrané zprávy byli úspěšně smazány.', 'success');

            } catch (\DibiException $e) {
                $this->flashMessage(
                    'Při pokusu o hromadné smazání zpráv došlo k chybě.
                     Zkuste akci opakovat později.',
                    'error'
                );
            }

            if ($this->presenter->isAjax()) {
                $this->redrawControl();

            } else {
                $this->redirect('this');
            }
        }
    }

    /**
     * @secured
     */
    public function handleDeleteMessage($id)
    {
        $this->loader->removeMessage($id);

        if ($this->presenter->isAjax()) {
            $this->redrawControl();

        } else {

            $this->flashMessage('Zpráva byla úspěšně smazána.', 'success');
            $this->redirect('this');
        }
    }
}