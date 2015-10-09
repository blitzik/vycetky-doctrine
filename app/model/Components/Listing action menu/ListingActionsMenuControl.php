<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

class ListingActionsMenuControl extends BaseComponent
{
    /** @var Listing */
    private $listing;

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/menu.latte');

        $template->listing = $this->listing;

        $template->render();
    }
}