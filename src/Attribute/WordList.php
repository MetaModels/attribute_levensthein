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

/**
 * This class represents a word list of sanitized words.
 */
class WordList
{
    /**
     * The language code.
     *
     * @var string
     */
    private $language;

    /**
     * The list of words.
     *
     * @var array<string,int>
     */
    private $words;

    /**
     * The SHA-1 checksum of the words.
     *
     * @var string
     */
    private $checksum;

    /**
     * Create a new instance.
     *
     * @param string            $language The language code.
     *
     * @param array<string,int> $words    The list of words (key is the word, value the relevance).
     */
    public function __construct($language, $words)
    {
        $this->language = (string) $language;
        $this->words    = (array) $words;
    }

    /**
     * Calculate a checksum over the word list.
     *
     * @return string
     */
    public function checksum()
    {
        if (!isset($this->checksum)) {
            $this->checksum = sha1(implode('', $this->words));
        }

        return $this->checksum;
    }

    /**
     * Retrieve the word list as associative array (key == word, value == relevance).
     *
     * @return array<string,int>
     */
    public function getWords()
    {
        return $this->words;
    }

    /**
     * Get the defined language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Retrieve the length of the word list.
     *
     * @return int
     */
    public function count()
    {
        return count($this->words);
    }
}
