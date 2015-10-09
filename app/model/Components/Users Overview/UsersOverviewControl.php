<?php

namespace App\Model\Components;

use App\Model\Queries\Users\UsersOverviewQuery;
use Nette\Application\UI\Multiplier;
use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use Components\IPaginatorFactory;
use Doctrine\ORM\AbstractQuery;
use Nette\Utils\Arrays;

class UsersOverviewControl extends BaseComponent
{
    /** @var IUsersRelationshipsRestrictionsControlFactory  */
    private $relationshipsRestrictionsControlFactory;

    /** @var IUserBlockingControlFactory  */
    protected $userBlockingControlFactory;

    /** @var IPaginatorFactory  */
    private $paginatorFactory;

    /** @var User */
    protected $userEntity;

    /** @var UsersFacade */
    protected $usersFacade;

    /** @var UsersOverviewQuery */
    protected $usersQuery;

    /**  @var array */
    protected $users = [];

    /** @var array */
    protected $alreadyBlockedUsers = [];
    protected $usersWithClosedAccount = [];

    protected $areRelationshipsRestrictionsVisible = true;
    protected $isHintBoxVisible = true;


    public function __construct(
        User $userEntity,
        UsersFacade $usersFacade,
        IPaginatorFactory $paginatorFactory,
        IUserBlockingControlFactory $userBlockingControlFactory,
        IUsersRelationshipsRestrictionsControlFactory $relationshipsRestrictionsControlFactory
    ) {
        $this->userEntity = $userEntity;
        $this->usersFacade = $usersFacade;
        $this->paginatorFactory = $paginatorFactory;
        $this->userBlockingControlFactory = $userBlockingControlFactory;
        $this->relationshipsRestrictionsControlFactory = $relationshipsRestrictionsControlFactory;

        $this->usersQuery = (new UsersOverviewQuery())
                            ->onlyWithFields(['id', 'username', 'isClosed'])
                            ->orderByUsername('ASC');
                            //->withoutUser($userEntity);
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
                         ->create($userId, $this->userEntity);

            $comp->setAlreadyBlockedUsersIDs($this->alreadyBlockedUsers);
            $comp->setUsersWithClosedAccountIDs($this->usersWithClosedAccount);

            $comp->onBlockUser[] = [$this, 'onBlockUser'];
            $comp->onUnblockUser[] = [$this, 'onUnblockUser'];

            $comp->onCloseAccount[] = [$this, 'onCloseAccount'];
            $comp->onOpenAccount[] = [$this, 'onOpenAccount'];

            return $comp;
        });
    }

    public function hideRelationshipsRestrictions()
    {
        $this->areRelationshipsRestrictionsVisible = false;
    }

    public function hideHintBox()
    {
        $this->isHintBoxVisible = false;
    }

    protected function createComponentRelationshipsRestrictions()
    {
        $ru = $this->usersFacade->findRestrictedUsers($this->userEntity);
        $su = $this->usersFacade->findSuspendedUsers();

        return $this->relationshipsRestrictionsControlFactory
                    ->create(
                        $ru['usersBlockedByMe'],
                        $ru['usersBlockingMe'],
                        $su
                    );
    }

    private function processBlockingCallbacks()
    {
        // in Ajax request we do NOT want to query
        // database for users, blocked users etc.
        // so we create 1 item in these Arrays in order to NOT query database
        // (see IF construct in render method)
        $this->users[] = null;
        $this->alreadyBlockedUsers[] = null;
        $this->usersWithClosedAccount[] = null;

        $this['relationshipsRestrictions']->redrawControl();
    }

    public function onBlockUser(UserBlockingControl $control, User $user = null)
    {
        $this->processBlockingCallbacks();
    }

    public function onUnblockUser(UserBlockingControl $control, User $user = null)
    {
        $this->processBlockingCallbacks();
    }

    public function onCloseAccount(UserBlockingControl $control, User $user = null)
    {
        $this->processBlockingCallbacks();
    }

    public function onOpenAccount(UserBlockingControl $control, User $user = null)
    {
        $this->processBlockingCallbacks();
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $paginator = $this['paginator']->getPaginator();

        if (empty($this->users)) {
            $usersResultSet = $this->usersFacade
                                   ->fetchUsers($this->usersQuery);

            $usersResultSet->applyPaginator($paginator, 15);

            $this->users = Arrays::associate(
                $usersResultSet->toArray(AbstractQuery::HYDRATE_ARRAY),
                'id'
            );
            unset($this->users[$this->userEntity->getId()]);
        }

        $template->users = $this->users;

        if (empty($this->alreadyBlockedUsers)) {
            $alreadyBlockedUsers = Arrays::associate(
                $this->usersFacade
                     ->fetchUsers(
                         (new UsersOverviewQuery())
                         ->onlyWithFields(['id'])
                         ->findUsersBlockedBy($this->userEntity)
                     )->toArray(AbstractQuery::HYDRATE_ARRAY),
                'id'
            );
            $this->alreadyBlockedUsers = $alreadyBlockedUsers;
        }

        if ($this->userEntity->isInRole('admin') and
            empty($this->usersWithClosedAccount)) {
            $uwca = Arrays::associate(
                $this->usersFacade
                     ->fetchUsers(
                         (new UsersOverviewQuery())
                         ->onlyWithFields(['id'])
                         ->findUsersWithClosedAccount()
                     )->toArray(AbstractQuery::HYDRATE_ARRAY),
                'id'
            );
            $this->usersWithClosedAccount = $uwca;
        }

        $template->areRelationshipsRestrictionsVisible = $this->areRelationshipsRestrictionsVisible;
        $template->isHintBoxVisible = $this->isHintBoxVisible;

        $template->render();
    }
}