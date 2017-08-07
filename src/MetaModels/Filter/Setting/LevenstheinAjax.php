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
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Attribute\Levensthein\AttributeLevensthein;
use MetaModels\MetaModelsServiceContainer;
use SimpleAjax\Event\SimpleAjax as SimpleAjaxEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This class handles the ajax requests.
 */
class LevenstheinAjax
{
    /**
     * Handle the request.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function handle(SimpleAjaxEvent $event)
    {
        $input = \Input::getInstance();
        if (!($attr = $input->get('mm_levensthein_search'))
            || !($table = $input->get('mm_levensthein_model'))
            || !($search = $input->get('search'))
        ) {
            return;
        }

        $GLOBALS['TL_LANGUAGE'] = $input->get('mm_levensthein_language');

        /** @var MetaModelsServiceContainer $container */
        $container = $GLOBALS['container']['metamodels-service-container'];

        /** @var AttributeLevensthein $attribute */
        $metaModel = $container->getFactory()->getMetaModel($table);
        $attribute = $metaModel->getAttributeById($attr);
        if (!$metaModel || !$attribute) {
            return;
        }

        $suggestions = $attribute->getSuggestions($search);
        $return      = array_map(
            function ($word) {
                return ['value' => $word, 'label' => $word];
            },
            $suggestions
        );

        $response = new JsonResponse($return);
        $event->setResponse($response);
    }
}
