<?php

namespace App\FrontModule\Presenters;


use App\Model\Authorization\AuthorizatorFactory;
use App\Model\Authorization\IResource;
use App\Model\Authorization\IRole;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\ReceivedMessage;
use App\Model\Domain\Entities\User;
use App\Model\Facades\ListingsFacade;
use App\Model\Facades\LocalitiesFacade;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\UsersFacade;
use App\Model\Services\InvitationHandler;
use App\Model\Services\Providers\LocalityProvider;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Readers\ListingsReader;
use App\Model\Services\Readers\MessagesReader;
use App\Model\Services\Readers\UsersReader;
use Components\VisualPaginator;
use Exceptions\Logic\InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\GeneratedProxy\__CG__\App\Model\Domain\Entities\WorkedHours;
use Kdyby\Persistence\Queryable;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Forms\Controls\SubmitButton;
use Nette\Security\IAuthorizator;
use Nette\Security\Permission;
use Nette\Utils\ArrayHash;
use Nette\Utils\Validators;
use Tracy\Debugger;

/**
 * @Role admin
 */
class TestPresenter extends SecurityPresenter
{
    /**
     * @var EntityManager
     * @inject
     */
    public $em;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messagesFacade;

    /**
     * @var MessagesReader
     * @inject
     */
    public $messagesReader;

    /**
     * @var LocalitiesFacade
     * @inject
     */
    public $localityFacade;

    /**
     * @var ListingsFacade
     * @inject
     */
    public $listingsFacade;

    /**
     * @var ListingItemsReader
     * @inject
     */
    public $listingItemsReader;

    /**
     * @var ListingItemsReader
     * @inject
     */
    public $itemsReader;

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;

    /**
     * @var UsersReader
     * @inject
     */
    public $usersReader;

    /**
     * @var ListingsReader
     * @inject
     */
    public $listingsReader;

    /**
     * @var InvitationHandler
     * @inject
     */
    public $invHandler;

    /**
     * @var LocalityProvider
     * @inject
     */
    public $localityProvider;

    /**
     * @var IStorage
     * @inject
     */
    public $cacheStorage;

    protected function startup()
    {
        if (!$this->user->isInRole('admin')) {
            throw new ForbiddenRequestException;
        }

        parent::startup();
    }


    public function actionDefault()
    {

    }

    public function renderDefault()
    {
        //dump($this->name);
    }

    protected function createComponentVp()
    {
        $c = new VisualPaginator();
        return $c;
    }

    //public function checkRequirements($element)
    //{
    //    parent::checkRequirements($element);
//
    //    $this->user->setAuthorizator(new MyAuthorizator());
//
    //    if (!$this->user->isAllowed($this->name, $this->action) or
    //        ($this->signal !== null and !$this->user->isAllowed($this->name, $this->formatSignalString()))) {
    //        throw new ForbiddenRequestException;
    //    }
    //}

    public function checkRequirements($element)
    {

    }

    protected function createComponentFooComponent()
    {
        return new TestComponent();
    }

    protected function formatSignalString()
    {
        return $this->signal === NULL ? NULL : ltrim(implode('-', $this->signal), '-') . '!';
    }

    /**
     * @Role employee
     */
    public function handleClick()
    {

    }
}

class TestComponent extends Control
{
    protected function createComponentListingRemoval()
    {
        $form = new Form();

        $form->addText('listing', 'FizzBuzz');

        $form->addSubmit('send', 'Send')
            ->onClick[] = [$this, 'onSuccessSend'];

        $form->addSubmit('cancel', 'cancel')
            ->onClick[] = [$this, 'onCancel'];

        //$form->onSuccess[] = [$this, 'onSuccessSend'];

        return $form;
    }

    public function onSuccessSend(SubmitButton $button)
    {

        //$this->user->isAllowed($this->signal.'!', 'click');
    }

    public function onCancel(SubmitButton $button)
    {

    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ .'/../templates/Test/template.latte');

        $template->render();
    }
}