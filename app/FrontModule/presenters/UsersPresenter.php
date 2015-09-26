<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IUsersOverviewControlFactory;

class UsersPresenter extends SecurityPresenter
{
    /**
     * @var IUsersOverviewControlFactory
     * @inject
     */
    public $usersOverviewFactory;


    /*
     * -------------------------
     * ------ INVITATIONS ------
     * -------------------------
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