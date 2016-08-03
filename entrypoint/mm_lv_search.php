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

// Set the script name
define('TL_SCRIPT', 'mm_lv_search.php');

// Initialize the system
define('TL_MODE', 'FE');
require __DIR__ . '/system/initialize.php';

// Run the controller
$controller = new MetaModels\Filter\Setting\LevenstheinAjax();
$controller->handle();
