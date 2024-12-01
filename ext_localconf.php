<?php
declare(strict_types=1);
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
defined('TYPO3') or die();

call_user_func(function () {
    /** @var RendererRegistry $rendererRegistry */
    $rendererRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class
    );
    $rendererRegistry->registerRendererClass(C1\FluidStyledSvg\Resource\Rendering\ImageRenderer::class);
});
