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

namespace MetaModels\AttributeLevenshteinBundle\EventListener\DcGeneral\Table\Attribute;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use Doctrine\DBAL\Connection;
use MetaModels\IFactory;
use MetaModels\IMetaModel;

/**
 * This provides a way to obtain a MetaModel.
 */
abstract class AbstractListener extends AbstractAbstainingListener
{
    /**
     * The MetaModel factory.
     *
     * @var IFactory
     */
    protected $factory;

    /**
     * The database connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Create a new instance.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The scope determinator.
     * @param IFactory                 $factory           The MetaModel factory.
     * @param Connection               $connection        The database connection.
     */
    public function __construct(
        RequestScopeDeterminator $scopeDeterminator,
        IFactory $factory,
        Connection $connection
    ) {
        parent::__construct($scopeDeterminator);
        $this->factory    = $factory;
        $this->connection = $connection;
    }

    /**
     * Retrieve the MetaModel instance.
     *
     * @param string $metaModelId The MetaModel id.
     *
     * @return IMetaModel
     *
     * @throws \RuntimeException When the factory has not been set.
     */
    protected function getMetaModel($metaModelId)
    {
        if (null === $this->factory) {
            throw new \RuntimeException('No factory set.');
        }

        $metaModelName = $this->factory->translateIdToMetaModelName($metaModelId);
        $metaModel     = $this->factory->getMetaModel($metaModelName);

        return $metaModel;
    }
}
