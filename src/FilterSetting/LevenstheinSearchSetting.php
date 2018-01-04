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

namespace MetaModels\AttributeLevenshteinBundle\FilterSetting;

use MetaModels\Attribute\Levensthein\AttributeLevensthein;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Rule\LevenstheinSearchRule;
use MetaModels\FrontendIntegration\FrontendFilterOptions;

/**
 * Filter attributes for keywords using the LevenstheinSearch algorithm.
 */
class LevenstheinSearchSetting extends SimpleLookup
{
    /**
     * Overrides the parent implementation to always return true, as this setting is always optional.
     *
     * @return bool true if all matches shall be returned, false otherwise.
     */
    public function allowEmpty()
    {
        return true;
    }

    /**
     * Overrides the parent implementation to always return true, as this setting is always available for FE filtering.
     *
     * @return bool true as this setting is always available.
     */
    public function enableFEFilterWidget()
    {
        return true;
    }

    /**
     * Retrieve the filter parameter name to react on.
     *
     * @return string
     */
    protected function getParamName()
    {
        if ($this->get('urlparam')) {
            return $this->get('urlparam');
        }

        $objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));
        if ($objAttribute) {
            return $objAttribute->getColName();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareRules(IFilter $filter, $filterUrl)
    {
        $metaModel = $this->getMetaModel();
        $attribute = $metaModel->getAttributeById($this->get('attr_id'));
        $paramName = $this->getParamName();
        $value     = $filterUrl[$paramName];

        if ($attribute && $paramName && $value) {
            /** @var AttributeLevensthein $attribute */
            $filter->addFilterRule(new LevenstheinSearchRule($attribute, $value));
            return;
        }

        $filter->addFilterRule(new StaticIdList(null));
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return ($strParamName = $this->getParamName()) ? array($strParamName) : array();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterNames()
    {
        if (($strParamName = $this->getParamName())) {
            return array(
                $strParamName => ($this->get('label') ? $this->get('label') : $this->getParamName())
            );
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterWidgets(
        $arrIds,
        $arrFilterUrl,
        $arrJumpTo,
        FrontendFilterOptions $objFrontendFilterOptions
    ) {
        $arrReturn = array();
        $paramName = $this->getParamName();
        $tableName = $this->getMetaModel()->getTableName();
        $attrId    = $this->get('attr_id');
        $language  = $this->getMetaModel()->getActiveLanguage();
        $this->addFilterParam($paramName);

        // Address search.
        $arrCount  = array();
        $arrWidget = array(
            'label'     => array(
                ($this->get('label') ? $this->get('label') : $paramName),
                'GET: ' . $paramName
            ),
            'inputType' => 'text',
            'count'     => $arrCount,
            'showCount' => $objFrontendFilterOptions->isShowCountValues(),
            'eval'      => array(
                'colname'  => $this->getMetaModel()->getAttributeById($attrId)->getColName(),
                'urlparam' => $paramName,
                'template' => $this->get('template'),
            )
        );
        $objFrontendFilterOptions->setAutoSubmit(false);

        $arrReturn[$paramName] =
            $this->prepareFrontendFilterWidget($arrWidget, $arrFilterUrl, $arrJumpTo, $objFrontendFilterOptions);

        $GLOBALS['TL_JQUERY'][] = <<<EOF
  <script>
  $(function() {
    function split(val) {
        var arr = val.match(/\w+|"[^"]+"/g),
            i   = arr ? arr.length : 0;
        while(i--){
            arr[i] = arr[i].replace(/"/g,"");
        }
        return arr ? arr : [];
    }
    function extractLast(term) {
      var chunks = split(term);
      return chunks.length ? chunks.pop() : '';
    }

    $("#ctrl_$paramName")
      // don't navigate away from the field on tab when selecting an item
        .bind("keydown", function(event) {
            if (event.keyCode === $.ui.keyCode.TAB && $(this).autocomplete("instance").menu.active) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function(request, response) {
                $.getJSON(
                    "mm_lv_search.php",
                    {
                        mm_levensthein_model: "$tableName",
                        mm_levensthein_search: "$attrId",
                        mm_levensthein_language: "$language",
                        search: request.term
                    },
                    response
                );
            },
            search: function() {
                // custom minLength
                var term = extractLast(this.value);
                if (term.length < 2) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function(event, ui) {
                var terms = split(this.value);
                // remove the current input
                var last = terms.pop();
                // add the selected item
                if (last.charAt(0) === '"') { // FIXME: this is currently always false.
                    terms.push('"' + ui.item.value);
                } else {
                    terms.push(ui.item.value);
                }
                // add placeholder to get the space at the end
                terms.push("");
                this.value = terms.join(" ");
                return false;
            }
        });
    });
</script>
EOF;

        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterDCA()
    {
        return array();
    }

    /**
     * Add Param to global filter params array.
     *
     * @param string $strParam Name of filter param.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function addFilterParam($strParam)
    {
        $GLOBALS['MM_FILTER_PARAMS'][] = $strParam;
    }
}
