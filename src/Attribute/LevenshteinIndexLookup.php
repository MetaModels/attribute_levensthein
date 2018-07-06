<?php

/**
 * This file is part of MetaModels/attribute_levenshtein.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeLevenstheinBundle
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levenshtein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\AttributeLevenshteinBundle\Attribute;

use Contao\Database;
use Contao\Database\Result;
use MetaModels\Attribute\IAttribute;
use MetaModels\Attribute\ITranslated;
use Patchwork\Utf8;

/**
 * This class implements an general purpose search index for MetaModels to be searched with LevenstheinSearch algorithm.
 */
class LevenshteinIndexLookup
{
    /**
     * The database connection to use.
     *
     * @var Database
     */
    private $database;

    /**
     * The list of valid attributes.
     *
     * @var IAttribute[]
     */
    private $attributeList;

    /**
     * The maximum allowed levensthein distance.
     *
     * @var array
     */
    private $maxDistance;

    /**
     * Minimum length of words to be added to the index.
     *
     * @var int
     */
    private $minLength = 3;

    /**
     * Maximum length of words to be added to the index.
     *
     * @var int
     */
    private $maxLength = 20;

    /**
     * Create a new instance.
     *
     * @param Database     $database      The database instance to use.
     *
     * @param IAttribute[] $attributeList The list of valid attributes.
     *
     * @param string       $language      The language key.
     *
     * @param string       $pattern       The pattern to search.
     *
     * @param array        $maxDistance   The maximum allowed levensthein distance.
     *
     * @return string[]
     */
    public static function filter(Database $database, $attributeList, $language, $pattern, $maxDistance = array(0 => 2))
    {
        $instance = new static($database, $attributeList, $maxDistance);

        return $instance->search($language, $pattern);
    }

    /**
     * Create a new instance.
     *
     * @param Database     $database      The database instance to use.
     *
     * @param IAttribute[] $attributeList The list of valid attributes.
     *
     * @param int[]        $maxDistance   The maximum allowed levensthein distance.
     */
    public function __construct(Database $database, $attributeList, $maxDistance = array(0 => 2))
    {
        $this->database      = $database;
        $this->attributeList = $attributeList;
        $this->maxDistance   = $maxDistance;
    }

    /**
     * Search for occurences and return the ids of matching items.
     *
     * @param string $language The language key.
     *
     * @param string $pattern  The pattern to search.
     *
     * @return string[]
     */
    public function search($language, $pattern)
    {
        return $this->searchInternal($language, new SearchStringParser($pattern));
    }

    /**
     * Compile a list of best matching suggestions of words for all items that have been returned by the search.
     *
     * @param string $language The language key.
     *
     * @param string $pattern  The pattern to search.
     *
     * @return string[]
     */
    public function getSuggestions($language, $pattern)
    {
        // Chop off the last word as it is the beginning of a new word.
        $parser    = new SearchStringParser($pattern, true);
        $items     = $this->searchInternal($language, $parser);
        $procedure = array();
        $params    = array();
        $partial   = $parser->getPartial();

        if (in_array($partial[0], array('+', '-', '""'))) {
            $partial = substr($partial, 1);
        }

        if (!empty($items)) {
            $procedure[] = sprintf(
                'tl_metamodel_levensthein.item IN (%1$s)',
                implode(',', array_fill(0, count($items), '?'))
            );
            $params      = array_merge($params, $items);
        }

        $attributeIds = array();
        foreach ($this->attributeList as $attribute) {
            $attributeIds[] = $attribute->get('id');
        }

        $procedure[] = '(tl_metamodel_levensthein_index.language=?)';
        $procedure[] = sprintf(
            '(tl_metamodel_levensthein_index.attribute IN (%1$s))',
            implode(',', array_fill(0, count($attributeIds), '?'))
        );
        $procedure[] = '(word LIKE ?)';
        $params      = array_merge(
            $params,
            array($language),
            $attributeIds,
            array($partial . '%'),
            $attributeIds
        );
        $query       = sprintf(
            'SELECT DISTINCT word ' .
            'FROM tl_metamodel_levensthein_index ' .
            'LEFT JOIN tl_metamodel_levensthein ON (tl_metamodel_levensthein.id=tl_metamodel_levensthein_index.pid)' .
            'WHERE ' . implode(' AND ', $procedure) .
            'ORDER BY FIELD(tl_metamodel_levensthein_index.attribute,%1$s),word',
            implode(',', array_fill(0, count($attributeIds), '?'))
        );

        $result = $this->database
            ->prepare($query)
            ->execute($params);

        return $result->fetchEach('word');
    }

