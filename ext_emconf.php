<?php
/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = array(
    'title' => 'C1 Fluid Styled Content SVG Renderer',
    'description' => 'Registers a custom renderer for SVG (Scalable Vector Graphics)',
    'category' => 'fe',
    'version' => '0.9.1',
    'state' => 'beta',
    'uploadfolder' => false,
    'clearcacheonload' => true,
    'author' => 'Manuel Munz',
    'author_email' => 't3dev@comuno.net',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-8.99.99',
            'typo3' => '7.5.0-8.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
);
