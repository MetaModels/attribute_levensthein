<?php

/**
 * This file is part of MetaModels/attribute_levensthein.
 *
 * (c) 2012-2016 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeLevensthein
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute\Levensthein;

use MetaModels\Attribute\BaseComplex;
use MetaModels\Attribute\IAttribute;

/**
 * This class implements an general purpose search index for MetaModels to be searched with LevenstheinSearch algorithm.
 */
class AttributeLevensthein extends BaseComplex
{
    /**
     * The index to work with.
     *
     * @var LevenstheinIndex
     */
    private $index;

    /**
     * The index to work with.
     *
     * @var LevenstheinIndexLookup
     */
    private $indexLookup;

    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array('levensthein_distance', 'levensthein_attributes'));
    }

    /**
     * {@inheritdoc}
     *
     * This method is a no-op in this class.
     *
     * @codeCoverageIgnore
     */
    public function parseValue($arrRowData, $strOutputFormat = 'text', $objSettings = null)
    {
        return array($strOutputFormat => null);
    }

    /**
     * {@inheritdoc}
     *
     * This method is a no-op in this class.
     *
     * @codeCoverageIgnore
     */
    public function getFilterOptions($idList, $usedOnly, &$count = null)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     *
     * This method is a no-op in this class.
     *
     * @codeCoverageIgnore
     */
    public function getDataFor($idList)
    {
        // No op - this attribute is not meant to be manipulated.
        return null;
    }

    /**
     * This method is a no-op in this class.
     *
     * @param mixed[int] $values Unused.
     *
     * @return void
     *
     * @SuppressWarnings("unused")
     * @codeCoverageIgnore
     */
    public function setDataFor($values)
    {
        // No op - this attribute is not meant to be manipulated.
    }

    /**
     * Delete all values for the given items.
     *
     * @param int[] $idList The ids of the items to remove votes for.
     *
     * @return void
     */
    public function unsetDataFor($idList)
    {
        // FIXME: delete search index for passed ids.
    }

    /**
     * Search the index with levensthein algorithm.
     *
     * The standard wildcards * (many characters) and ? (a single character) are supported.
     *
     * @param string $pattern The search pattern to search.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     */
    public function searchFor($pattern)
    {
        $index = $this->getLookup();

        return $index->search(
            $this->getMetaModel()->getActiveLanguage(),
            $pattern
        );
    }

    /**
     * {@inheritdoc}
     */
    public function modelSaved($item)
    {
        $indexer   = $this->getIndex();
        $blacklist = $this->getBlackList();
        $metaModel = $this->getMetaModel();
        $language  = $metaModel->getActiveLanguage();
        // Parse the value as text representation for each attribute.
        foreach ($this->getIndexedAttributes() as $attribute) {
            $value = $item->parseAttribute($attribute->getColName());
            $indexer->updateIndex(
                $value['text'],
                $attribute,
                $item->get('id'),
                $language,
                $blacklist
            );
        }
    }

    /**
     * Search the index with levensthein algorithm.
     *
     * The standard wildcards * (many characters) and ? (a single character) are supported.
     *
     * @param string $pattern The search pattern to search.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     */
    public function getSuggestions($pattern)
    {
        $index = $this->getLookup();

        return $index->getSuggestions(
            $this->getMetaModel()->getActiveLanguage(),
            $pattern
        );
    }

    /**
     * Retrieve the list of attributes we index.
     *
     * @return IAttribute[]
     */
    private function getIndexedAttributes()
    {
        $metaModel  = $this->getMetaModel();
        $attributes = array();
        foreach ($this->get('levensthein_attributes') as $attributeName) {
            $attribute = $metaModel->getAttribute($attributeName);
            if ($attribute) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Retrieve the index instance.
     *
     * @return LevenstheinIndex
     */
    private function getIndex()
    {
        if (!isset($this->index)) {
            $this->index = new LevenstheinIndex($this->getMetaModel()->getServiceContainer()->getDatabase());
        }

        return $this->index;
    }

    /**
     * Retrieve the index lookup instance.
     *
     * @return LevenstheinIndexLookup
     */
    private function getLookup()
    {
        if (!isset($this->indexLookup)) {
            $this->indexLookup = new LevenstheinIndexLookup(
                $this->getMetaModel()->getServiceContainer()->getDatabase(),
                $this->getIndexedAttributes(),
                $this->get('levensthein_distance')
            );
        }

        return $this->indexLookup;
    }

    /**
     * Retrieve the blacklist.
     *
     * @return Blacklist
     */
    private function getBlackList()
    {
        $blacklist = new Blacklist();
        $blacklist->addLanguage(
            'en',
            array (
                'a',
                'an',
                'any',
                'are',
            )
        );

        return $blacklist;
    }
}
