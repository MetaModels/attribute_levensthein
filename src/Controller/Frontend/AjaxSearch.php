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

namespace MetaModels\AttributeLevenshteinBundle\Controller\Frontend;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
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
     * @var Factory
     */
    private $factory;

    /**
     * AjaxSearch constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string  $table   The MetaModel table name.
     * @param string  $attr    The attribute id.
     * @param Request $request The request.
     *
     * @return JsonResponse The json that get processed in the auto suggestions input.
     */
    public function __invoke($table, $attr, Request $request)
    {
        $search = Input::get('search');
        if (!$search) {
            throw new PageNotFoundException('No "search" keyword given.');
        }

        $GLOBALS['TL_LANGUAGE'] = Input::get('language');

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
