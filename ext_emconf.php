<?php
/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'C1 Fluid Styled Content SVG Renderer',
    'description' => 'Registers a custom renderer for SVG (Scalable Vector Graphics)',
    'category' => 'fe',
    'version' => '2.0.0',
    'state' => 'beta',
    'author' => 'Manuel Munz',
    'author_email' => 't3dev@comuno.net',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
