<?php

namespace C1\FluidStyledSvg\Resource\Rendering;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;


/**
 * Class ImageRenderer
 * @package C1\FluidStyledSvg\Resource\Rendering
 */
class ImageRenderer implements FileRendererInterface
{

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var TagBuilder
     */
    protected $tagBuilder;

    /**
     * @var array
     */
    protected $possibleMimeTypes = [
        'image/svg+xml'
    ];

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $imageFile;

    /**
     * @var FileInterface
     */
    protected $originalFile;

    /**
     * @var string
     */
    protected $altText;

    /**
     * @var array
     */
    protected $additionalConfig;

    /**
     * @var array
     */
    protected $additionalAttributes;

    /**
     * @var array
     */
    protected $defaultProcessConfiguration;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->settings = [];
        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
        $this->getConfiguration();
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 5;
    }

    /**
     * @param FileInterface $file
     * @return bool
     */
    public function canRender(FileInterface $file)
    {
        return TYPO3_MODE === 'FE' && in_array($file->getMimeType(), $this->possibleMimeTypes, true);
    }

    /**
     * @param array $options
     * @return void
     */
    protected function init($file, $width, $height, $options)
    {

        $this->originalFile = $file;

        if ($file instanceof FileReference) {
            $this->imageFile = $file->getOriginalFile();
        } else {
            $this->imageFile = $file;
        }

        try {
            $this->defaultProcessConfiguration = [];
            $this->defaultProcessConfiguration['width'] = (int)$width;
            $this->defaultProcessConfiguration['height'] = (int)$height;
            // cropping not implemented yet. maybe possible with manipulating the viewport ofthe svg
            $this->defaultProcessConfiguration['crop'] = $this->originalFile->getProperty('crop');
        } catch (\InvalidArgumentException $e) {
            $this->defaultProcessConfiguration['crop'] = '';
        }

        // alternative text
        if ($options['alt']) {
            $this->altText = $options['alt'];
        } else {
            $altText = $this->imageFile->getProperty('alternative');
            $this->altText = $altText ? $this->imageFile->getProperty(
                'alternative'
            ) : $this->imageFile->getProperty(
                'name'
            );
        }

        is_array($options['additionalConfig']) ? $this->additionalConfig = $options['additionalConfig'] : null;

        if (is_array($options['additionalAttributes'])) {
            $this->additionalAttributes = $options['additionalAttributes'];
        }
    }

    /**
     * Renders the object tag
     * @return string
     */
    protected function renderObjectTag()
    {

        $this->tagBuilder->reset();
        $this->tagBuilder->setTagName('object');
        $this->tagBuilder->addAttribute('data', $this->imageFile->getPublicUrl());
        $this->tagBuilder->addAttribute('type', 'image/svg+xml');
        $this->tagBuilder->addAttribute('name', $this->altText);
        $this->tagBuilder->addAttribute('width', $this->imageFile->getProperty('width'));
        $this->tagBuilder->addAttribute('height', $this->imageFile->getProperty('height'));
        $this->tagBuilder->forceClosingTag(true);

        return $this->tagBuilder->render();
    }


    /**
     * Renders the image tag
     * @return string
     */
    protected function renderImg()
    {
        if ($this->imageFile->getSize() < $this->settings['inlineSmallerThan']) {
            $svgRaw = $this->imageFile->getContents();
            return $svgRaw;
        } else {
            $this->tagBuilder->reset();
            $this->tagBuilder->setTagName('noscript');
            $this->tagBuilder->setContent($this->renderObjectTag());
            $noscriptTag = $this->tagBuilder->render();
            $this->tagBuilder->reset();
            $this->tagBuilder->setTagName('div');
            $this->tagBuilder->forceClosingTag(true);
            $this->tagBuilder->addAttribute('class', 'svg-ajaxload');
            $this->tagBuilder->addAttribute('data-src', $this->imageFile->getPublicUrl());
            $this->tagBuilder->setContent($noscriptTag);
            return $this->tagBuilder->render();
        }
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
     * @return void
     */
    protected function getConfiguration()
    {
        $configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($this->getTypoScriptSetup());
        $settings = ObjectAccess::getPropertyPath($configuration, 'tx_c1_fluid_styled_svg.settings');
        $this->settings = is_array($settings) ? $settings : [];
    }

    /**
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function render(
        FileInterface $file,
        $width,
        $height,
        array $options = array(),
        $usedPathsRelativeToCurrentScript = false
    ) {
        $this->init($file, $width, $height, $options);
        return $this->renderImg();
    }

}
