<?php
namespace RSM\Rsmbouncemailprocessor\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;

class ActionViewHelper extends AbstractViewHelper {

    public function __construct(private readonly UriBuilder $uriBuilder)
    {
    }
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('uid', 'mixed', '', true);
        $this->registerArgument('table', 'string', '', true);
        $this->registerArgument('action', 'string', '', false, 'edit');
    }

	/**
	 * @return string $uri
	 */
	public function render () {

        $uid = intval($this->arguments['uid']);
        $table = trim(strval($this->arguments['table']));
        $action = trim(strval($this->arguments['action']));

        // Migrated
        $uriBuilder = $this->uriBuilder;
        $uriParams = [
            $action => [
                $table => [
                    $uid => $action,
                ],
            ],
            'returnNewPageId' => 1,
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $uriParams);
		//$uri = htmlspecialchars($uri, ENT_COMPAT, 'UTF-8');

		return $uri;

    }

}
