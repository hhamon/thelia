<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Core\Template\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;

use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Log\Tlog;

use Thelia\Model\Base\FeatureProductQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Map\FeatureAvTableMap;
use Thelia\Model\Map\FeatureProductTableMap;
use Thelia\Type\TypeCollection;
use Thelia\Type;

/**
 *
 * FeatureValue loop
 *
 *
 * Class FeatureValue
 * @package Thelia\Core\Template\Loop
 * @author Etienne Roudeix <eroudeix@openstudio.fr>
 */
class FeatureValue extends BaseI18nLoop
{
    public $timestampable = true;

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('feature', null, true),
            Argument::createIntTypeArgument('product', null, true),
            Argument::createIntListTypeArgument('feature_availability'),
            Argument::createBooleanTypeArgument('exclude_feature_availability', 0),
            Argument::createBooleanTypeArgument('exclude_personal_values', 0),
            new Argument(
                'order',
                new TypeCollection(
                    new Type\EnumListType(array('alpha', 'alpha_reverse', 'manual', 'manual_reverse'))
                ),
                'manual'
            )
        );
    }

    /**
     * @param $pagination
     *
     * @return \Thelia\Core\Template\Element\LoopResult
     */
    public function exec(&$pagination)
    {
        $search = FeatureProductQuery::create();

        /* manage featureAv translations */
        $locale = $this->configureI18nProcessing(
            $search,
            array('TITLE', 'CHAPO', 'DESCRIPTION', 'POSTSCRIPTUM'),
            FeatureAvTableMap::TABLE_NAME,
            'FEATURE_AV_ID',
            true
        );

        $feature = $this->getFeature();

        $search->filterByFeatureId($feature, Criteria::EQUAL);

        $product = $this->getProduct();

        $search->filterByProductId($product, Criteria::EQUAL);

        $featureAvailability = $this->getFeature_availability();

        if (null !== $featureAvailability) {
            $search->filterByFeatureAvId($featureAvailability, Criteria::IN);
        }

        $excludeFeatureAvailability = $this->getExclude_feature_availability();
        if($excludeFeatureAvailability == true) {
            $search->filterByFeatureAvId(null, Criteria::NULL);
        }

        $excludeDefaultValues = $this->getExclude_personal_values();
        if($excludeDefaultValues == true) {
            $search->filterByByDefault(null, Criteria::NULL);
        }

        $orders  = $this->getOrder();

        foreach($orders as $order) {
            switch ($order) {
                case "alpha":
                    $search->addAscendingOrderByColumn(FeatureAvTableMap::TABLE_NAME . '_i18n_TITLE');
                    break;
                case "alpha_reverse":
                    $search->addDescendingOrderByColumn(FeatureAvTableMap::TABLE_NAME . '_i18n_TITLE');
                    break;
                case "manual":
                    $search->orderByPosition(Criteria::ASC);
                    break;
                case "manual_reverse":
                    $search->orderByPosition(Criteria::DESC);
                    break;
            }
        }

        $featureValues = $this->search($search, $pagination);

        $loopResult = new LoopResult($featureValues);

        foreach ($featureValues as $featureValue) {
            $loopResultRow = new LoopResultRow($loopResult, $featureValue, $this->versionable, $this->timestampable, $this->countable);
            $loopResultRow->set("ID", $featureValue->getId());

            $loopResultRow
                ->set("LOCALE",$locale)
                ->set("PERSONAL_VALUE", $featureValue->getByDefault())
                ->set("TITLE",$featureValue->getVirtualColumn(FeatureAvTableMap::TABLE_NAME . '_i18n_TITLE'))
                ->set("CHAPO", $featureValue->getVirtualColumn(FeatureAvTableMap::TABLE_NAME . '_i18n_CHAPO'))
                ->set("DESCRIPTION", $featureValue->getVirtualColumn(FeatureAvTableMap::TABLE_NAME . '_i18n_DESCRIPTION'))
                ->set("POSTSCRIPTUM", $featureValue->getVirtualColumn(FeatureAvTableMap::TABLE_NAME . '_i18n_POSTSCRIPTUM'))
                ->set("POSITION", $featureValue->getPosition());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}