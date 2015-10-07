<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IUsersRelationshipsRestrictionsControlFactory;
use App\Model\Components\IUsersOverviewControlFactory;
use App\Model\Facades\UsersFacade;

class UsersPresenter extends SecurityPresenter
{
    /**
     * @var IUsersOverviewControlFactory
     * @inject
     */
    public $usersOverviewFactory;

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;


    /*
     * ----------------------------
     * ------ USERS OVERVIEW ------
     * ----------------------------
     */

    public function actionOverview()
    {

    }

    public function renderOverview()
    {

    }

    /**
     * @Actions overview
     */
    protected function createComponentUsersList()
    {
        $comp = $this->usersOverviewFactory
                     ->create($this->user->getIdentity());

        return $comp;
    }
}