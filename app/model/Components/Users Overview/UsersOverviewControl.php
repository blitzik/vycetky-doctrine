<?php

namespace App\Model\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use App\Model\Queries\Users\UsersOverviewQuery;
use Nette\Application\UI\Multiplier;
use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use Components\IPaginatorFactory;
use Nette\Application\UI\Control;
use Doctrine\ORM\AbstractQuery;
use Nette\Utils\Arrays;

class UsersOverviewControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var IUserBlockingControlFactory
     */
    protected $userBlockingControlFactory;

    /**
     * @var IPaginatorFactory
     */
    private $paginatorFactory;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var UsersFacade
     */
    protected $usersFacade;

    /**
     * @var UsersOverviewQuery
     */
    protected $usersQuery;

    /**
     * @var array
     */
    protected $users = [];

    /**
     * @var array
     */
    protected $alreadyBlockedUsers = [];

    public function __construct(
        User $user,
        UsersFacade $usersFacade,
        IUserBlockingControlFactory $userBlockingControlFactory,
        IPaginatorFactory $paginatorFactory
    ) {
        $this->user = $user;
        $this->usersFacade = $usersFacade;
        $this->userBlockingControlFactory = $userBlockingControlFactory;

        $this->usersQuery = (new UsersOverviewQuery())
                            ->onlyWithFields(['id', 'username'])
                            ->withoutUser($user);

        $this->paginatorFactory = $paginatorFactory;
    }

    protected function createComponentPaginator()
    {
        $paginator = $this->paginatorFactory->create();
        $paginator->onPaginate[] = function () {
            $this->redrawControl();
        };

        return $paginator;
    }

    protected function createComponentUserBlocking()
    {
        return new Multiplier(function ($userId) {
            $comp = $this->userBlockingControlFactory
                ->create($userId, $this->user);

            $comp->setAlreadyBlockedUsersIDs($this->alreadyBlockedUsers);
            $comp->onBlockUser[] = [$this, 'onBlockUser'];
            $comp->onUnblockUser[] = [$this, 'onUnblockUser'];

            return $comp;
        });
    }

    public function onBlockUser(UserBlockingControl $control, User $user = null)
    {
        // in Ajax request we do NOT want to query database for users
        // so we create 1 item in these Arrays in order to NOT query database
        // (see IF construct in render method)
        $this->users[] = null;
        $this->alreadyBlockedUsers[] = null;
    }

    public function onUnblockUser(UserBlockingControl $control, User $user = null)
    {
        $this->users[] = null;
        $this->alreadyBlockedUsers[] = null;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $paginator = $this['paginator']->getPaginator();

        if (empty($this->users)) {
            $usersResultSet = $this->usersFacade
                                   ->fetchUsers($this->usersQuery);

            $usersResultSet->applyPaginator($paginator, 10);

            $this->users = Arrays::associate(
                $usersResultSet->toArray(AbstractQuery::HYDRATE_ARRAY),
                'id=username'
            );
            //unset($this->users[$this->user->getId()]);
        }

        $template->users = $this->users;

        if (empty($this->alreadyBlockedUsers)) {
            $alreadyBlockedUsers = Arrays::associate(
                $this->usersFacade
                     ->fetchUsers(
                         (new UsersOverviewQuery())
                         ->onlyWithFields(['id'])
                         ->findUsersBlockedBy($this->user)
                )->toArray(AbstractQuery::HYDRATE_ARRAY),
                'id'
            );
            $this->alreadyBlockedUsers = $alreadyBlockedUsers;
        }

        $template->render();
    }
}