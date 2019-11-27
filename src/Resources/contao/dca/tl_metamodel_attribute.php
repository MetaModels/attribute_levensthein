<?php

/**
 * This file is part of MetaModels/attribute_levensthein.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_levensthein
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['levensthein extends _complexattribute_'] = array(
    '+advanced' => array(
        '-isvariant',
        '-isunique',
        'levensthein_distance',
        'levensthein_attributes'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['levensthein_distance'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => array
    (
        'disableSorting'     => true,
        'minCount'           => 1,
        'maxCount'           => 1,
        'columnFields' => array
        (
            'wordLength' => array
            (
                'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance_wordlength'],
                'inputType' => 'text',
                'eval'      => array
                (
                    'rgxp'   => 'digit',
                    'style'  => 'width:115px',
                )
            ),
            'distance'   => array
            (
                'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_distance_distance'],
                'inputType' => 'select',
                'options'   => array(0,1,2,3,4,5,6,7,8,9,10),
                'default'   => '2',
                'eval'      => array
                (
                    'includeBlankOption' => true,
                    'style'              => 'width:115px',
                    'chosen'             => 'true'
                )
            ),
        ),
        'tl_class' => 'w50 w50x'
    ),
    'sql'                     => 'mediumtext NULL',
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['levensthein_attributes'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['levensthein_attributes'],
    'exclude'                 => true,
    'inputType'               => 'checkboxWizard',
    'eval'                    => array('multiple' => true, 'tl_class' => 'w50 w50x'),
    'sql'                     => 'blob NULL',
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['list']['operations']['rebuild_levensthein'] = array(
    'label' => $GLOBALS['TL_LANG']['tl_metamodel_attribute']['rebuild_levensthein'],
    'href'  => 'act=rebuild_levensthein',
    'icon'  => 'system/modules/metamodelsattribute_levensthein/html/levensthein.png'
);
