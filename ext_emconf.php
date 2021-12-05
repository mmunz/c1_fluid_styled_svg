<?php
/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = array(
    'title' => 'C1 Fluid Styled Content SVG Renderer',
    'description' => 'Registers a custom renderer for SVG (Scalable Vector Graphics)',
    'category' => 'fe',
    'version' => '1.0.0',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'author' => 'Manuel Munz',
    'author_email' => 't3dev@comuno.net',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.6-10.4.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
);
