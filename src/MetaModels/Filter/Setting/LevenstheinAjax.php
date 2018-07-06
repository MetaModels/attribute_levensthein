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
class LevenstheinAjax extends \Frontend
{
    /**
     * Initialize the object.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function __construct()
    {
        // Load the user object before calling the parent constructor
        $this->import('FrontendUser', 'User');
        parent::__construct();

        // Check whether a user is logged in
        define('BE_USER_LOGGED_IN', $this->getLoginStatus('BE_USER_AUTH'));
        define('FE_USER_LOGGED_IN', $this->getLoginStatus('FE_USER_AUTH'));

        // No back end user logged in
        if (!$_SESSION['DISABLE_CACHE']) {
            // Maintenance mode (see #4561 and #6353)
            if (\Config::get('maintenanceMode')) {
                header('HTTP/1.1 503 Service Unavailable');
                die_nicely('be_unavailable', 'This site is currently down for maintenance. Please come back later.');
            }

            // Disable the debug mode (see #6450)
            \Config::set('debugMode', false);
        }
    }

    /**
     * Handle the request.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @SuppressWarnings(PHPMD.ExitExpression)
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
