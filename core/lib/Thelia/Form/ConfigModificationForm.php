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

use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Model\LangQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Validator\Constraints\GreaterThan;

class ConfigModificationForm extends BaseForm
{
    use StandardDescriptionFieldsTrait;

    protected function buildForm()
    {
        $this->formBuilder
            ->add("id", "hidden", array(
                    "constraints" => array(
                        new GreaterThan(
                            array('value' => 0)
                        )
                    )
            ))
            ->add("name", "text", array(
                "constraints" => array(
                    new NotBlank()
                ),
                "label" => "Name",
                "label_attr" => array(
                    "for" => "name"
                )
            ))
            ->add("value", "text", array(
                "label" => "Value",
                "label_attr" => array(
                    "for" => "value"
                )
            ))
            ->add("hidden", "hidden", array())
            ->add("secured", "hidden", array(
                "label" => "Prevent variable modification or deletion, except for super-admin"
            ))
         ;

        // Add standard description fields
        $this->addStandardDescFields();
    }

    public function getName()
    {
        return "thelia_config_modification";
    }
}