    /**
     * Search using the given search parser.
     *
     * @param string             $language The language key.
     *
     * @param SearchStringParser $parser   The parser to use.
     *
     * @return string[]|null
     */
    private function searchInternal($language, $parser)
    {
        $results = new ResultSet();

        $attributeIds = array();
        foreach ($this->attributeList as $attribute) {
            $attributeIds[] = $attribute->get('id');
        }

        $this->getLiteralMatches($language, $parser, $results);
        $this->getMustIds($attributeIds, $language, $parser, $results);
        $this->getMatchingKeywords($attributeIds, $language, $parser, $results);
        $this->getMustNotIds($attributeIds, $language, $parser, $results);

        if (!($parser->getLiterals() || $parser->getMust() || $parser->getKeywords())) {
            $metaModel = $this->attributeList[0]->getMetaModel();
            $ids       = $metaModel->getIdsFromFilter($metaModel->getEmptyFilter());
            foreach ($this->attributeList as $attribute) {
                $results->addResults('-all-', $attribute, $ids);
            }
        }
        $items = $results->getCombinedResults($this->attributeList);

        if (array_filter($items)) {
            return $items;
        }

        // Try via levensthein now.
        $this->getLevenstheinCandidates($attributeIds, $parser, $results);

        return $results->getCombinedResults($this->attributeList);
    }

    /**
     * Retrieve the items matching the literal search patterns.
     *
     * @param string             $language  The language key.
     *
     * @param SearchStringParser $parser    The parser to use.
     *
     * @param ResultSet          $resultSet The result set to add to.
     *
     * @return void
     */
    private function getLiteralMatches($language, SearchStringParser $parser, ResultSet $resultSet)
    {
        $literals = $parser->getLiterals();
        foreach ($literals as $literal) {
            foreach ($this->attributeList as $attribute) {
                if ($attribute instanceof ITranslated) {
                    $results = $attribute->searchForInLanguages('*' . $literal . '*', array($language));
                } else {
                    $results = $attribute->searchFor('*' . $literal . '*');
                }

                if (null === $results) {
                    $metaModel = $this->attributeList[0]->getMetaModel();
                    $results   = $metaModel->getIdsFromFilter($metaModel->getEmptyFilter());
                }

                $resultSet->addResults(
                    '"' . $literal . '"',
                    $attribute,
                    $results ?: array()
                );
            }
        }
    }

