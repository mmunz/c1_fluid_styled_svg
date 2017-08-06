<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'c1_fluid_styled_svg',
    'Configuration/TypoScript',
    'Fluid Styled Content svg renderer'
);
