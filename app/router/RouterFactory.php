<?php

namespace App;

use	Nette\Application\Routers\RouteList;
use	Nette\Application\Routers\Route;
use Nette;

class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

        $router[] = new Route('user/<presenter>/<action>[[/<email>]/<token>]', array(
                    'module' => 'User',
                    'presenter' => 'Account',
                    'action' => 'default',
                    "email" => null,
                    'token' => null,
        ));

        $router[] = new Route('mail-box/<action received-read|received-unread|sent>[/page-<messagesTable-paginator-page>]', array(
            'module' => 'Front',
            'presenter' => 'MailBox',
            'action' => 'receivedUnread'
        ));

        $router[] = new Route('listing/<action overview|add>/<year>[/<month>]', array(
            'module' => 'Front',
            'presenter' => 'Listing',
            'action' => 'overview',
            'year' => null,
            'month' => [
                Route::FILTER_IN => function ($monthName) {
                    $date = \DateTime::createFromFormat('!F', $monthName);
                    return $date !== false ? $date->format('n') : date('n');
                },
                Route::FILTER_OUT => function ($monthNumber) {
                    if ($monthNumber < 0 or $monthNumber > 12) return null;
                    $date = \DateTime::createFromFormat('!n', $monthNumber);
                    if ($date === false) return null;

                    return Nette\Utils\Strings::webalize($date->format('F'));
                }
            ]
        ));

        $router[] = new Route('<presenter>/<action>[/<id \d+>]', array(
                    'module' => 'Front',
                    'presenter' => 'Listing',
                    'action' => 'overview',
                    'id' => null,
        ));

		return $router;
	}
}