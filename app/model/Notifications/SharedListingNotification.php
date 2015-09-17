<?php

namespace App\Model\Notifications;

use Nette\Application\LinkGenerator;
use App\Model\Entities\Message;
use App\Model\Entities\Listing;
use App\Model\Time\TimeUtils;
use Nette\Object;

class SharedListingNotification extends Object
{
    /**
     * @var LinkGenerator
     */
    private $linkGenerator;

    public function __construct(LinkGenerator $linkGenerator)
    {
        $this->linkGenerator = $linkGenerator;
    }

    public function getNotificationMessage(
        Listing $listing,
        $senderName,
        $recipientName
    ) {
        $period = TimeUtils::getMonthName($listing->month) . ' ' . $listing->year;

        $m = new Message(
            $this->constructSubject($senderName, $period),
            $this->constructMessage(
                $senderName,
                $recipientName,
                $period,
                $this->linkGenerator->link(
                    'Front:Listing:detail',
                    ['id' => $listing->listingID]
                )
            ),
            0 // system
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