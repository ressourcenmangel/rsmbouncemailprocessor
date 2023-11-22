<?php
declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Controller;

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Exception;
use PharIo\Manifest\InvalidUrlException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RSM\Rsmbouncemailprocessor\Domain\Model\Recipientreport;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;


use RSM\Rsmbouncemailprocessor\Domain\Model\Bouncereport;
use RSM\Rsmbouncemailprocessor\Domain\Repository\BouncereportRepository;


use Undkonsorten\CuteMailing\Domain\Model\Newsletter;
use Undkonsorten\CuteMailing\Domain\Model\NewsletterTask;
use Undkonsorten\CuteMailing\Domain\Model\RecipientListInterface;
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
        /*
        \TYPO3\CMS\Core\Utility\DebugUtility::debug($this->bouncereportRepository);
        \TYPO3\CMS\Core\Utility\DebugUtility::debug($this->bouncereportRepository->findAll());
        */

        // read bounce mail report
        $currentPid = (int)GeneralUtility::_GP('id');
        if ($currentPid === 0) {
            return new ForwardResponse('choosePage');
        }

        // read reports
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $currentPid)->get();
        $bouncereports = $this->bouncereportRepository->findByRootline($rootline);
        if (! count($bouncereports)) {
            return new ForwardResponse('choosePage');
        }

        $this->view->assignMultiple([
            'bouncereports' => $bouncereports,
        ]);
        return $this->htmlResponse();
    }

    public function choosePageAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }


    /**
     * @param Bouncereport $bouncereport
     * @return ResponseInterface
     * @throws IllegalObjectTypeException
     * @throws StopActionException
     * @throws DBALException
     */
    public function deleteAction(Bouncereport $bouncereport): ResponseInterface
    {
        $this->bouncereportRepository->remove($bouncereport);
        $this->addFlashMessage(LocalizationUtility::translate('module.bouncereport.delete.message', 'rsmbouncemailprocessor'), 'Deleted');
        return $this->redirect('list');
    }

}
