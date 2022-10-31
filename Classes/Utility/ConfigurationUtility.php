<?php

namespace C1\FluidStyledSvg\Utility;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class ConfigurationUtility
{
    private TypoScriptService $typoScriptService;

    public function injectTypoScriptService(TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @return array
     */
    protected function getTypoScriptSetup()
    {
        if (!$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            return [];
        }

        if (!$GLOBALS['TSFE']->tmpl instanceof TemplateService) {
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
