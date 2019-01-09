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

namespace MetaModels\AttributeLevenshteinBundle\Controller\Frontend;

use Contao\CoreBundle\Exception\PageNotFoundException;
use MetaModels\AttributeLevenshteinBundle\Attribute\AttributeLevenshtein;
use MetaModels\Factory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller handles the ajax supported search for the levenshtein filter.
 */
class AjaxSearch
{
    /**
     * The MetaModels factory.
     *
     * @var Factory
     */
    private $factory;

    /**
     * AjaxSearch constructor.
     *
     * @param Factory $factory The MetaModels factory.
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Perform ajax search
     *
     * @param string  $table   The MetaModel table name.
     * @param string  $attr    The attribute id.
     * @param Request $request The request.
     *
     * @return JsonResponse The json that get processed in the auto suggestions input.
     *
     * @throws PageNotFoundException When no keyword has been given or the MetaModel/attribute does not exist.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function __invoke($table, $attr, Request $request)
    {
        $search   = $request->query->get('search');
        $language = $request->query->get('language');
        if (null === $search) {
            throw new PageNotFoundException('No "search" keyword in query given.');
        }

        if (null !== $language) {
            $GLOBALS['TL_LANGUAGE'] = $language;
        }

        $metaModel = $this->factory->getMetaModel($table);
        if (!$metaModel) {
            throw new PageNotFoundException('MetaModel not found: ' . $table);
        }

        $attribute = $metaModel->getAttributeById($attr);
        if (!$attribute) {
            throw new PageNotFoundException('Attribute not found: ' . $attr);
        }

        /** @var AttributeLevenshtein $attribute */
        $suggestions = $attribute->getSuggestions($search);

        return new JsonResponse(
            array_map(
                function ($word) {
                    return ['value' => $word, 'label' => $word];
                },
                $suggestions
            )
        );
    }
}
