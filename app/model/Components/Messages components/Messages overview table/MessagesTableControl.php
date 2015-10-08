<?php

namespace App\Model\Components;

use App\Model\Authorization\Authorizator;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\MessagesHandlers\IMessagesHandler;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\ResultSet;
use Nette\Application\UI\ITemplate;
use Nextras\Application\UI\SecuredLinksControlTrait;
use Nette\Forms\Controls\SubmitButton;
use Nette\Application\UI\Control;
use Components\IPaginatorFactory;
use App\Model\Domain\Entities\SentMessage;
use Nette\Application\UI\Form;
use Tracy\Debugger;

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
     * @var MessagesFacade
     */
    private $messagesFacade;

    /**
     * @var Authorizator
     */
    private $authorizator;

    /**
     * @var ResultSet
     */
    private $resultSet;

    public function __construct(
        IMessagesHandler $handler,
        IPaginatorFactory $pf,
        MessagesFacade $messagesFacade,
        Authorizator $authorizator
    ) {
        $this->paginatorFactory = $pf;
        $this->messagesHandler = $handler;
        $this->messagesFacade = $messagesFacade;
        $this->authorizator = $authorizator;
        $this->user = $handler->getUser();
    }

    protected function createComponentPaginator()
    {
        $vp = $this->paginatorFactory->create();
        $vp->onPaginate[] = function () {
            $this->redrawControl();
        };

        return $vp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $this->switchTemplateByMessagesType($template);

        $this->resultSet = $this->messagesHandler->getResultSet();

        $paginator = $this['paginator']->getPaginator();
        $this->resultSet->applyPaginator($paginator, 10);

        $messages = $this->resultSet->toArray(AbstractQuery::HYDRATE_ARRAY);

        $template->messages = $messages;

        $template->numberOfMessages = $paginator->getItemCount();

        $template->render();
    }

    private function switchTemplateByMessagesType(ITemplate $template)
    {
        switch ($this->messagesHandler->getMessagesType()) {
            case SentMessage::RECEIVED:
                $template->setFile(__DIR__ . '/templates/receivedMessagesTable.latte');
                break;

            case SentMessage::SENT:
                $template->setFile(__DIR__ . '/templates/sentMessagesTable.latte');
                break;
        }
    }

    /**
     * @secured
     */
    public function handleDeleteMessage($id)
    {
        $message = $this->messagesFacade->readMessage($id, $this->messagesHandler->getMessagesType());
        if ($message !== null) {
            if (!$this->authorizator->isAllowed($this->user, $message, 'remove')) {
                $this->flashMessage('Nemáte dostatečná oprávnění ke smazání zprávy.', 'warning');
                $this->redirect('this');
            }
            try {
                $this->messagesHandler->removeMessage($id);
                $this->flashMessage('Zpráva byla úspěšně odstraněna.', 'success');
            } catch (DBALException $e) {
                $this->flashMessage('Zprávu se nepodařilo odstranit.', 'error');
            }
        }

        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }
}