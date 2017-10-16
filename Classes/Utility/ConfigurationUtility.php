<?php

namespace C1\FluidStyledSvg\Utility;

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ConfigurationUtility
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->typoScriptService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
    }

    /**
     * @return array
     */
    protected function getTypoScriptSetup()
    {
        if (!$GLOBALS['TSFE'] instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
            return [];
        }

        if (!$GLOBALS['TSFE']->tmpl instanceof \TYPO3\CMS\Core\TypoScript\TemplateService) {
            return [];
        }
        return $GLOBALS['TSFE']->tmpl->setup;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        $configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($this->getTypoScriptSetup());
        $settings = ObjectAccess::getPropertyPath($configuration, 'tx_c1_fluid_styled_svg.settings');
        return is_array($settings) ? $settings : [];
    }
}
