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

use Patchwork\Utf8;

/**
 * This class represents a list of forbidden words.
 */
class Blacklist
{
    /**
     * The language codes.
     *
     * @var string
     */
    private $languages = array();

    /**
     * The list of words.
     *
     * @var array<string,string>
     */
    private $words = array();

    /**
     * Add a language to the blacklist.
     *
     * @param string $language The language code.
     *
     * @param array  $list     The initial list of words.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the language has already been added.
     */
    public function addLanguage($language, $list = array())
    {
        if ($this->hasLanguage($language)) {
            throw new \InvalidArgumentException('Language ' . $language . ' already registered.');
        }

        $this->languages[$language] = $language;

        foreach ($list as $word) {
            $this->addWord($language, $word);
        }
    }

    /**
     * Check if a language has been registered.
     *
     * @param string $language The language code.
     *
     * @return bool
     */
    public function hasLanguage($language)
    {
        return isset($this->languages[$language]);
    }

    /**
     * Add a word to the given language.
     *
     * @param string $language The language code.
     *
     * @param string $word     The word to add.
     *
     * @return void
     */
    public function addWord($language, $word)
    {
        $word = Utf8::strtolower($word);

        $this->words[$language][$word] = $word;
    }

    /**
     * Retrieve the word list as associative array (key == word, value == relevance).
     *
     * @param WordList $list The list to filter.
     *
     * @return array<string,int>
     */
    public function filter(WordList $list)
    {
        $language = $list->getLanguage();
        $words    = array();
        foreach ($list->getWords() as $word) {
            if (!$this->matches($language, $word)) {
                $words[] = $word;
            }
        }

        return $words;
    }

    /**
     * Check if a certain word is blacklisted.
     *
     * @param string $language The language code.
     *
     * @param string $word     The word to check.
     *
     * @return bool
     */
    public function matches($language, $word)
    {
        return isset($this->words[$language][Utf8::strtolower($word)]);
    }
}
