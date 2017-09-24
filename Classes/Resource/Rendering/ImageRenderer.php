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

    const WRAPPERCLASS = 'c1-svg__wrapper';
    const IMAGECLASS = 'c1-svg__image';

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
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $imgClassNames;

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

        if ($options['class']) {
            $this->addImgClassNames($options['class']);
        }
        $this->addImgClassNames(self::IMAGECLASS);
        $this->options = $options;
    }

    /**
     * Adds one or more classes to this->imageClassNames
     * @return void
     */
    protected function addImgClassNames($classNames)
    {
        foreach (explode(' ', $classNames) as $cl) {
            if (! in_array($cl, $this->imgClassNames)) {
                $this->imgClassNames[] = $cl;
            }
        }
    }

    /**
     * Outputs the current css classes as string
     * @return string
     */
    protected function getImgClassNames()
    {
        return implode(" ", $this->imgClassNames);
    }

    /**
     * Returns the current aspect ratio of the svg
     * @return float
     */
    protected function getAspectRatio()
    {
        return ($this->defaultProcessConfiguration['height'] / $this->defaultProcessConfiguration['width']) * 100;
    }

    /**
     * Render a ratio box wrapper tag
     * @return string
     */
    protected function ratioBox($content)
    {
        $aspectRatio = $this->getAspectRatio();
        $tagBuilder = new $this->tagBuilder();
        $tagBuilder->setTagName('div');
        $tagBuilder->addAttribute('class', self::WRAPPERCLASS);
        $tagBuilder->addAttribute(
            'style',
            'padding-bottom:' . $aspectRatio . '%;width:' . $this->defaultProcessConfiguration['width'] . 'px'
        );
        $tagBuilder->setContent($content);
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
                    $svgTemplate->attributes()->width = '100%';
                } else {
                    $svgTemplate->addAttribute('width', '100%');
                }
                if ($key === "height") {
                    $svgTemplate->attributes()->height = '100%';
                } else {
                    $svgTemplate->addAttribute('height', '100%');
                }
                if ($key === "class") {
                    $svgTemplate->attributes()->class = $this->getImgClassNames() . ' c1-svg__image--inline';
                } else {
                    $svgTemplate->addAttribute('class', $this->getImgClassNames() . ' c1-svg__image--inline');
                }
            }
            return $this->ratioBox($svgTemplate->asXML());
        } else {
            $tagBuilder->setTagName('object');
            $tagBuilder->addAttribute('data', $this->imageFile->getPublicUrl());
            $tagBuilder->addAttribute('type', 'image/svg+xml');
            $tagBuilder->addAttribute('name', $this->altText);
            $tagBuilder->addAttribute('class', $this->getImgClassNames() . ' c1-svg__image--inject');
            $tagBuilder->addAttribute('width', $this->defaultProcessConfiguration['width']);
            $tagBuilder->addAttribute('height', $this->defaultProcessConfiguration['height']);
            $tagBuilder->forceClosingTag(true);
            return $this->ratioBox($tagBuilder->render());
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
