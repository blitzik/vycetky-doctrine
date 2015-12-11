<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\ListingsFacade;
use App\Model\Services\Pdf\ListingPDFGenerator;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Form;

class AnnualPDFGenerationControl extends BaseComponent
{
    /** @var array */
    private $companyParameters;

    /** @var ListingsFacade */
    private $listingsFacade;

    /** @var ListingPDFGenerator */
    private $PDFGenerator;

    /** @var User */
    private $user;

    public function __construct(
        array $companyParameters,
        ListingsFacade $listingsFacade,
        ListingPDFGenerator $PDFGenerator
    ) {
        $this->listingsFacade = $listingsFacade;
        $this->companyParameters = $companyParameters;
        $this->PDFGenerator = $PDFGenerator;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/annualPdfGeneration.latte');

        $annualListings = $this->listingsFacade->getListingsYears($this->user);
        krsort($annualListings);

        $template->listingsData = $annualListings;

        //dump($this->listingsFacade->getListingsIDsByYear(2015, $this->user));

        $template->render();
    }

    /**
     * @secured
     */
    public function handleDownload($year)
    {
        $zipPath = $this->PDFGenerator
                        ->generateAnnualSeparatedPDFs(
                            $year,
                            $this->user
                        );

        $this->presenter->sendResponse(new FileResponse($zipPath));
    }
}