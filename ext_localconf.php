<?php

defined('TYPO3') or die();

call_user_func(function () {
    /** @var \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry $rendererRegistry */
    $rendererRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class
    );
    $rendererRegistry->registerRendererClass(C1\FluidStyledSvg\Resource\Rendering\ImageRenderer::class);
});
