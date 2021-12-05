<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

ExtensionManagementUtility::addStaticFile(
    'c1_fluid_styled_svg',
    'Configuration/TypoScript',
    'Fluid Styled Content svg renderer'
);
