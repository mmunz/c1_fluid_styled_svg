<?php
//
//// if you need to change the ratios in a provider extension, you need to copy
//// the complete code.
//
//$GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['ratios'] = [
//    '3' => '3:1',
//    '2' => '2:1',
//    '1.7777777777777777' => '16:9',
//    '1.3333333333333333' => '4:3',
//    '1' => '1:1',
//    'NaN' => 'Free',
//];
//
//function ratiosToItems() {
//    $ratios = $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['ratios'];
//    // Empty item
//    $items[] = ['', '0'];
//    foreach ($ratios as $key => $ratio) {
//        if ($key !== 'NaN') {
//            $items[] = array($ratio, $key);
//        }
//    }
//    return $items;
//}
//
//
//// Add image_format and image_rows to TCA
//$additionalColumns = [
//    'image_format' => [
//        'exclude' => true,
//        'label' => 'LLL:EXT:c1_fluid_styled_svg/Resources/Private/Language/TCA.xlf:image_format_formlabel',
//        'config' => [
//            'type' => 'select',
//            'renderType' => 'selectSingle',
//            'items' => ratiosToItems(),
//        ]
//    ],
//    'image_rows' => array(
//        'exclude' => true,
//        'label' => 'LLL:EXT:c1_fluid_styled_svg/Resources/Private/Language/TCA.xlf:image_rows_formlabel',
//        'config' => array(
//            'type' => 'check',
//            'items' => array(
//                '1' => array(
//                    '0' => 'LLL:EXT:c1_fluid_styled_svg/Resources/Private/Language/TCA.xlf:image_rows.1.0'
//                )
//            ),
//            'default' => 1,
//        )
//    ),
//];
//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $additionalColumns);
