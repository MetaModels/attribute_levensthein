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

namespace MetaModels\DcGeneral\Events\Table\Attribute;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\GetReferrerEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\DecodePropertyValueForWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetOperationButtonEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\IdSerializer;
use ContaoCommunityAlliance\DcGeneral\DcGeneralEvents;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use MetaModels\DcGeneral\Events\BaseSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles event operations.
 */
class AttributeLevenstheinSubscriber extends BaseSubscriber
{
    /**
     * Register all listeners to handle creation of a data container.
     *
     * @return void
     */
    protected function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                GetOperationButtonEvent::NAME,
                array(
                    $this,
                    'hideButton'
                )
            )
            ->addListener(
                DcGeneralEvents::ACTION,
                array(
                    $this,
                    'regenerateSearchIndex'
                )
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                array($this, 'getOptions')
            )
            ->addListener(
                EncodePropertyValueFromWidgetEvent::NAME,
                array($this, 'saveDistances')
            )
            ->addListener(
                DecodePropertyValueForWidgetEvent::NAME,
                array($this, 'loadDistances')
            );
    }

    /**
     * Hide the rebuild button for non levensthein attributes.
     *
     * @param GetOperationButtonEvent $event The event to process.
     *
     * @return void
     */
    public function hideButton(GetOperationButtonEvent $event)
    {
        if ($event->getModel()->getProviderName() !== 'tl_metamodel_attribute'
            || $event->getCommand()->getName() !== 'rebuild_levensthein') {
            return;
        }

        if ($event->getModel()->getProperty('type') !== 'levensthein') {
            $event->setDisabled();
        }
    }

    /**
     * Perform the action.
     *
     * @param ActionEvent $event The event to process.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function regenerateSearchIndex(ActionEvent $event)
    {
        if ($event->getAction()->getName() !== 'rebuild_levensthein') {
            return;
        }

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = func_get_arg(2);

        $input     = $event->getEnvironment()->getInputProvider();
        $metaModel = $this->getMetaModelById(IdSerializer::fromSerialized($input->getParameter('pid'))->getId());
        $attribute = $metaModel->getAttributeById(IdSerializer::fromSerialized($input->getParameter('id'))->getId());
        $database  = $metaModel->getServiceContainer()->getDatabase();

        $entries = $database
            ->prepare('SELECT id FROM tl_metamodel_levensthein WHERE metamodel=?')
            ->execute($metaModel->get('id'))
            ->fetchEach('id');

        $database
            ->prepare('DELETE FROM tl_metamodel_levensthein WHERE metamodel=?')
            ->execute($metaModel->get('id'));
        if (!empty($entries)) {
            $database
                ->prepare(
                    sprintf(
                        'DELETE FROM tl_metamodel_levensthein_index WHERE pid IN (%s)',
                        implode(', ', array_fill(0, count($entries), '?'))
                    )
                )
                ->execute($entries);
        }

        $languageBackup = $GLOBALS['TL_LANGUAGE'];
        foreach ($metaModel->getAvailableLanguages() as $language) {
            $GLOBALS['TL_LANGUAGE'] = $language;
            foreach ($metaModel->findByFilter(null) as $item) {
                $attribute->modelSaved($item);
            }
        }
        $GLOBALS['TL_LANGUAGE'] = $languageBackup;

        $count = $database->prepare('SELECT
          COUNT(word)
          FROM tl_metamodel_levensthein_index
          WHERE attribute=?
          GROUP BY word')->execute($attribute->get('id'))->fetchField(0);

        $refererEvent = new GetReferrerEvent(true, 'tl_metamodel_attribute');
        $dispatcher->dispatch(ContaoEvents::SYSTEM_GET_REFERRER, $refererEvent);

        $event->setResponse(
            sprintf(
                '<div id="tl_buttons">
    <a href="%1$s" class="header_back" title="%2$s" accesskey="b" onclick="Backend.getScrollOffset();">
        %2$s
    </a>
</div>
<div class="tl_listing_container levensthein_reindex">
            The search index now contains %3$d words.
</div>
',
                $refererEvent->getReferrerUrl(),
                $GLOBALS['TL_LANG']['MSC']['backBT'],
                $count
            )
        );
    }

    /**
     * Check if the event is intended for us.
     *
     * @param GetPropertyOptionsEvent $event The event to test.
     *
     * @return bool
     */
    private function isEventForMe(GetPropertyOptionsEvent $event)
    {
        $input = $event->getEnvironment()->getInputProvider();
        $type  = $event->getModel()->getProperty('type');

        if ($input->hasValue('type')) {
            $type = $input->getValue('type');
        }

        if (empty($type)) {
            $type = $event->getModel()->getProperty('type');
        }

        return
            ($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($type !== 'levensthein')
            || ($event->getPropertyName() !== 'levensthein_attributes');
    }

    /**
     * Retrieve the options for the attributes.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getOptions(GetPropertyOptionsEvent $event)
    {
        if (self::isEventForMe($event)) {
            return;
        }

        $model       = $event->getModel();
        $metaModelId = $model->getProperty('pid');
        if (!$metaModelId) {
            $metaModelId = IdSerializer::fromSerialized(
                $event->getEnvironment()->getInputProvider()->getValue('pid')
            )->getId();
        }

        $factory       = $this->getServiceContainer()->getFactory();
        $metaModelName = $factory->translateIdToMetaModelName($metaModelId);
        $metaModel     = $factory->getMetaModel($metaModelName);

        if (!$metaModel) {
            return;
        }

        $result = array();

        // Fetch all attributes except for the current attribute.
        foreach ($metaModel->getAttributes() as $attribute) {
            if ($attribute->get('id') === $model->getId()) {
                continue;
            }

            $result[$attribute->getColName()] = sprintf(
                '%s [%s]',
                $attribute->getName(),
                $attribute->get('type')
            );
        }

        $event->setOptions($result);
    }

    /**
     * Encode the value arrey.
     *
     * @param EncodePropertyValueFromWidgetEvent $event The values.
     *
     * @return void
     */
    public function saveDistances(EncodePropertyValueFromWidgetEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty() !== 'levensthein_distance')
        ) {
            return;
        }

        $processed = array();

        foreach (deserialize($event->getValue(), true) as $value) {
            $processed[$value['wordLength']] = $value['distance'];
        }

        ksort($processed);

        $event->setValue(serialize($processed));
    }

    /**
     * Encode the value arrey.
     *
     * @param DecodePropertyValueForWidgetEvent $event The values.
     *
     * @return void
     */
    public function loadDistances(DecodePropertyValueForWidgetEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getProperty() !== 'levensthein_distance')
        ) {
            return;
        }

        $processed = array();

        foreach ((array) $event->getValue() as $wordLength => $distance) {
            $processed[] = array('wordLength' => $wordLength, 'distance' => $distance);
        }

        $event->setValue(serialize($processed));
    }
}
