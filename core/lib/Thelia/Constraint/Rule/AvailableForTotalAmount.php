<?php
/**********************************************************************************/
/*                                                                                */
/*      Thelia	                                                                  */
/*                                                                                */
/*      Copyright (c) OpenStudio                                                  */
/*      email : info@thelia.net                                                   */
/*      web : http://www.thelia.net                                               */
/*                                                                                */
/*      This program is free software; you can redistribute it and/or modify      */
/*      it under the terms of the GNU General Public License as published by      */
/*      the Free Software Foundation; either version 3 of the License             */
/*                                                                                */
/*      This program is distributed in the hope that it will be useful,           */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of            */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             */
/*      GNU General Public License for more details.                              */
/*                                                                                */
/*      You should have received a copy of the GNU General Public License         */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.      */
/*                                                                                */
/**********************************************************************************/

namespace Thelia\Constraint\Rule;

use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Translation\Translator;
use Thelia\Coupon\CouponAdapterInterface;
use Thelia\Constraint\Validator\PriceParam;
use Thelia\Constraint\Validator\RuleValidator;
use Thelia\Exception\InvalidRuleException;
use Thelia\Exception\InvalidRuleOperatorException;
use Thelia\Exception\InvalidRuleValueException;

/**
 * Created by JetBrains PhpStorm.
 * Date: 8/19/13
 * Time: 3:24 PM
 *
 * Rule AvailableForTotalAmount
 * Check if a Checkout total amount match criteria
 *
 * @package Constraint
 * @author  Guillaume MOREL <gmorel@openstudio.fr>
 *
 */
class AvailableForTotalAmount extends CouponRuleAbstract
{
    /** Rule 1st parameter : price */
    CONST PARAM1_PRICE = 'price';

    /** Rule 1st parameter : currency */
    CONST PARAM1_CURRENCY = 'currency';

    /** @var array Available Operators (Operators::CONST) */
    protected $availableOperators = array(
        Operators::INFERIOR,
        Operators::EQUAL,
        Operators::SUPERIOR,
    );

    /** @var PriceParam Price Validator */
    protected $priceValidator = null;

    /**
     * Check if backoffice inputs are relevant or not
     *
     * @throws InvalidRuleOperatorException if Operator is not allowed
     * @throws InvalidRuleValueException    if Value is not allowed
     * @return bool
     */
    public function checkBackOfficeInput()
    {
        if (!isset($this->validators)
            || empty($this->validators)
            ||!isset($this->validators[self::PARAM1_PRICE])
            ||!isset($this->validators[self::PARAM1_PRICE])
        ) {
            throw new InvalidRuleValueException(get_class(), self::PARAM1_PRICE);
        }

        /** @var RuleValidator $ruleValidator */
        $ruleValidator = $this->validators[self::PARAM1_PRICE];
        /** @var PriceParam $price */
        $price = $ruleValidator->getParam();

        if (!$price instanceof PriceParam) {
            throw new InvalidRuleValueException(get_class(), self::PARAM1_PRICE);
        }

        $this->checkBackOfficeInputsOperators();

        return $this->isPriceValid($price->getPrice());
    }

    /**
     * Check if Checkout inputs are relevant or not
     *
     * @throws InvalidRuleValueException if Value is not allowed
     * @return bool
     */
    public function checkCheckoutInput()
    {
        if (!isset($this->paramsToValidate)
            || empty($this->paramsToValidate)
            ||!isset($this->paramsToValidate[self::PARAM1_PRICE])
        ) {
            throw new InvalidRuleValueException(get_class(), self::PARAM1_PRICE);
        }

        $price = $this->paramsToValidate[self::PARAM1_PRICE];

        return $this->isPriceValid($price);
    }

    /**
     * Check if a price is valid
     *
     * @param float $price Price to check
     *
     * @throws InvalidRuleValueException if Value is not allowed
     * @return bool
     */
    protected function isPriceValid($price)
    {
        $priceValidator = $this->priceValidator;
        try {
            $priceValidator->getParam()->compareTo($price);
        } catch(\InvalidArgumentException $e) {
            throw new InvalidRuleValueException(get_class(), self::PARAM1_PRICE);
        }

        return true;
    }

    /**
     * Generate current Rule param to be validated from adapter
     *
     * @return $this
     */
    protected function setParametersToValidate()
    {
        $this->paramsToValidate = array(
            self::PARAM1_PRICE => $this->adapter->getCartTotalPrice()
        );

        return $this;
    }

    /**
     * Get I18n name
     *
     * @return string
     */
    public function getName()
    {
        return $this->adapter->get('thelia.translator')->trans(
            'Cart total amount',
            array(),
            'constraint'
        );
    }

    /**
     * Get I18n tooltip
     *
     * @return string
     */
    public function getToolTip()
    {
        $i18nOperator = Operators::getI18n(
            $this->translator, $this->priceValidator->getOperator()
        );

        $toolTip = $this->translator->trans(
            'If cart total amount is <strong>%operator%</strong> %amount% %currency%',
            array(
                '%operator%' => $i18nOperator,
                '%amount%' => $this->priceValidator->getParam()->getPrice(),
                '%currency%' => $this->priceValidator->getParam()->getCurrency()
            ),
            'constraint'
        );

        return $toolTip;
    }

    /**
     * Populate a Rule from a form admin
     *
     * @param array $operators Rule Operator set by the Admin
     * @param array $values    Rule Values set by the Admin
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public function populateFromForm(array $operators, array $values)
    {
        if ($values[self::PARAM1_PRICE] === null
            || $values[self::PARAM1_CURRENCY] === null
        ) {
            throw new InvalidArgumentException(
                'The Rule ' . get_class() . 'needs at least a quantity set (' . self::PARAM1_PRICE . ', ' . self::PARAM1_CURRENCY . ')'
            );
        }

        $this->priceValidator = new PriceParam(
            $this->adapter,
            $values[self::PARAM1_PRICE],
            $values[self::PARAM1_CURRENCY]
        );

        return $this;
    }

    /**
     * Return a serializable Rule
     *
     * @return SerializableRule
     */
    public function getSerializableRule()
    {
        $serializableRule = new SerializableRule();
        $serializableRule->operators = array(
            self::PARAM1_PRICE => $this->priceValidator->getOperator()
        );

        $serializableRule->values = array(
            self::PARAM1_PRICE => $this->priceValidator->getPrice(),
            self::PARAM1_CURRENCY => $this->priceValidator->getCurrency()
        );

        return $serializableRule;
    }



}