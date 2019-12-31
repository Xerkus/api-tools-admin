<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-admin for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-admin/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-admin/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Admin\Model;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Rest\AbstractResourceListener;
use Laminas\ApiTools\Rest\Exception\CreationException;
use Laminas\ApiTools\Rest\Exception\PatchException;
use RuntimeException;

class RestServiceResource extends AbstractResourceListener
{
    /**
     * @var RestServiceModel
     */
    protected $model;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var RestServiceModelFactory
     */
    protected $restFactory;

    /**
     * @param  RestServiceModelFactory $restFactory
     */
    public function __construct(RestServiceModelFactory $restFactory)
    {
        $this->restFactory = $restFactory;
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

        $moduleName = $this->getEvent()->getRouteParam('name', false);
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
     * @return RestServiceModel
     */
    public function getModel($type = RestServiceModelFactory::TYPE_DEFAULT)
    {
        if ($this->model instanceof RestServiceModel) {
            return $this->model;
        }
        $moduleName = $this->getModuleName();
        $this->model = $this->restFactory->factory($moduleName, $type);
        return $this->model;
    }

    /**
     * Create a new REST service
     *
     * @param  array|object $data
     * @return RestServiceEntity
     * @throws CreationException
     */
    public function create($data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $type = RestServiceModelFactory::TYPE_DEFAULT;
        if (isset($data['table_name'])) {
            $creationData = new DbConnectedRestServiceEntity();
            $type = RestServiceModelFactory::TYPE_DB_CONNECTED;
        } else {
            $creationData = new NewRestServiceEntity();
        }

        $creationData->exchangeArray($data);
        $model = $this->getModel($type);

        try {
            $service = $model->createService($creationData);
        } catch (\Exception $e) {
            throw new CreationException('Unable to create REST service', $e->getCode(), $e);
        }

        return $service;
    }

    /**
     * Fetch REST metadata
     *
     * @param  string $id
     * @return RestServiceEntity|ApiProblem
     */
    public function fetch($id)
    {
        $service = $this->getModel()->fetch($id);
        if (!$service instanceof RestServiceEntity) {
            return new ApiProblem(404, 'REST service not found');
        }
        return $service;
    }

    /**
     * Fetch metadata for all REST services
     *
     * @param  array $params
     * @return RestServiceEntity[]
     */
    public function fetchAll($params = array())
    {
        $version = $this->getEvent()->getQueryParam('version', null);
        return $this->getModel()->fetchAll($version);
    }

    /**
     * Update an existing REST service
     *
     * @param  string $id
     * @param  object|array $data
     * @return ApiProblem|RestServiceEntity
     * @throws PatchException if unable to update configuration
     */
    public function patch($id, $data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return new ApiProblem(400, 'Invalid data provided for update');
        }

        if (empty($data)) {
            return new ApiProblem(400, 'No data provided for update');
        }

        // Make sure we have an entity first
        $model  = $this->getModel();
        $entity = $model->fetch($id);

        $entity->exchangeArray($data);

        try {
            switch (true) {
                case ($entity instanceof DbConnectedRestServiceEntity):
                    $model   = $this->restFactory->factory($this->getModuleName(), RestServiceModelFactory::TYPE_DB_CONNECTED);
                    $updated = $model->updateService($entity);
                    break;
                case ($entity instanceof RestServiceEntity):
                default:
                    $updated = $model->updateService($entity);
            }
        } catch (\Exception $e) {
            throw new PatchException('Error updating REST service', 500, $e);
        }

        return $updated;
    }

    /**
     * Delete a service
     *
     * @param  string $id
     * @return true
     */
    public function delete($id)
    {
        // Make sure we have an entity first
        $model  = $this->getModel();
        $entity = $model->fetch($id);

        try {
            switch (true) {
                case ($entity instanceof DbConnectedRestServiceEntity):
                    $model   = $this->restFactory->factory($this->getModuleName(), RestServiceModelFactory::TYPE_DB_CONNECTED);
                    $model->deleteService($entity);
                    break;
                case ($entity instanceof RestServiceEntity):
                default:
                    $model->deleteService($entity->controllerServiceName);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error deleting REST service', 500, $e);
        }

        return true;
    }
}
