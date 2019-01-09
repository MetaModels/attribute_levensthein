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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Filter\Setting;

/**
 * Attribute type factory for text filter settings.
 */
class FilterSettingLevenstheinTypeFactory extends AbstractFilterSettingTypeFactory
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this
            ->setTypeName('levensthein')
            ->setTypeIcon('system/modules/metamodelsattribute_levensthein/html/filter_levensthein.png')
            ->setTypeClass('MetaModels\Filter\Setting\LevenstheinSearchSetting')
            ->allowAttributeTypes('levensthein');
    }
}
