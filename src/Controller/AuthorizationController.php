<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-admin for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-admin/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-admin/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Admin\Controller;

use Laminas\ApiTools\Admin\Model\AuthorizationModel;
use Laminas\ApiTools\Admin\Model\AuthorizationModelFactory;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\ApiTools\ContentNegotiation\ViewModel;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use RuntimeException;

class AuthorizationController extends AbstractActionController
{
    protected $factory;

    protected $model;

    protected $moduleName;

    public function __construct(AuthorizationModelFactory $factory)
    {
        $this->factory = $factory;
    }

    public function authorizationAction()
    {
        $request = $this->getRequest();
        $version = $request->getQuery('version', 1);
        $model   = $this->getModel();

        switch ($request->getMethod()) {
            case $request::METHOD_GET:
                $entity = $model->fetch($version);
                break;
            case $request::METHOD_PUT:
                $this->getResponse()->getHeaders()->addHeaderLine(
                    'X-Deprecated',
                    'This service has deprecated the PUT method; please use PATCH'
                );
                // intentionally fall through
            case $request::METHOD_PATCH:
                $entity = $model->update($this->bodyParams(), $version);
                break;
            default:
                return new ApiProblemResponse(
                    new ApiProblem(405, 'Only the methods GET and PUT are allowed for this URI')
                );
        }

        $entity = new Entity($entity, null);
        $entity->getLinks()->add(Link::factory(array(
            'rel'   => 'self',
            'route' => array(
                'name'    => 'api-tools/api/module/authorization',
                'params'  => array(
                    'name' => $this->moduleName,
                ),
                'options' => array(
                    'query' => array(
                        'version' => $version,
                    ),
                ),
            )
        )));
        return new ViewModel(array('payload' => $entity));
    }

    /**
     * @return AuthorizationModel
     */
    public function getModel()
    {
        if ($this->model instanceof AuthorizationModel) {
            return $this->model;
        }

        $this->model = $this->factory->factory($this->getModuleName());
        return $this->model;
    }

    /**
     * @return string
     * @throws RuntimeException if module name is not present in route matches
     */
    public function getModuleName()
    {
        if (null !== $this->moduleName) {
            return $this->moduleName;
        }

        $event = $this->getEvent();
        if (! $event instanceof MvcEvent) {
            throw new RuntimeException(sprintf(
                '%s cannot operate correctly without a composed MvcEvent',
                __CLASS__
            ));
        }

        $matches    = $event->getRouteMatch();
        $moduleName = $matches->getParam('name', false);
        if (!$moduleName) {
            throw new RuntimeException(sprintf(
                '%s cannot operate correctly without a "name" segment in the route matches',
                __CLASS__
            ));
        }
        $this->moduleName = $moduleName;
        return $moduleName;
    }

    /**
     * Set the request object manually
     *
     * Provided for testing.
     *
     * @param  Request $request
     * @return self
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }
}
