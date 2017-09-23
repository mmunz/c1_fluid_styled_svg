<?php

namespace C1\FluidStyledSvg\Resource\Rendering;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
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
            // cropping not implemented yet. maybe possible with manipulating the viewport of the svg
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
        $tagBuilder = new $this->tagBuilder();
        $tagBuilder->reset();
        $tagBuilder->setTagName('object');
        $tagBuilder->addAttribute('data', $this->imageFile->getPublicUrl());
        $tagBuilder->addAttribute('type', 'image/svg+xml');
        $tagBuilder->addAttribute('name', $this->altText);
        $tagBuilder->addAttribute('width', $this->defaultProcessConfiguration['width']);
        $tagBuilder->addAttribute('height', $this->defaultProcessConfiguration['height']);
        $tagBuilder->forceClosingTag(true);
        return $tagBuilder->render();
    }


    /**
     * Renders the image tag
     * @return string
     */
    protected function renderImg()
    {
        $tagBuilder = new $this->tagBuilder();
        if ($this->imageFile->getSize() < $this->settings['inlineSmallerThan']) {
            // inline the svg
            $svgRaw = $this->imageFile->getContents();
            $svgTemplate = new \SimpleXMLElement($svgRaw);
            $svgTemplate->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
            // set or modify width and height attributes of the svg
            foreach($svgTemplate->attributes() as $key => $value) {
                if ($key === "width") {
                    $svgTemplate->attributes()->width = $this->defaultProcessConfiguration['width'];
                } else {
                    $svgTemplate->addAttribute('width', $this->defaultProcessConfiguration['width']);
                }
                if ($key === "height") {
                    $svgTemplate->attributes()->height = $this->defaultProcessConfiguration['height'];
                } else {
                    $svgTemplate->addAttribute('height', $this->defaultProcessConfiguration['height']);
                }
            }
            return $svgTemplate->asXML();
        } else {

            $tagBuilder->setTagName('object');
            $tagBuilder->addAttribute('data', $this->imageFile->getPublicUrl());
            $tagBuilder->addAttribute('type', 'image/svg+xml');
            $tagBuilder->addAttribute('name', $this->altText);
            $tagBuilder->addAttribute('class', 'svg-ajaxload');
            $tagBuilder->addAttribute('width', $this->defaultProcessConfiguration['width']);
            $tagBuilder->addAttribute('height', $this->defaultProcessConfiguration['height']);
            $tagBuilder->forceClosingTag(true);

//            $tagBuilder->reset();
//            $tagBuilder->setTagName('noscript');
//            $tagBuilder->forceClosingTag(true);
//            $tagBuilder->setContent($this->renderObjectTag());
//            $noscriptTag = $tagBuilder->render();
//            $tagBuilder->reset();
//            $tagBuilder->setTagName('div');
//            $tagBuilder->forceClosingTag(true);
//            $tagBuilder->addAttribute('class', 'svg-ajaxload');
//            $tagBuilder->addAttribute('data-src', $this->imageFile->getPublicUrl());
//            $tagBuilder->setContent($noscriptTag);
            return $tagBuilder->render();
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