    /**
     * Find exact matches of chunks and return the parent ids.
     *
     * @param string[]           $attributeIds The attributes to search on.
     *
     * @param string             $language     The language key.
     *
     * @param SearchStringParser $parser       The chunks to search for.
     *
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getMatchingKeywords($attributeIds, $language, $parser, ResultSet $resultSet)
    {
        if ($parser->getKeywords() == array()) {
            return;
        }

        foreach ($parser->getKeywords() as $word) {
            $searchWord = str_replace(
                array('*', '?'),
                array('%', '_'),
                str_replace(
                    array('%', '_'),
                    array('\%', '\_'),
                    $word
                )
            ) . '%';

            $parameters   = array_merge(array($language), $attributeIds);
            $parameters[] = $this->normalizeWord($searchWord);
            $parameters[] = $searchWord;
            $parameters   = array_merge($parameters, $attributeIds);
            $query        = sprintf(
                'SELECT attribute,item FROM tl_metamodel_levensthein WHERE id IN (SELECT pid
                FROM tl_metamodel_levensthein_index
                WHERE language=?
                AND attribute IN (%1$s)
                AND (transliterated LIKE ? OR word LIKE ?)
                ) ORDER BY FIELD(attribute,%1$s)',
                implode(',', array_fill(0, count($attributeIds), '?'))
            );

            $query = $this->database
                ->prepare($query)
                ->execute($parameters);

            if (!$query->numRows) {
                foreach ($attributeIds as $attribute) {
                    $resultSet->addResult($word, $attribute, 0);
                }
            }

            while ($query->next()) {
                if (!empty($query->attribute) && !empty($query->item)) {
                    $resultSet->addResult($word, $query->attribute, $query->item);
                }
            }
        }
    }

    /**
     * Find exact matches of chunks and return the parent ids.
     *
     * @param string[]           $attributeIds The attributes to search on.
     *
     * @param string             $language     The language key.
     *
     * @param SearchStringParser $parser       The chunks to search for.
     *
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getMustIds($attributeIds, $language, $parser, ResultSet $resultSet)
    {
        if (($must = $parser->getMust()) === array()) {
            return;
        }

        $parameters = array_merge(array($language), $attributeIds);
        $attributes = implode(',', array_fill(0, count($attributeIds), '?'));
        $sql        = sprintf(
            'SELECT attribute,item FROM tl_metamodel_levensthein WHERE id IN (
                SELECT pid
                    FROM tl_metamodel_levensthein_index
                    WHERE language=?
                    AND attribute IN (%1$s)
                    AND (%2$s)
                    ORDER BY FIELD(attribute,%1$s),word
            )',
            $attributes,
            'transliterated=? OR word=?'
        );

        foreach ($must as $word) {
            $query = $this->database
                ->prepare($sql)
                ->execute(array_merge($parameters, array($this->normalizeWord($word), $word), $attributeIds));

            // If no matches, add an empty array.
            if ($query->numRows === 0) {
                foreach ($attributeIds as $attribute) {
                    $resultSet->addMustResult($word, $attribute, array());
                }
                continue;
            }

            while ($query->next()) {
                if (!empty($query->attribute) && !empty($query->item)) {
                    $resultSet->addMustResult($word, $query->attribute, $query->item);
                }
            }
        }
    }

    /**
     * Find exact matches of chunks and return the parent ids.
     *
     * @param string[]           $attributeIds The attributes to search on.
     *
     * @param string             $language     The language key.
     *
     * @param SearchStringParser $parser       The chunks to search for.
     *
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getMustNotIds($attributeIds, $language, $parser, ResultSet $resultSet)
    {
        if (($must = $parser->getMustNot()) === array()) {
            return;
        }

        $parameters = array_merge(array($language), $attributeIds);
        $attributes = implode(',', array_fill(0, count($attributeIds), '?'));
        $sql        = sprintf(
            'SELECT attribute,item FROM tl_metamodel_levensthein WHERE id IN (
                SELECT pid
                    FROM tl_metamodel_levensthein_index
                    WHERE language=?
                    AND attribute IN (%1$s)
                    AND (%2$s)
                    ORDER BY FIELD(attribute,%1$s),word
            )',
            $attributes,
            'transliterated=? OR word=?'
        );

        foreach ($must as $word) {
            $query = $this->database
                ->prepare($sql)
                ->execute(array_merge($parameters, array($this->normalizeWord($word), $word), $attributeIds));

            while ($query->next()) {
                if (!empty($query->attribute) && !empty($query->item)) {
                    $resultSet->addNegativeResult($word, $query->attribute, $query->item);
                }
            }
        }
    }

    /**
     * Retrieve all words from the search index valid as levensthein candidates.
     *
     * @param string[]           $attributeIds The ids of the attributes to query.
     *
     * @param SearchStringParser $parser       The chunks to search for.
     *
     * @param ResultSet          $resultSet    The result set to add to.
     *
     * @return void
     */
    private function getLevenstheinCandidates($attributeIds, $parser, ResultSet $resultSet)
    {
        $words = $parser->getKeywords($this->minLength, $this->maxLength);
        $query = sprintf(
            'SELECT li.transliterated, li.word, li.pid, li.attribute, l.item
                FROM tl_metamodel_levensthein_index AS li
                RIGHT JOIN tl_metamodel_levensthein AS l ON (l.id=li.pid)
                WHERE
                li.attribute IN (%1$s)
                AND LENGTH(transliterated) BETWEEN ? AND ?
                ORDER BY word
                ',
            implode(',', array_fill(0, count($attributeIds), '?'))
        );

        foreach ($words as $chunk) {
            if ($resultSet->hasResultsFor($chunk)) {
                continue;
            }

            $transChunk = $this->normalizeWord($chunk);
            $wordlength = strlen($transChunk);
            $distance   = $this->getAllowedDistanceFor($transChunk);
            $minLen     = ($wordlength - $distance);
            $maxLen     = ($wordlength + $distance);

            // Easy out, word is too short or too long.
            if (($wordlength < $this->minLength) || ($wordlength > $this->maxLength)) {
                continue;
            }

            $results = $this->database
                ->prepare($query)
                ->execute(array_merge($attributeIds, array($minLen, $maxLen)));

            $this->processCandidates($resultSet, $chunk, $results, $distance);
        }
    }

