<?php
declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


use RSM\Rsmbouncemailprocessor\Domain\Model\Bouncereport;
use RSM\Rsmbouncemailprocessor\Domain\Repository\BouncereportRepository;
use RSM\Rsmbouncemailprocessor\Service\ReportPresenter;
use Undkonsorten\CuteMailing\Domain\Repository\NewsletterRepository;
use Undkonsorten\CuteMailing\Domain\Repository\RecipientListRepositoryInterface;

/**
 * Class BouncemailController
 */
class BouncemailController extends ActionController
{

    /**
     * @var BouncereportRepository|null
     */
    protected $bouncereportRepository = null;


    /**
     * @var NewsletterRepository|null
     */
    protected $newsletterRepository = null;

    /**
     * @var RecipientListRepositoryInterface|null
     */
    protected $recipientListRepository = null;


    /**
     * @param BouncereportRepository $bouncereportRepository
     */
    public function __construct(
        BouncereportRepository $bouncereportRepository,
        NewsletterRepository $newsletterRepository,
        RecipientListRepositoryInterface $recipientListRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ReportPresenter $reportPresenter
    ) {
        $this->bouncereportRepository = $bouncereportRepository;
        $this->newsletterRepository = $newsletterRepository;
        $this->recipientListRepository = $recipientListRepository;
    }

    /**
     *
     */
    public function listAction(): ResponseInterface
    {
        // read bounce mail report
        $currentPid = (int)($this->request->getParsedBody()['id'] ?? $this->request->getQueryParams()['id'] ?? null);
        if ($currentPid === 0) {
            return new ForwardResponse('choosePage');
        }

        // read reports
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $currentPid)->get();
        $bouncereports = $this->bouncereportRepository->findByRootline($rootline);
        if (! count($bouncereports)) {
            return new ForwardResponse('choosePage');
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple($this->reportPresenter->presentBouncereports($bouncereports));
        $moduleTemplate->assign('activeTab', 'bounces');
        return $moduleTemplate->renderResponse('List');
    }

    public function choosePageAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('activeTab', 'bounces');
        return $moduleTemplate->renderResponse('ChoosePage');
    }


    /**
     * @param Bouncereport $bouncereport
     * @return ResponseInterface
     * @throws IllegalObjectTypeException
     * @throws StopActionException
     */
    public function deleteAction(Bouncereport $bouncereport): ResponseInterface
    {
        $this->bouncereportRepository->remove($bouncereport);
        $this->addFlashMessage(LocalizationUtility::translate('module.bouncereport.delete.message', 'rsmbouncemailprocessor'), 'Deleted');
        return $this->redirect('list');
    }

}
