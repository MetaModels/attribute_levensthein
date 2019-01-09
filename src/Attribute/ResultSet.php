<?php

/**
 * This file is part of MetaModels/attribute_levenshtein.
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

use MetaModels\Attribute\IAttribute;

/**
 * This class implements an general purpose search result container.
 */
class ResultSet
{
    /**
     * The list of results.
     *
     * @var array[]
     */
    private $results = array();

    /**
     * The list of results.
     *
     * @var array[]
     */
    private $mustResults;

    /**
     * The list of results tha must not be returned.
     *
     * @var array[]
     */
    private $negativeResults = array();

    /**
     * Add some results for a search expression on an attribute.
     *
     * @param string            $searchExpression The expression that was searched.
     *
     * @param IAttribute|string $attribute        The attribute that was searched.
     *
     * @param string[]          $results          The resulting ids.
     *
     * @return void
     */
    public function addResults($searchExpression, $attribute, $results)
    {
        $attributeId = ($attribute instanceof IAttribute) ? $attribute->get('id') : $attribute;
        foreach ($results as $result) {
            $this->addResult($searchExpression, $attributeId, $result);
        }
    }

    /**
     * Add a single result match.
     *
     * @param string $searchExpression The search expression.
     *
     * @param string $attributeId      The attribute id.
     *
     * @param string $result           The matching id.
     *
     * @return void
     */
    public function addResult($searchExpression, $attributeId, $result)
    {
        if (!isset($this->results[$attributeId])) {
            $this->results[$attributeId] = array();
        }

        if (!isset($this->results[$attributeId][$searchExpression])) {
            $this->results[$attributeId][$searchExpression] = array();
        }

        $this->results[$attributeId][$searchExpression][] = $result;
    }

    /**
     * Add a single result match.
     *
     * @param string $searchExpression The search expression.
     *
     * @param string $attributeId      The attribute id.
     *
     * @param string $result           The matching id.
     *
     * @return void
     */
    public function addMustResult($searchExpression, $attributeId, $result)
    {
        if (null === $this->mustResults) {
            $this->mustResults = array();
        }

        if (!isset($this->mustResults[$attributeId])) {
            $this->mustResults[$attributeId] = array();
        }

        if (!isset($this->mustResults[$attributeId][$searchExpression])) {
            $this->mustResults[$attributeId][$searchExpression] = array();
        }

        $this->mustResults[$attributeId][$searchExpression][] = $result;
    }

    /**
     * Add multiple result matches.
     *
     * @param string   $searchExpression The search expression.
     *
     * @param string   $attributeId      The attribute id.
     *
     * @param string[] $results          The resulting ids.
     *
     * @return void
     */
    public function addMustResults($searchExpression, $attributeId, $results)
    {
        if (null === $this->mustResults) {
            $this->mustResults = array();
        }

        if (!isset($this->mustResults[$attributeId])) {
            $this->mustResults[$attributeId] = array();
        }

        if (!isset($this->mustResults[$attributeId][$searchExpression])) {
            $this->mustResults[$attributeId][$searchExpression] = array();
        }

        foreach ($results as $result) {
            $this->mustResults[$attributeId][$searchExpression][] = $result;
        }
    }

    /**
     * Add a single negative result match.
     *
     * @param string $searchExpression The search expression.
     *
     * @param string $attributeId      The attribute id.
     *
     * @param string $result           The matching id.
     *
     * @return void
     */
    public function addNegativeResult($searchExpression, $attributeId, $result)
    {
        if (!isset($this->negativeResults[$attributeId])) {
            $this->negativeResults[$attributeId] = array();
        }

        if (!isset($this->negativeResults[$attributeId][$searchExpression])) {
            $this->negativeResults[$attributeId][$searchExpression] = array();
        }

        $this->negativeResults[$attributeId][$searchExpression][] = $result;
    }

    /**
     * Add multiple negative result matches.
     *
     * @param string   $searchExpression The search expression.
     *
     * @param string   $attributeId      The attribute id.
     *
     * @param string[] $results          The matching id.
     *
     * @return void
     */
    public function addNegativeResults($searchExpression, $attributeId, $results)
    {
        if (!isset($this->negativeResults[$attributeId])) {
            $this->negativeResults[$attributeId] = array();
        }

        if (!isset($this->negativeResults[$attributeId][$searchExpression])) {
            $this->negativeResults[$attributeId][$searchExpression] = array();
        }

        foreach ($results as $result) {
            $this->negativeResults[$attributeId][$searchExpression][] = $result;
        }
    }

