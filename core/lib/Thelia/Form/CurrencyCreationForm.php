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
namespace Thelia\Form;

use Symfony\Component\Validator\Constraints;
use Thelia\Model\CurrencyQuery;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CurrencyCreationForm extends BaseForm
{
    protected function buildForm($change_mode = false)
    {
        $code_constraints = array(new Constraints\NotBlank());

        if (!$change_mode) {
            $code_constraints[] = new Constraints\Callback(array(
                "methods" => array(array($this, "checkDuplicateCode"))
            ));
        }

        $this->formBuilder
            ->add("name"   , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => "Name *",
                "label_attr" => array(
                    "for" => "name"
                ))
            )
            ->add("locale" , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ))
            )
            ->add("symbol" , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => "Symbol *",
                "label_attr" => array(
                    "for" => "symbol"
                ))
            )
            ->add("rate"   , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => "Rate from &euro; *",
                "label_attr" => array(
                    "for" => "rate"
                ))
            )
            ->add("code"   , "text"  , array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => "ISO 4217 code *",
                "label_attr" => array(
                    "for" => "iso_4217_code"
                ))
            )
        ;
    }

    public function getName()
    {
        return "thelia_currency_creation";
    }

    public function checkDuplicateCode($value, ExecutionContextInterface $context)
    {
        $currency = CurrencyQuery::create()->findOneByCode($value);

        if ($currency) {
            $context->addViolation(sprintf("A currency with code \"%s\" already exists.", $value));
        }
    }

}
