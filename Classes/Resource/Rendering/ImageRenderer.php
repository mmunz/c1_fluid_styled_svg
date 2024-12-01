<?php

namespace C1\FluidStyledSvg\Resource\Rendering;

use C1\FluidStyledSvg\Utility\FileUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class ImageRenderer
 * @package C1\FluidStyledSvg\Resource\Rendering
 */
class ImageRenderer implements FileRendererInterface
{
    final public const WRAPPERCLASS = 'c1-svg__wrapper';
    final public const IMAGECLASS = 'c1-svg__image';

    private ?TagBuilder $tagBuilder;

    protected array $possibleMimeTypes = [
        'image/svg+xml'
    ];
    protected SiteSettings $settings;
    protected ?FileInterface $imageFile;
    protected ?FileInterface $originalFile;
    protected string $altText;
    protected array $additionalConfig;
    protected array $additionalAttributes;
    protected array $defaultProcessConfiguration;
    protected array $options;
    protected array $imgClassNames = [];

    /**
     * constructor
     */
    public function __construct(
        private readonly PageRenderer $pageRenderer,
        private readonly FileUtility $fileUtility,
    )
    {
        $this->tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
    }

    private function setConfiguration(): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'];
        $site = $request->getAttribute('site');
        $this->settings = $site->getSettings();
    }

    public function getPriority(): int
    {
        return 5;
    }

    public function canRender(FileInterface $file): bool
    {
        return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            && in_array($file->getMimeType(), $this->possibleMimeTypes, true);
    }

    protected function init(FileInterface $file, int $width, int $height, array $options): void
    {
        $this->setConfiguration();
        $this->originalFile = $file;

        if ($file instanceof FileReference) {
            $this->imageFile = $file->getOriginalFile();
        } else {
            $this->imageFile = $file;
        }

        $this->defaultProcessConfiguration = [];
        $this->defaultProcessConfiguration['width'] = $width;
        $this->defaultProcessConfiguration['height'] = $height;
        // cropping not implemented yet. maybe possible with manipulating the viewport of the svg
        // $this->defaultProcessConfiguration['crop'] = $this->originalFile->getProperty('crop');

        // alternative text
        $this->altText = $this->fileUtility->getAltText($this->imageFile, $options);

        if (array_key_exists('additionalConfig', $options) && is_array($options['additionalConfig'])) {
            $this->additionalConfig = $options['additionalConfig'];
        }

        if (array_key_exists('additionalAttributes', $options) && is_array($options['additionalAttributes'])) {
            $this->additionalAttributes = $options['additionalAttributes'];
        }

        if (array_key_exists('class', $options) && !empty($options['class'])) {
            $this->addImgClassNames($options['class']);
        }

        $this->addImgClassNames(self::IMAGECLASS);
        $this->options = $options;
    }

    /*
     * Adds one or more classes to this->imageClassNames
     */
    protected function addImgClassNames($classNames): void
    {
        foreach (explode(' ', (string) $classNames) as $cl) {
            if (!in_array($cl, $this->imgClassNames)) {
                $this->imgClassNames[] = $cl;
            }
        }
    }

    protected function getImgClassNames(): string
    {
        return implode(" ", $this->imgClassNames);
    }

    /*
     * Returns the current aspect ratio of the svg
     */
    protected function getAspectRatio(): float
    {
        $ratio = ($this->defaultProcessConfiguration['height'] / $this->defaultProcessConfiguration['width']) * 100;
        return round($ratio, 2);
    }

    /*
     * Render a ratio box wrapper tag
     */
    protected function ratioBox($content): string
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function renderImg(): string
    {
        $tagBuilder = new $this->tagBuilder();
        $inlineSmallerThan = $this->settings->get('c1FluidStyledSvg.inlineSmallerThan');

        if ($this->imageFile->getSize() < $inlineSmallerThan) {
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
            }
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
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function render(
        FileInterface $file,
                      $width,
                      $height,
        array         $options = []
    ): string {
        $this->init($file, (int) $width, (int) $height, $options);
        return $this->renderImg();
    }

}
