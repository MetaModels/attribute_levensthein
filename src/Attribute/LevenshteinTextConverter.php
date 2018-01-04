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
 * This class is used to convert a text into a word list to be used in a search index.
 */
class LevenshteinTextConverter
{
    /**
     * The blacklist as language => words list.
     *
     * @var Blacklist
     */
    private $blacklist;

    /**
     * The language code we are working on.
     *
     * @var string
     */
    private $language;

    /**
     * Create a new instance.
     *
     * @param Blacklist $blacklist The list of forbidden words.
     *
     * @param string    $language  The language code we are working on.
     */
    public function __construct(Blacklist $blacklist, $language)
    {
        $this->blacklist = $blacklist;
        $this->language  = $language;
    }

    /**
     * Process some text and return the resulting word list.
     *
     * @param string $text The text to convert.
     *
     * @return WordList
     */
    public function process($text)
    {
        return new WordList($this->language, $this->filterWords($this->sanitizeText($text)));
    }

    /**
     * Prepare text to be bare of any special chars and split the words.
     *
     * @param string $text The text to prepare.
     *
     * @return string[]
     */
    private function sanitizeText($text)
    {
        $content = str_replace(
            array("\n", "\r", "\t", '&#160;', '&nbsp;', '&shy;'),
            array(' ', ' ', ' ', ' ', ' ', ''),
            $text
        );

        // Remove quotes
        $content = str_replace(array('´', '`'), "'", $content);
        $content = mb_eregi_replace('[^[:alnum:]\'\.:,\+_-]|- | -|\' | \'|\. |\.$|: |:$|, |,$', ' ', $content);
        return preg_split('/ +/', utf8_strtolower($content));
    }

    /**
     * Filter the passed word list against the blacklist and trim all words from inter punctuation.
     *
     * @param string[] $words The words to filter.
     *
     * @return int[string]
     */
    private function filterWords($words)
    {
        $list = array();
        foreach ($words as $word) {
            $word = $this->convertWord($word);

            if (null === $word || $this->blacklist->matches($this->language, $word)) {
                continue;
            }

            if (isset($list[$word])) {
                $list[$word]++;
                continue;
            }

            $list[$word] = 1;
        }

        return $list;
    }

    /**
     * Perform sanitation on a word.
     *
     * @param string $word The word to sanitize.
     *
     * @return string|null
     */
    private function convertWord($word)
    {
        // Strip a leading plus.
        if (strncmp($word, '+', 1) === 0) {
            $word = substr($word, 1);
        }
        $word = trim($word);
        if (!strlen($word) || preg_match('/^[\.:,\'_-]+$/', $word)) {
            return null;
        }
        if (preg_match('/^[\':,]/', $word)) {
            $word = substr($word, 1);
        }
        if (preg_match('/[\':,\.]$/', $word)) {
            $word = substr($word, 0, -1);
        }

        return $word;
    }
}
