<?php

namespace C1\FluidStyledSvg\Resource\Rendering;

use C1\FluidStyledSvg\Utility\FileUtility;
use C1\FluidStyledSvg\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class ImageRenderer
 * @package C1\FluidStyledSvg\Resource\Rendering
 */
class ImageRenderer implements FileRendererInterface
{

    const WRAPPERCLASS = 'c1-svg__wrapper';
    const IMAGECLASS = 'c1-svg__image';

    private ?PageRenderer $pageRenderer = null;

    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    private ?TagBuilder $tagBuilder = null;

    private ?FileUtility $fileUtility = null;

    public function injectFileUtility(FileUtility $fileUtility)
    {
        $this->fileUtility = $fileUtility;
    }

    private ConfigurationUtility $configurationUtility;

    public function injectConfigurationUtility(ConfigurationUtility $configurationUtility)
    {
        $this->configurationUtility = $configurationUtility;
    }

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
     * @var File
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
    protected $imgClassNames = [];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
    }

    private function setConfiguration()
    {
        $this->settings = $this->configurationUtility->getConfiguration();
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
        return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            && in_array($file->getMimeType(), $this->possibleMimeTypes, true);
    }

    /**
     * @param array $options
     * @return void
     */
    protected function init($file, $width, $height, $options)
    {
        $this->setConfiguration();
        $this->originalFile = $file;

        if ($file instanceof FileReference) {
            $this->imageFile = $file->getOriginalFile();
        } else {
            $this->imageFile = $file;
        }

        $this->defaultProcessConfiguration = [];
        $this->defaultProcessConfiguration['width'] = (int)$width;
        $this->defaultProcessConfiguration['height'] = (int)$height;
        // cropping not implemented yet. maybe possible with manipulating the viewport of the svg
        // $this->defaultProcessConfiguration['crop'] = $this->originalFile->getProperty('crop');

        // alternative text
        $this->altText = $this->fileUtility->getAltText($this->imageFile, $options);

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
            if (!in_array($cl, $this->imgClassNames)) {
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
        $ratio = ($this->defaultProcessConfiguration['height'] / $this->defaultProcessConfiguration['width']) * 100;
        return round($ratio, 2);
    }

    /**
     * Render a ratio box wrapper tag
     * @return string
     */
    protected function ratioBox($content)
    {
        $aspectRatio = $this->getAspectRatio();
        $aspectRatioDotted = \preg_replace('/\./i', 'dot', $aspectRatio);
        $tagBuilder = new $this->tagBuilder();
        $tagBuilder->setTagName('div');


        $className = 'c1-svg__wrapper--pb-' . $aspectRatioDotted . '-w-' . $this->defaultProcessConfiguration['width'];
        $css = sprintf(
            '.%s{padding-bottom:%s%%;width:%spx}',
            $className,
            $aspectRatio,
            $this->defaultProcessConfiguration['width']
        );
        // add css for the className to the head.
        // Because of the unique key used there won't be multiple css rules for the same thing.
        $this->pageRenderer->addCssInlineBlock($className, $css, true);
        $tagBuilder->addAttribute('class', self::WRAPPERCLASS . ' ' . $className);
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
            $xmlDocument = new \DOMDocument();
            $xmlDocument->formatOutput = true;
            $xmlDocument->loadXML($svgRaw, LIBXML_NSCLEAN);
            $xmlDocument->documentElement->setAttribute('width', '100%');
            $xmlDocument->documentElement->setAttribute('height', '100%');
            $xmlDocument->documentElement->setAttribute(
                'class',
                $this->getImgClassNames() . ' c1-svg__image--inline'
            );
            return $this->ratioBox($xmlDocument->saveXML($xmlDocument->documentElement));
        } else {
            $tagBuilder->setTagName('object');
            $tagBuilder->addAttribute('data', $this->imageFile->getPublicUrl());
            $tagBuilder->addAttribute('type', 'image/svg+xml');
            if (!empty($this->altText)) {
                $tagBuilder->addAttribute('name', $this->altText);
            };
            $tagBuilder->addAttribute('class', $this->getImgClassNames() . ' c1-svg__image--inject');
            $tagBuilder->addAttribute('width', $this->defaultProcessConfiguration['width']);
            $tagBuilder->addAttribute('height', $this->defaultProcessConfiguration['height']);
            $tagBuilder->forceClosingTag(true);
            return $this->ratioBox($tagBuilder->render());
        }
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
        array         $options = array(),
                      $usedPathsRelativeToCurrentScript = false
    )
    {
        $this->init($file, $width, $height, $options);
        return $this->renderImg();
    }

}
