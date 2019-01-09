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
 * This class implements an general purpose search string parser.
 */
class SearchStringParser
{
    /**
     * The search string.
     *
     * @var string
     */
    private $string;

    /**
     * All keywords that must be contained.
     *
     * @var string[]
     */
    private $must = array();

    /**
     * All keywords that must NOT be contained.
     *
     * @var string[]
     */
    private $mustNot = array();

    /**
     * All literal representations that must be contained.
     *
     * @var string[]
     */
    private $literal = array();

    /**
     * All keywords to search for.
     *
     * @var string[]
     */
    private $keyword = array();

    /**
     * The last token from the input string.
     *
     * @var string
     */
    private $partial;

    /**
     * Create a new instance.
     *
     * @param string $string        The search string to tokenize.
     *
     * @param bool   $omitLastToken If true, the last token will be ignored as it is partial.
     */
    public function __construct($string, $omitLastToken = false)
    {
        $this->string = $string;

        $this->parseSearchString($omitLastToken);
    }

    /**
     * Parse the search string into searchable tokens.
     *
     * @param bool $omitLastToken If true, the last token will be ignored as it is partial.
     *
     * @throws \Exception When the search string is empty.
     *
     * @return void
     */
    private function parseSearchString($omitLastToken)
    {
        // Clean the keywords
        $searchString = $this->string;
        if (function_exists('mb_eregi_replace')) {
            $searchString = mb_eregi_replace('[^[:alnum:] \*\+\'"\.:,_-]|\. |\.$|: |:$|, |,$', ' ', $searchString);
        } else {
            $searchString = preg_replace(
                array(
                    '/\. /',
                    '/\.$/',
                    '/: /',
                    '/:$/',
                    '/, /',
                    '/,$/',
                    '/[^\pN\pL \*\+\'"\.:,_-]/u'
                ),
                ' ',
                $searchString
            );
        }
        // Check keyword string
        if (!strlen($searchString)) {
            throw new \Exception('Empty keyword string');
        }
        // Split keywords
        $chunks = array();
        preg_match_all('/"[^"]+"|[\+\-]?[^ ]+\*?/', $searchString, $chunks);

        if ($omitLastToken) {
            $this->partial = utf8_strtolower(array_pop($chunks[0]));
        }
        if (empty($chunks)) {
            return;
        }

        foreach ($chunks[0] as $match) {
            $this->handleToken($match);
        }

        if (!$omitLastToken) {
            $this->partial = utf8_strtolower(array_pop($chunks[0]));
        }
    }

    /**
     * Handle a parsed token and add it to the correct field.
     *
     * @param string $token The token to process.
     *
     * @return void
     */
    private function handleToken($token)
    {
        $token = trim($token);

        if ($token[0] == '"' && substr($token, -1) == '"') {
            $this->addLiteralToken(substr($token, 1, -1));
            return;
        }
        if ($token[0] === '-') {
            $this->addMustNotToken(substr($token, 1));
            return;
        }

        if ($token[0] === '+') {
            $this->addMustToken(substr($token, 1));
            return;
        }

        $this->addKeywordToken($token);
    }

    /**
     * Add a token.
     *
     * @param string $token The token.
     *
     * @return void
     */
    private function addLiteralToken($token)
    {
        $value = trim($token);

        if (empty($value)) {
            return;
        }

        $this->literal[$value] = $value;

        foreach (explode(' ', $value) as $subToken) {
            $this->addMustToken($subToken);
        }
    }

    /**
     * Add a token.
     *
     * @param string $token The token.
     *
     * @return void
     */
    private function addKeywordToken($token)
    {
        $value = Utf8::strtolower(trim($token));

        if (empty($value)) {
            return;
        }

        $this->keyword[$value] = $value;
    }

    /**
     * Add a token.
     *
     * @param string $token The token.
     *
     * @return void
     */
    private function addMustToken($token)
    {
        $value = Utf8::strtolower(trim($token));

        if (empty($value)) {
            return;
        }

        $this->must[$value] = $value;

        $this->addKeywordToken($value);
    }

    /**
     * Add a keyword.
     *
     * @param string $token The token.
     *
     * @return void
     */
    private function addMustNotToken($token)
    {
        $value = Utf8::strtolower(trim($token));

        if (empty($value)) {
            return;
        }

        $this->mustNot[$value] = $value;
    }

    /**
     * Retrieve all keywords.
     *
     * @param int $minLength The minimum length for keywords.
     *
     * @param int $maxLength The maximum length for keywords.
     *
     * @return \string[]
     */
    public function getKeywords($minLength = 0, $maxLength = 0)
    {
        if (0 === $minLength && 0 === $maxLength) {
            return array_values($this->keyword);
        }

        $values = array();
        foreach ($this->keyword as $keyword) {
            if (0 !== $minLength && strlen($keyword) < $minLength) {
                continue;
            }
            if (0 !== $maxLength && strlen($keyword) > $maxLength) {
                continue;
            }

            $values[] = $keyword;
        }

        return $values;
    }

    /**
     * Retrieve all MUST NOT keywords.
     *
     * @param int $minLength The minimum length for keywords.
     *
     * @param int $maxLength The maximum length for keywords.
     *
     * @return \string[]
     */
    public function getMustNot($minLength = 0, $maxLength = 0)
    {
        if (0 === $minLength && 0 === $maxLength) {
            return array_values($this->mustNot);
        }

        $values = array();
        foreach ($this->mustNot as $keyword) {
            if (0 !== $minLength && strlen($keyword) < $minLength) {
                continue;
            }
            if (0 !== $maxLength && strlen($keyword) > $maxLength) {
                continue;
            }

            $values[] = $keyword;
        }

        return $values;
    }

    /**
     * Retrieve all MUST keywords.
     *
     * @param int $minLength The minimum length for keywords.
     *
     * @param int $maxLength The maximum length for keywords.
     *
     * @return \string[]
     */
    public function getMust($minLength = 0, $maxLength = 0)
    {
        if (0 === $minLength && 0 === $maxLength) {
            return array_values($this->must);
        }

        $values = array();
        foreach ($this->must as $keyword) {
            if (0 !== $minLength && strlen($keyword) < $minLength) {
                continue;
            }
            if (0 !== $maxLength && strlen($keyword) > $maxLength) {
                continue;
            }

            $values[] = $keyword;
        }

        return $values;
    }

    /**
     * Check if a word is listed as "MUST NOT".
     *
     * @param string $word The word to check.
     *
     * @return bool
     */
    public function isMustNot($word)
    {
        return isset($this->mustNot[Utf8::strtolower(trim($word))]);
    }

    /**
     * Retrieve all literal search values.
     *
     * @return \string[]
     */
    public function getLiterals()
    {
        return array_values($this->literal);
    }

    /**
     * Retrieve the last token from the parsed string.
     *
     * @return string
     */
    public function getPartial()
    {
        return $this->partial;
    }
}
