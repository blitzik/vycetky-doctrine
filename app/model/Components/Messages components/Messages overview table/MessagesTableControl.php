<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\MessagesHandlers\IMessagesHandler;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\ResultSet;
use Nette\Application\UI\ITemplate;
use Nextras\Application\UI\SecuredLinksControlTrait;
use Nette\Forms\Controls\SubmitButton;
use Nette\Application\UI\Control;
use Components\IPaginatorFactory;
use App\Model\Domain\Entities\Message;
use Nette\Application\UI\Form;

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
     * @var IMessagesHandler
     */
    private $messagesHandler;

    /**
     * @var ResultSet
     */
    private $resultSet;

    public function __construct(
        IMessagesHandler $handler,
        IPaginatorFactory $pf
    ) {
        $this->paginatorFactory = $pf;
        $this->messagesHandler = $handler;
        $this->user = $handler->getUser();
    }

    protected function createComponentPaginator()
    {
        $vp = $this->paginatorFactory->create();

        return $vp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $this->switchTemplateByMessagesType($template);

        $this->resultSet = $this->messagesHandler->getResultSet();

        $paginator = $this['paginator']->getPaginator();
        $this->resultSet->applyPaginator($paginator, 20);

        $messages = $this->resultSet->toArray(AbstractQuery::HYDRATE_ARRAY);

        $template->messages = $messages;

        $template->numberOfMessages = $paginator->getItemCount();

        $template->render();
    }

    private function switchTemplateByMessagesType(ITemplate $template)
    {
        switch ($this->messagesHandler->getMessagesType()) {
            case Message::RECEIVED:
                $template->setFile(__DIR__ . '/templates/receivedMessagesTable.latte');
                break;

            case Message::SENT:
                $template->setFile(__DIR__ . '/templates/sentMessagesTable.latte');
                break;
        }
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
                $this->messagesHandler->removeMessages($messagesIDs);
                $this->flashMessage('Vybrané zprávy byli úspěšně smazány.', 'success');

            } catch (DBALException $e) {
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
        $this->messagesHandler->removeMessage($id);

        if ($this->presenter->isAjax()) {
            $this->redrawControl();

        } else {

            $this->flashMessage('Zpráva byla úspěšně smazána.', 'success');
            $this->redirect('this');
        }
    }
}