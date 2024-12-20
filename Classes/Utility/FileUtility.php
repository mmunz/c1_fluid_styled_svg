<?php

namespace C1\FluidStyledSvg\Utility;

use TYPO3\CMS\Core\Resource\File;
class FileUtility
{

    /**
     * Return the alternative text from an image file.
     * Tries first to get the alternative text from $options from the f:media viewhelper ('alt').
     * If that fails, then try to get the alternative text from the original file
     * If that also fails, then return an empty string
     *
     * @param File $file
     * @param array $options
     * @return string
     */
    public function getAltText($file, $options = [])
    {
        $altText = '';
        if (isset($options['alt'])) {
            $altText = $options['alt'];
        } elseif ($file->getProperty('alternative')) {
            $altText = $file->getProperty('alternative');
        }
        return $altText;
    }
}
