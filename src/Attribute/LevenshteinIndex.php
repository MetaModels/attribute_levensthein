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

use Contao\Database;
use MetaModels\Attribute\IAttribute;
use Patchwork\Utf8;

/**
 * This class implements an general purpose search index for MetaModels to be searched with LevenstheinSearch algorithm.
 */
class LevenshteinIndex
{
    /**
     * The database connection to use.
     *
     * @var Database
     */
    private $database;

    /**
     * Create a new instance.
     *
     * @param Database $database The database instance to use.
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Add an text to the search index.
     *
     * @param string     $text      The text to index.
     *
     * @param IAttribute $attribute The attribute the text originated from.
     *
     * @param string     $itemId    The id of the item the text originates from.
     *
     * @param string     $language  The language key.
     *
     * @param Blacklist  $blacklist The blacklist to use.
     *
     * @return void
     */
    public function updateIndex($text, $attribute, $itemId, $language, Blacklist $blacklist)
    {
        $converter = new LevenshteinTextConverter($blacklist, $language);
        $words     = $converter->process($text);
        $entry     = $this->lookUpEntry($attribute, $itemId, $language);

        if (false === $entry) {
            $entry = $this->createEntry($attribute, $itemId, $language, $words->checksum());
        } elseif ($words->checksum() === $entry['checksum']) {
            return;
        }

        $this->addWords($entry, $words);
    }

    /**
     * Look up an entry in the database.
     *
     * @param IAttribute $attribute The attribute.
     *
     * @param string     $itemId    The item id.
     *
     * @param string     $language  The language code.
     *
     * @return array
     */
    private function lookUpEntry($attribute, $itemId, $language)
    {
        return $this->database
            ->prepare(
                'SELECT * FROM tl_metamodel_levensthein WHERE metamodel=? AND attribute=? AND item=? AND language=?'
            )
            ->execute($attribute->getMetaModel()->get('id'), $attribute->get('id'), $itemId, $language)
            ->fetchAssoc();
    }

    /**
     * Look up an entry in the database.
     *
     * @param IAttribute $attribute The attribute.
     *
     * @param string     $itemId    The item id.
     *
     * @param string     $language  The language code.
     *
     * @param string     $checkSum  The checksum of the word list.
     *
     * @return array
     */
    private function createEntry($attribute, $itemId, $language, $checkSum)
    {
        $this->database
            ->prepare(
                'INSERT INTO tl_metamodel_levensthein %s'
            )
            ->set(
                array(
                    'metamodel' => $attribute->getMetaModel()->get('id'),
                    'attribute' => $attribute->get('id'),
                    'item'      => $itemId,
                    'language'  => $language,
                    'checksum'  => $checkSum
                )
            )
            ->execute();

        return $this->lookUpEntry($attribute, $itemId, $language);
    }

    /**
     * Add the words from the given list to the index.
     *
     * @param array    $entry The entry from the tl_metamodel_levensthein table.
     *
     * @param WordList $words The word list.
     *
     * @return void
     */
    private function addWords($entry, WordList $words)
    {
        // First off, delete all words.
        $this->database->prepare('DELETE FROM tl_metamodel_levensthein_index WHERE pid=?')
            ->execute($entry['id']);

        if ($words->count() === 0) {
            return;
        }

        $language = $words->getLanguage();
        $values   = array();
        foreach ($words->getWords() as $word => $relevance) {
            $values[] = $entry['id'];
            $values[] = $entry['attribute'];
            $values[] = $word;
            $values[] = $this->normalizeWord($word);
            $values[] = $relevance;
            $values[] = $language;
        }

        $this->database
            ->prepare(
                'INSERT INTO tl_metamodel_levensthein_index
                (pid, attribute, word, transliterated, relevance, language)
                VALUES ' . implode(', ', array_fill(0, $words->count(), '(?, ?, ?, ?, ?, ?)'))
            )
            ->execute($values);
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
