<?php

namespace App\Model\Notifications;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use Nette\Application\LinkGenerator;
use App\Model\Time\TimeUtils;
use Nette\Object;

class SharedListingNotification extends Object
{
    /** @var LinkGenerator  */
    private $linkGenerator;

    public function __construct(
        LinkGenerator $linkGenerator
    ) {
        $this->linkGenerator = $linkGenerator;
    }

    public function getNotificationMessage(
        Listing $newListing,
        User $sender
    ) {
        $period = TimeUtils::getMonthName($newListing->month) . ' ' . $newListing->year;

        $m = new SentMessage(
            $this->constructSubject($sender->username, $period),
            $this->constructMessage(
                $sender->username,
                $newListing->getUser()->username,
                $period,
                $this->linkGenerator->link(
                    'Front:Listing:detail',
                    ['id' => $newListing->getId()]
                )
            ),
            $sender
        );

        return $m;
    }

    private function constructSubject($senderName, $period)
    {
        return 'Uživatel ' . $senderName . ' Vám nasdílel výčetku pro [' .
                $period . ']';
    }

    private function constructMessage($senderName, $recipientName, $period, $link)
    {
        $message = '<p>Dobrý den ' . $recipientName . ',</p>
        <p>uživatel <b>' .$senderName. '</b> Vám nasdílel výčetku
        pro <a href="'.$link.'">[' . $period . ']</a></p>' ;

        return $message;
    }

}