    /**
     * Process the result list and add acceptable entries to the list.
     *
     * @param ResultSet $resultSet The result list to add to.
     * @param string    $chunk     The chunk being processed.
     * @param Result    $results   The results to process.
     * @param int       $distance  The acceptable distance.
     *
     * @return void
     */
    private function processCandidates(ResultSet $resultSet, $chunk, Result $results, $distance)
    {
        while ($results->next()) {
            if (empty($results->attribute) || empty($results->item)) {
                continue;
            }

            if (!empty($results->transliterated)) {
                $trans = $results->transliterated;

                if ($this->isAcceptableByLevenshtein($chunk, $trans, $distance)) {
                    $resultSet->addResult($chunk, $results->attribute, $results->item);
                }
            }
        }
    }

    /**
     * Determine the allowed distance for the given word.
     *
     * @param string $word The word.
     *
     * @return int
     */
    private function getAllowedDistanceFor($word)
    {
        $length   = strlen($word);
        $distance = 0;

        foreach ($this->maxDistance as $minimumLength => $allowedDistance) {
            if ($minimumLength > $length) {
                break;
            }

            $distance = $allowedDistance;
        }

        return $distance;
    }

    /**
     * Check if the passed value is an acceptable entry.
     *
     * @param string $chunk    Transliterated version of the chunk being searched.
     *
     * @param string $trans    Transliterated version of the matched entry.
     *
     * @param int    $distance The maximum levensthein distance allowed.
     *
     * @return bool
     */
    private function isAcceptableByLevenshtein($chunk, $trans, $distance)
    {
        // Length too short.
        if (strlen($trans) <= $this->minLength) {
            return false;
        }
        // Result has different Type (Prevent matches like XX = 01).
        if (is_numeric($trans) && !is_numeric($chunk)) {
            return false;
        }
        $lev = levenshtein($chunk, $trans);
        if (0 === $lev) {
            return true;
        }
        // Distance too far.
        if ($lev > $distance) {
            return false;
        }

        return true;
    }

    /**
     * Normalize a word to plain ASCII representation.
     *
     * @param string $word The word to convert.
     *
     * @return string
     */
    private function normalizeWord($word)
    {
        if (mb_detect_encoding($word) == 'ASCII') {
            return $word;
        }

        return Utf8::toAscii($word);
    }
}