    /**
     * Retrieve the combined results.
     *
     * @param IAttribute[] $sortOrder            The attribute to sort the results by.
     *
     * @param bool         $addMissingAttributes If true, missing attributes will be appended to the sort list.
     *
     * @return \string[]
     */
    public function getCombinedResults($sortOrder, $addMissingAttributes = true)
    {
        $attributes = array();
        foreach ($sortOrder as $attribute) {
            $attributes[] = $attribute->get('id');
        }

        // add missing ids.
        if ($addMissingAttributes) {
            $attributes = array_unique(array_merge($attributes, array_keys($this->results)));
        }

        // determine the negative result set.
        $negatives = $this->getNegatives($attributes);

        $matches = array_diff($this->getPositives($attributes), $negatives);

        if (null !== ($must = $this->getMust($attributes))) {
            return array_values(array_intersect($matches, $must));
        }

        return array_values($matches);
    }

    /**
     * Check if there are any results for the given word.
     *
     * @param string $word The word to check.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) - complex method but readable, so we accept a complexity of 10.
     */
    public function hasResultsFor($word)
    {
        $temp = new static();

        foreach ($this->results as $attribute => $attributeResults) {
            foreach ($attributeResults as $expression => $exprMatches) {
                if ($expression === $word) {
                    $temp->addResults($expression, $attribute, $exprMatches);
                    break;
                }
            }
        }

        foreach ((array) $this->mustResults as $attribute => $attributeResults) {
            foreach ($attributeResults as $expression => $exprMatches) {
                if ($expression === $word) {
                    $temp->addMustResults($expression, $attribute, $exprMatches);
                    break;
                }
            }
        }

        foreach ($this->negativeResults as $attribute => $attributeResults) {
            foreach ($attributeResults as $expression => $exprMatches) {
                if ($expression === $word) {
                    $temp->addNegativeResults($expression, $attribute, $exprMatches);
                    break;
                }
            }
        }

        // Filtering because we have to kill any optional placeholder '0' in the results.
        return array() !== array_filter($temp->getCombinedResults(array()));
    }

    /**
     * Retrieve the mandatory ids as array.
     *
     * @param string[] $attributeIds The list of attributes.
     *
     * @return string[]|null
     */
    private function getMust($attributeIds)
    {
        if (null === $this->mustResults) {
            return null;
        }

        $must = array();
        foreach ($attributeIds as $attributeId) {
            if (empty($this->mustResults[$attributeId])) {
                continue;
            }

            foreach ($this->mustResults[$attributeId] as $expression => $exprMatches) {
                if (!isset($must[$expression])) {
                    $must[$expression] = array();
                }

                $must[$expression] = array_unique(array_merge($must[$expression], $exprMatches));
            }
        }

        $results = current($must);

        if (false === $results) {
            return array();
        }

        foreach ($must as $result) {
            $results = array_intersect($results, $result);
        }

        return array_values(array_unique($results));
    }

    /**
     * Retrieve the negative ids as array.
     *
     * @param string[] $attributeIds The list of attributes.
     *
     * @return string[]
     */
    private function getNegatives($attributeIds)
    {
        // determine the negative result set.
        $negatives = array();
        foreach ($attributeIds as $attributeId) {
            if (empty($this->negativeResults[$attributeId])) {
                continue;
            }

            foreach ($this->negativeResults[$attributeId] as $exprMatches) {
                $negatives = array_merge($negatives, $exprMatches);
            }
        }

        return array_values(array_unique($negatives));
    }

    /**
     * Retrieve the positive ids as array.
     *
     * @param string[] $attributeIds The list of attributes.
     *
     * @return string[]
     */
    private function getPositives($attributeIds)
    {
        $positives = array();
        $points    = array();
        $point     = $this->scoreMatrix($attributeIds);
        foreach ($attributeIds as $attributeId) {
            if (empty($this->results[$attributeId])) {
                continue;
            }

            foreach ($this->results[$attributeId] as $expression => $exprMatches) {
                if (!isset($positives[$expression])) {
                    $positives[$expression] = array();
                }

                $positives[$expression] = array_unique(array_merge($positives[$expression], $exprMatches));

                foreach ($exprMatches as $match) {
                    $points[$match] |= $point[$attributeId];
                }
            }
        }

        $results = current($positives);

        if (false === $results) {
            return array();
        }

        if (count($positives) > 1) {
            $results = call_user_func_array('array_intersect', $positives);
        }

        $results = array_values(array_unique($results));
        arsort($points);

        $results = array_intersect(array_keys($points), $results);

        return array_values($results);
    }

    /**
     * Create a bit mask to allow setting of bits for sorting.
     *
     * @param string[] $attributeIds List of attribute ids.
     *
     * @return int[]
     */
    private function scoreMatrix($attributeIds)
    {
        $point = array();

        foreach (array_values(array_reverse($attributeIds)) as $index => $id) {
            $point[$id] = (1 << $index);
        }

        return $point;
    }
}
