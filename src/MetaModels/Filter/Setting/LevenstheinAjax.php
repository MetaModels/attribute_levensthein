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
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_levensthein/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Attribute\Levensthein\AttributeLevensthein;
use MetaModels\MetaModelsServiceContainer;

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
    public function handle()
    {
        $input = \Input::getInstance();
        if (($attr = $input->get('mm_levensthein_search'))
            && ($table = $input->get('mm_levensthein_model'))
            && ($search = $input->get('search'))
        ) {
            $GLOBALS['TL_LANGUAGE'] = $input->get('mm_levensthein_language');

            /** @var MetaModelsServiceContainer $container */
            $container = $GLOBALS['container']['metamodels-service-container'];

            $metaModel = $container->getFactory()->getMetaModel($table);
            if (!$metaModel) {
                echo 'MetaModel ' . $table;
                return;
            }
            $attribute = $metaModel->getAttributeById($attr);
            if (!$attribute) {
                return;
            }
            /** @var AttributeLevensthein $attribute */

            $suggestions = $attribute->getSuggestions($search);

            header('Content-Type: application/json');
            echo json_encode(
                array_map(function ($word) {
                    return array('value' => $word, 'label' => $word);
                }, $suggestions)
            );
            exit;
        }
    }
}
