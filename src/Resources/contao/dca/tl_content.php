<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

$dca = &$GLOBALS['TL_DCA']['tl_content'];

/*
 * Palettes
 */
$dca['palettes']['loginRedirects'] = '{type_legend},type;{lr_legend},lr_choose_redirect;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

/*
 * Fields
 */
$dca['fields']['lr_choose_redirect'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lr_choose_redirect'],
    'inputType' => 'multiColumnEditor',
    'exclude' => true,
    'save_callback' => [['HeimrichHannot\LoginRedirectsBundle\Backend\Backend', 'checkSelection']],
    'eval' => [
        'tl_class' => 'long clr',
        'multiColumnEditor' => [
            'minRowCount' => 0,
            'fields' => [
                'lr_id' => [
                    'label' => $GLOBALS['TL_LANG']['tl_content']['lr_id'],
                    'inputType' => 'select',
                    'options_callback' => ['HeimrichHannot\LoginRedirectsBundle\Backend\Backend', 'getSelection'],
                    'eval' => ['mandatory' => true, 'groupStyle' => 'width:48%;', 'includeBlankOption' => true],
                ],
                'lr_redirecturl' => [
                    'label' => $GLOBALS['TL_LANG']['tl_content']['lr_redirecturl'],
                    'exclude' => true,
                    'search' => true,
                    'inputType' => 'text',
                    'eval' => ['mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'addWizardClass' => false, 'tl_class' => 'w50', 'groupStyle' => 'width:48%;'],
                    'wizard' => [
                        ['tl_content', 'pagePicker'],
                    ],
                ],
            ],
        ],
    ],
    'sql' => 'blob NULL',
];
