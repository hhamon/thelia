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
namespace Thelia\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

use Thelia\Core\Security\SecurityContext;
use Thelia\Tools\URL;
use Thelia\Tools\Redirect;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Event\ActionEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Thelia\Form\BaseForm;
use Thelia\Form\Exception\FormValidationException;
use Symfony\Component\EventDispatcher\Event;
use Thelia\Core\Event\DefaultActionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 * The defaut administration controller. Basically, display the login form if
 * user is not yet logged in, or back-office home page if the user is logged in.
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */

class BaseController extends ContainerAware
{

    /**
     * Return an empty response (after an ajax request, for example)
     */
    protected function nullResponse()
    {
        return new Response();
    }

    /**
     * Dispatch a Thelia event
     *
     * @param string    $eventName a TheliaEvent name, as defined in TheliaEvents class
     * @param Event     $event the action event, or null (a DefaultActionEvent will be dispatched)
     */
    protected function dispatch($eventName, ActionEvent $event = null)
    {
        if ($event == null) $event = new DefaultActionEvent();

        $this->getDispatcher()->dispatch($eventName, $event);
    }

    /**
     * Return the event dispatcher,
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     *
     * return the Translator
     *
     * @return mixed \Thelia\Core\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->container->get('thelia.translator');
    }

    /**
     * Return the parser context,
     *
     * @return ParserContext
     */
    protected function getParserContext()
    {
        return $this->container->get('thelia.parser.context');
    }

    /**
     * Return the security context, by default in admin mode.
     *
     * @return \Thelia\Core\Security\SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->container->get('thelia.securityContext');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Returns the session from the current request
     *
     * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected function getSession()
    {
        $request = $this->getRequest();

        return $request->getSession();
    }

    /**
     * Get all errors that occured in a form
     *
     * @param \Symfony\Component\Form\Form $form
     * @return string the error string
     */
    private function getErrorMessages(\Symfony\Component\Form\Form $form) {

        $errors = '';

        foreach ($form->getErrors() as $key => $error) {
            $errors .= $error->getMessage() . ', ';
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors .= $this->getErrorMessages($child) . ', ';
            }
        }

        return rtrim($errors, ', ');
    }

    /**
     * Validate a BaseForm
     *
     * @param  BaseForm                     $aBaseForm      the form
     * @param  string                       $expectedMethod the expected method, POST or GET, or null for any of them
     * @throws FormValidationException      is the form contains error, or the method is not the right one
     * @return \Symfony\Component\Form\Form Form the symfony form object
     */
    protected function validateForm(BaseForm $aBaseForm, $expectedMethod = null)
    {
        $form = $aBaseForm->getForm();

        if ($expectedMethod == null || $aBaseForm->getRequest()->isMethod($expectedMethod)) {

            $form->bind($aBaseForm->getRequest());

            if ($form->isValid()) {
                return $form;
            }
            else {
                throw new FormValidationException(sprintf("Missing or invalid data: %s", $this->getErrorMessages($form)));
            }
        }
        else {
            throw new FormValidationException(sprintf("Wrong form method, %s expected.", $expectedMethod));
        }
    }

    /**
     *
     * redirect request to the specified url
     *
     * @param string $url
     */
    public function redirect($url, $status = 302)
    {
        Redirect::exec($url, $status);
    }

    /**
     * If success_url param is present in request or in the provided form, redirect to this URL.
     *
     * @param BaseForm $form a base form, which may contains the success URL
     */
    protected function redirectSuccess(BaseForm $form = null)
    {
        if ($form != null) {
            $url = $form->getSuccessUrl();
        }
        else {
            $url = $this->getRequest()->get("success_url");
        }

        echo "url=$url";

        if (null !== $url) $this->redirect($url);
    }

    /**
     * Get a route path from the route id.
     *
     * @param $routerName
     * @param $routeId
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function getRouteFromRouter($routerName, $routeId) {
        $route = $this->container->get($routerName)->getRouteCollection()->get($routeId);

        if ($route == null) {
            throw new \InvalidArgumentException(sprintf("Route ID '%s' does not exists.", $routeId));
        }

        return $route->getPath();
    }

    /**
     * Return a 404 error
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function pageNotFound()
    {
        throw new NotFoundHttpException();
    }
}