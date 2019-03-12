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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */
namespace MetaModels\AttributeLevenshteinBundle\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\AbstractSimpleAttributeTypeFactory;
use MetaModels\Helper\TableManipulator;

/**
 * Attribute type factory for levensthein attributes.
 */
class LevenshteinAttributeTypeFactory extends AbstractSimpleAttributeTypeFactory
{
    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection, TableManipulator $tableManipulator)
    {
        parent::__construct($connection, $tableManipulator);

        $this->typeName  = 'levensthein';
        $this->typeIcon  = 'bundles/metamodelsattributelevenshtein/levensthein.png';
        $this->typeClass = AttributeLevenshtein::class;
    }
}
