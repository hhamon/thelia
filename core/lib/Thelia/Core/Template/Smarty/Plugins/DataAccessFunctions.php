<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
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

namespace Thelia\Core\Template\Smarty\Plugins;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;
use Thelia\Model\CategoryQuery;
use Thelia\Model\ContentQuery;
use Thelia\Model\FolderQuery;
use Thelia\Model\Product;
use Thelia\Model\ProductQuery;
use Thelia\Model\Tools\ModelCriteriaTools;
use Thelia\Tools\DateTimeFormat;

/**
 * Implementation of data access to main Thelia objects (users, cart, etc.)
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 *
 */
class DataAccessFunctions extends AbstractSmartyPlugin
{
    private $securityContext;
    protected $parserContext;
    protected $request;

    public function __construct(Request $request, SecurityContext $securityContext, ParserContext $parserContext)
    {
        $this->securityContext = $securityContext;
        $this->parserContext = $parserContext;
        $this->request = $request;
    }

    /**
     * Provides access to the current logged administrator attributes using the accessors.
     *
     * @param  array   $params
     * @param  unknown $smarty
     * @return string  the value of the requested attribute
     */
    public function adminDataAccess($params, &$smarty)
    {
         return $this->dataAccess("Admin User", $params, $this->securityContext->getAdminUser());
    }

     /**
      * Provides access to the current logged customer attributes throught the accessor
      *
      * @param  array $params
      * @param  unknown $smarty
      * @return string the value of the requested attribute
      */
     public function customerDataAccess($params, &$smarty)
     {
         return $this->dataAccess("Customer User", $params, $this->securityContext->getCustomerUser());
     }

    public function productDataAccess($params, &$smarty)
    {
        $productId = $this->request->get('product_id');

        if($productId !== null) {

            $search = ProductQuery::create()
                ->filterById($productId);

            return $this->dataAccessWithI18n("Product",  $params, $search);
        }
    }

    public function categoryDataAccess($params, &$smarty)
    {
        $categoryId = $this->request->get('category_id');

        if($categoryId !== null) {

            $search = CategoryQuery::create()
                ->filterById($categoryId);

            return $this->dataAccessWithI18n("Category",  $params, $search);
        }
    }

    public function contentDataAccess($params, &$smarty)
    {
        $contentId = $this->request->get('content_id');

        if($contentId !== null) {

            $search = ContentQuery::create()
                ->filterById($contentId);

            return $this->dataAccessWithI18n("Content",  $params, $search);
        }
    }

    public function folderDataAccess($params, &$smarty)
    {
        $folderId = $this->request->get('folder_id');

        if($folderId !== null) {

            $search = FolderQuery::create()
                ->filterById($folderId);

            return $this->dataAccessWithI18n("Folder",  $params, $search);
        }
    }

    /**
     * @param               $objectLabel
     * @param               $params
     * @param ModelCriteria $search
     * @param array         $columns
     * @param null          $foreignTable
     * @param string        $foreignKey
     *
     * @return string
     */
    protected function dataAccessWithI18n($objectLabel, $params, ModelCriteria $search, $columns = array('TITLE', 'CHAPO', 'DESCRIPTION', 'POSTSCRIPTUM'), $foreignTable = null, $foreignKey = 'ID')
    {
        $lang = $this->getNormalizedParam($params, array('lang'));
        if($lang === null) {
            $lang = $this->request->getSession()->getLang()->getId();
        }

        ModelCriteriaTools::getI18n(
            false,
            $lang,
            $search,
            $this->request->getSession()->getLang()->getLocale(),
            $columns,
            $foreignTable,
            $foreignKey,
            true
        );

        $data = $search->findOne();

        $noGetterData = array();
        foreach($columns as $column) {
            $noGetterData[$column] = $data->getVirtualColumn('i18n_' . $column);
        }

        return $this->dataAccess($objectLabel, $params, $data, $noGetterData);
    }

    /**
     * @param       $objectLabel
     * @param       $params
     * @param       $data
     * @param array $noGetterData
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function dataAccess($objectLabel, $params, $data, $noGetterData = array())
    {
        $attribute = $this->getNormalizedParam($params, array('attribute', 'attrib', 'attr'));

        if (! empty($attribute)) {

            if (null != $data) {

                $keyAttribute = strtoupper($attribute);
                if(array_key_exists($keyAttribute, $noGetterData)) {
                    return $noGetterData[$keyAttribute];
                }

                $getter = sprintf("get%s", ucfirst($attribute));
                if (method_exists($data, $getter)) {
                    $return =  $data->$getter();

                    if($return instanceof \DateTime) {
                        if (array_key_exists("format", $params)) {
                            $format = $params["format"];
                        } else {
                            $format = DateTimeFormat::getInstance($this->request)->getFormat(array_key_exists("output", $params) ? $params["output"] : null);
                        }

                        $return = $return->format($format);
                    }

                    return $return;
                }

                throw new \InvalidArgumentException(sprintf("%s has no '%s' attribute", $objectLabel, $attribute));

            }
        }

        return '';
    }

    /**
     * Define the various smarty plugins hendled by this class
     *
     * @return an array of smarty plugin descriptors
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor('function', 'admin', $this, 'adminDataAccess'),
            new SmartyPluginDescriptor('function', 'customer', $this, 'customerDataAccess'),
            new SmartyPluginDescriptor('function', 'product', $this, 'productDataAccess'),
            new SmartyPluginDescriptor('function', 'category', $this, 'categoryDataAccess'),
            new SmartyPluginDescriptor('function', 'content', $this, 'contentDataAccess'),
            new SmartyPluginDescriptor('function', 'folder', $this, 'folderDataAccess'),
        );
    }
}
