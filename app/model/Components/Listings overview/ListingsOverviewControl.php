<?php

namespace App\Model\Components;

use App\Model\Queries\Listings\ListingsForOverviewQuery;
use Nextras\Application\UI\SecuredLinksControlTrait;
use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ListingsFacade;
use Nette\Application\UI\Multiplier;
use Nette\Application\UI\Control;
use Doctrine\ORM\AbstractQuery;
use Nextras\Datagrid\Datagrid;
use blitzik\Arrays\Arrays;

class ListingsOverviewControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var ListingsForOverviewQuery
     */
    private $listingsQuery;

    /**
     * @var ListingsFacade
     */
    private $listingsFacade;

    /**
     * @var Listing[]
     */
    private $listings;

    /**
     * @var string
     */
    private $heading;


    public function __construct(
        ListingsForOverviewQuery $listingsQuery,
        ListingsFacade $listingsFacade
    ) {
        $this->listingsFacade = $listingsFacade;
        $this->listingsQuery = $listingsQuery;
    }

    protected function createComponentDataGrid()
    {
        return new Multiplier(function ($month) {
            $grid = new Datagrid();

            $grid->addColumn('l_year', 'Rok');
            $grid->addColumn('l_month', 'Měsíc');
            $grid->addColumn('l_description', 'Popis');
            $grid->addColumn('worked_days', 'Dny');
            $grid->addColumn('total_worked_hours', 'Hodiny');

            $grid->setDataSourceCallback(function ($filter, $order) use ($month) {
                return $this->listings[$month];
            });

            $grid->addCellsTemplate(__DIR__ . '/templates/grid/grid.latte');

            return $grid;
        });
    }

    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

    private function prepareListings(array $listings)
    {
        $collection = [];
        foreach ($listings as $listing) {
            $collection[$listing['l_month']][] = (object) $listing;
        }

        return $collection;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        if ($this->listingsQuery->getMonth() !== null) {
            $this->template->date = new \DateTime($this->listingsQuery->getYear() . '-' .
                                                  $this->listingsQuery->getMonth() . '-01');
        }

        $this->listings = $this->prepareListings(
            $this->listingsFacade
                 ->fetchListings($this->listingsQuery)
                 ->toArray(AbstractQuery::HYDRATE_SCALAR)
        );

        $template->heading = $this->heading;
        $template->numberOfListings = isset($this->listings) ? Arrays::count_recursive($this->listings, 1) : 0;
        $template->listings = $this->listings;

        $template->render();
    }
}