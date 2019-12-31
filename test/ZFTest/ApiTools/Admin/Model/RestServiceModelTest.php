<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-admin for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-admin/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-admin/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Admin\Model;

use BarConf;
use Laminas\ApiTools\Admin\Model\ModuleEntity;
use Laminas\ApiTools\Admin\Model\NewRestServiceEntity;
use Laminas\ApiTools\Admin\Model\RestServiceEntity;
use Laminas\ApiTools\Admin\Model\RestServiceModel;
use Laminas\ApiTools\Configuration\ModuleUtils;
use Laminas\ApiTools\Configuration\ResourceFactory;
use Laminas\Config\Writer\PhpArray;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

require_once __DIR__ . '/TestAsset/module/BarConf/Module.php';

class RestServiceModelTest extends TestCase
{
    /**
     * Remove a directory even if not empty (recursive delete)
     *
     * @param  string $dir
     * @return boolean
     */
    protected function removeDir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    protected function cleanUpAssets()
    {
        $basePath   = sprintf('%s/TestAsset/module/%s', __DIR__, $this->module);
        $configPath = $basePath . '/config';
        $srcPath    = $basePath . '/src';
        if (is_dir($srcPath)) {
            $this->removeDir($srcPath);
        }
        copy($configPath . '/module.config.php.dist', $configPath . '/module.config.php');
    }

    public function setUp()
    {
        $this->module = 'BarConf';
        $this->cleanUpAssets();

        $modules = array(
            'BarConf' => new BarConf\Module()
        );

        $this->moduleEntity  = new ModuleEntity($this->module, array(), array(), false);
        $this->moduleManager = $this->getMockBuilder('Laminas\ModuleManager\ModuleManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->moduleManager->expects($this->any())
                            ->method('getLoadedModules')
                            ->will($this->returnValue($modules));

        $this->writer   = new PhpArray();
        $this->modules  = new ModuleUtils($this->moduleManager);
        $this->resource = new ResourceFactory($this->modules, $this->writer);
        $this->codeRest = new RestServiceModel($this->moduleEntity, $this->modules, $this->resource->factory('BarConf'));
    }

    public function tearDown()
    {
        $this->cleanUpAssets();
    }

    public function getCreationPayload()
    {
        $payload = new NewRestServiceEntity();
        $payload->exchangeArray(array(
            'resource_name'              => 'foo',
            'route_match'                => '/api/foo',
            'identifier_name'            => 'foo_id',
            'collection_name'            => 'foo',
            'resource_http_methods'      => array('GET', 'PATCH'),
            'collection_http_methods'    => array('GET', 'POST'),
            'collection_query_whitelist' => array('sort', 'filter'),
            'page_size'                  => 10,
            'page_size_param'            => 'p',
            'selector'                   => 'HalJson',
            'accept_whitelist'           => array('application/json', 'application/*+json'),
            'content_type_whitelist'     => array('application/json'),
        ));
        return $payload;
    }

    public function testRejectInvalidRestResourceName1()
    {
        $this->setExpectedException('Laminas\ApiTools\Rest\Exception\CreationException');
        $restServiceEntity = new NewRestServiceEntity();
        $restServiceEntity->exchangeArray(array('resourcename' => 'Foo Bar'));
        $this->codeRest->createService($restServiceEntity);
    }

    public function testRejectInvalidRestResourceName2()
    {
        $this->setExpectedException('Laminas\ApiTools\Rest\Exception\CreationException');
        $restServiceEntity = new NewRestServiceEntity();
        $restServiceEntity->exchangeArray(array('resourcename' => 'Foo:Bar'));
        $this->codeRest->createService($restServiceEntity);
    }

    public function testRejectInvalidRestResourceName3()
    {
        $this->setExpectedException('Laminas\ApiTools\Rest\Exception\CreationException');
        $restServiceEntity = new NewRestServiceEntity();
        $restServiceEntity->exchangeArray(array('resourcename' => 'Foo/Bar'));
        $this->codeRest->createService($restServiceEntity);
    }

    public function testCanCreateControllerServiceNameFromResourceNameSpace()
    {
        $this->assertEquals('BarConf\V1\Rest\Foo\Bar\Baz\Controller', $this->codeRest->createControllerServiceName('Foo\Bar\Baz'));
    }

    public function testCanCreateControllerServiceNameFromResourceName()
    {
        $this->assertEquals('BarConf\V1\Rest\Foo\Controller', $this->codeRest->createControllerServiceName('Foo'));
    }

    public function testCreateResourceClassReturnsClassNameCreated()
    {
        $resourceClass = $this->codeRest->createResourceClass('Foo');
        $this->assertEquals('BarConf\V1\Rest\Foo\FooResource', $resourceClass);
    }

    public function testCreateResourceClassCreatesClassFileWithNamedResourceClass()
    {
        $resourceClass = $this->codeRest->createResourceClass('Foo');

        $className = str_replace($this->module . '\\V1\\Rest\\Foo\\', '', $resourceClass);
        $path      = realpath(__DIR__) . '/TestAsset/module/BarConf/src/BarConf/V1/Rest/Foo/' . $className . '.php';
        $this->assertTrue(file_exists($path));

        require_once $path;

        $r = new ReflectionClass($resourceClass);
        $this->assertInstanceOf('ReflectionClass', $r);
        $parent = $r->getParentClass();
        $this->assertEquals('Laminas\ApiTools\Rest\AbstractResourceListener', $parent->getName());
    }

    public function testCreateResourceClassAddsInvokableToConfiguration()
    {
        $resourceClass = $this->codeRest->createResourceClass('Foo');

        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';
        $this->assertArrayHasKey('service_manager', $config);
        $this->assertArrayHasKey('invokables', $config['service_manager']);
        $this->assertArrayHasKey($resourceClass, $config['service_manager']['invokables']);
        $this->assertEquals($resourceClass, $config['service_manager']['invokables'][$resourceClass]);
    }

    public function testCreateEntityClassReturnsClassNameCreated()
    {
        $entityClass = $this->codeRest->createEntityClass('Foo');
        $this->assertEquals('BarConf\V1\Rest\Foo\FooEntity', $entityClass);
    }

    public function testCreateEntityClassCreatesClassFileWithNamedEntityClass()
    {
        $entityClass = $this->codeRest->createEntityClass('Foo');

        $className = str_replace($this->module . '\\V1\\Rest\\Foo\\', '', $entityClass);
        $path      = realpath(__DIR__) . '/TestAsset/module/BarConf/src/BarConf/V1/Rest/Foo/' . $className . '.php';
        $this->assertTrue(file_exists($path));

        require_once $path;

        $r = new ReflectionClass($entityClass);
        $this->assertInstanceOf('ReflectionClass', $r);
        $this->assertFalse($r->getParentClass());
    }

    public function testCreateCollectionClassReturnsClassNameCreated()
    {
        $collectionClass = $this->codeRest->createCollectionClass('Foo');
        $this->assertEquals('BarConf\V1\Rest\Foo\FooCollection', $collectionClass);
    }

    public function testCreateCollectionClassCreatesClassFileWithNamedCollectionClass()
    {
        $collectionClass = $this->codeRest->createCollectionClass('Foo');

        $className = str_replace($this->module . '\\V1\\Rest\\Foo\\', '', $collectionClass);
        $path      = realpath(__DIR__) . '/TestAsset/module/BarConf/src/BarConf/V1/Rest/Foo/' . $className . '.php';
        $this->assertTrue(file_exists($path));

        require_once $path;

        $r = new ReflectionClass($collectionClass);
        $this->assertInstanceOf('ReflectionClass', $r);
        $parent = $r->getParentClass();
        $this->assertEquals('Laminas\Paginator\Paginator', $parent->getName());
    }

    public function testCreateRouteReturnsNewRouteName()
    {
        $routeName = $this->codeRest->createRoute('FooBar', '/foo-bar', 'foo_bar_id', 'BarConf\Rest\FooBar\Controller');
        $this->assertEquals('bar-conf.rest.foo-bar', $routeName);
    }

    public function testCreateRouteWritesRouteConfiguration()
    {
        $routeName = $this->codeRest->createRoute('FooBar', '/foo-bar', 'foo_bar_id', 'BarConf\Rest\FooBar\Controller');

        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';
        $this->assertArrayHasKey('router', $config);
        $this->assertArrayHasKey('routes', $config['router']);
        $routes = $config['router']['routes'];

        $this->assertArrayHasKey($routeName, $routes);
        $expected = array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/foo-bar[/:foo_bar_id]',
                'defaults' => array(
                    'controller' => 'BarConf\Rest\FooBar\Controller',
                ),
            ),
        );
        $this->assertEquals($expected, $routes[$routeName]);
    }

    public function testCreareRouteWritesVersioningConfiguration()
    {
        $routeName = $this->codeRest->createRoute('FooBar', '/foo-bar', 'foo_bar_id', 'BarConf\Rest\FooBar\Controller');

        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';
        $this->assertArrayHasKey('router', $config);
        $this->assertArrayHasKey('routes', $config['router']);
        $routes = $config['api-tools-versioning']['uri'];

        $this->assertContains($routeName, $routes);
    }

    public function testCreateRestConfigWritesRestConfiguration()
    {
        $details = $this->getCreationPayload();
        $details->exchangeArray(array(
            'entity_class'     => 'BarConf\Rest\Foo\FooEntity',
            'collection_class' => 'BarConf\Rest\Foo\FooCollection',
        ));
        $this->codeRest->createRestConfig($details, 'BarConf\Rest\Foo\Controller', 'BarConf\Rest\Foo\FooResource', 'bar-conf.rest.foo');
        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';

        $this->assertArrayHasKey('api-tools-rest', $config);
        $this->assertArrayHasKey('BarConf\Rest\Foo\Controller', $config['api-tools-rest']);
        $config = $config['api-tools-rest']['BarConf\Rest\Foo\Controller'];

        $expected = array(
            'listener'                   => 'BarConf\Rest\Foo\FooResource',
            'route_name'                 => 'bar-conf.rest.foo',
            'identifier_name'            => $details->identifierName,
            'collection_name'            => $details->collectionName,
            'resource_http_methods'      => $details->resourceHttpMethods,
            'collection_http_methods'    => $details->collectionHttpMethods,
            'collection_query_whitelist' => $details->collectionQueryWhitelist,
            'page_size'                  => $details->pageSize,
            'page_size_param'            => $details->pageSizeParam,
            'entity_class'               => $details->entityClass,
            'collection_class'           => $details->collectionClass,
        );
        $this->assertEquals($expected, $config);
    }

    public function testCreateContentNegotiationConfigWritesContentNegotiationConfiguration()
    {
        $details = $this->getCreationPayload();
        $this->codeRest->createContentNegotiationConfig($details, 'BarConf\Rest\Foo\Controller');
        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';

        $this->assertArrayHasKey('api-tools-content-negotiation', $config);
        $config = $config['api-tools-content-negotiation'];

        $this->assertArrayHasKey('controllers', $config);
        $this->assertEquals(array(
            'BarConf\Rest\Foo\Controller' => $details->selector,
        ), $config['controllers']);

        $this->assertArrayHasKey('accept_whitelist', $config);
        $this->assertEquals(array(
            'BarConf\Rest\Foo\Controller' => $details->acceptWhitelist,
        ), $config['accept_whitelist'], var_export($config, 1));

        $this->assertArrayHasKey('content_type_whitelist', $config);
        $this->assertEquals(array(
            'BarConf\Rest\Foo\Controller' => $details->contentTypeWhitelist,
        ), $config['content_type_whitelist'], var_export($config, 1));
    }

    public function testCreateHalConfigWritesHalConfiguration()
    {
        $details = $this->getCreationPayload();
        $this->codeRest->createHalConfig($details, 'BarConf\Rest\Foo\FooEntity', 'BarConf\Rest\Foo\FooCollection', 'bar-conf.rest.foo');
        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';

        $this->assertArrayHasKey('api-tools-hal', $config);
        $this->assertArrayHasKey('metadata_map', $config['api-tools-hal']);
        $config = $config['api-tools-hal']['metadata_map'];

        $this->assertArrayHasKey('BarConf\Rest\Foo\FooEntity', $config);
        $this->assertEquals(array(
            'identifier_name' => $details->identifierName,
            'route_name'      => 'bar-conf.rest.foo',
        ), $config['BarConf\Rest\Foo\FooEntity']);

        $this->assertArrayHasKey('BarConf\Rest\Foo\FooCollection', $config);
        $this->assertEquals(array(
            'identifier_name' => $details->identifierName,
            'route_name'      => 'bar-conf.rest.foo',
            'is_collection'   => true,
        ), $config['BarConf\Rest\Foo\FooCollection']);
    }

    public function testCreateServiceReturnsRestServiceEntityOnSuccess()
    {
        $details = $this->getCreationPayload();
        $result  = $this->codeRest->createService($details);
        $this->assertInstanceOf('Laminas\ApiTools\Admin\Model\RestServiceEntity', $result);

        $this->assertEquals('BarConf', $result->module);
        $this->assertEquals('BarConf\V1\Rest\Foo\Controller', $result->controllerServiceName);
        $this->assertEquals('BarConf\V1\Rest\Foo\FooResource', $result->resourceClass);
        $this->assertEquals('BarConf\V1\Rest\Foo\FooEntity', $result->entityClass);
        $this->assertEquals('BarConf\V1\Rest\Foo\FooCollection', $result->collectionClass);
        $this->assertEquals('bar-conf.rest.foo', $result->routeName);
        $this->assertEquals(array('application/vnd.bar-conf.v1+json', 'application/hal+json', 'application/json'), $result->acceptWhitelist);
        $this->assertEquals(array('application/vnd.bar-conf.v1+json', 'application/json'), $result->contentTypeWhitelist);
    }

    public function testCreateServiceUsesDefaultContentNegotiation()
    {
        $payload = new NewRestServiceEntity();
        $payload->exchangeArray(array(
            'resource_name' => 'foo',
        ));
        $result  = $this->codeRest->createService($payload);
        $this->assertInstanceOf('Laminas\ApiTools\Admin\Model\RestServiceEntity', $result);
        $this->assertEquals(array('application/vnd.bar-conf.v1+json', 'application/hal+json', 'application/json'), $result->acceptWhitelist);
        $this->assertEquals(array('application/vnd.bar-conf.v1+json', 'application/json'), $result->contentTypeWhitelist);
    }

    public function testCanFetchServiceAfterCreation()
    {
        $details = $this->getCreationPayload();
        $result  = $this->codeRest->createService($details);

        $service = $this->codeRest->fetch('BarConf\V1\Rest\Foo\Controller');
        $this->assertInstanceOf('Laminas\ApiTools\Admin\Model\RestServiceEntity', $service);

        $this->assertEquals('BarConf', $service->module);
        $this->assertEquals('BarConf\V1\Rest\Foo\Controller', $service->controllerServiceName);
        $this->assertEquals('BarConf\V1\Rest\Foo\FooResource', $service->resourceClass);
        $this->assertEquals('BarConf\V1\Rest\Foo\FooEntity', $service->entityClass);
        $this->assertEquals('BarConf\V1\Rest\Foo\FooCollection', $service->collectionClass);
        $this->assertEquals('bar-conf.rest.foo', $service->routeName);
        $this->assertEquals('/api/foo[/:foo_id]', $service->routeMatch);
    }

    public function testCanUpdateRouteForExistingService()
    {
        $details  = $this->getCreationPayload();
        $original = $this->codeRest->createService($details);

        $patch = new RestServiceEntity();
        $patch->exchangeArray(array(
            'controller_service_name' => 'BarConf\Rest\Foo\Controller',
            'route_match'             => '/api/bar/foo',
        ));

        $this->codeRest->updateRoute($original, $patch);

        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';
        $this->assertArrayHasKey('router', $config);
        $this->assertArrayHasKey('routes', $config['router']);
        $this->assertArrayHasKey($original->routeName, $config['router']['routes']);
        $routeConfig = $config['router']['routes'][$original->routeName];
        $this->assertArrayHasKey('options', $routeConfig);
        $this->assertArrayHasKey('route', $routeConfig['options']);
        $this->assertEquals('/api/bar/foo', $routeConfig['options']['route']);
    }

    public function testCanUpdateRestConfigForExistingService()
    {
        $details  = $this->getCreationPayload();
        $original = $this->codeRest->createService($details);

        $options = array(
            'page_size'                  => 30,
            'page_size_param'            => 'r',
            'collection_query_whitelist' => array('f', 's'),
            'collection_http_methods'    => array('GET'),
            'resource_http_methods'      => array('GET'),
        );
        $patch = new RestServiceEntity();
        $patch->exchangeArray($options);

        $this->codeRest->updateRestConfig($original, $patch);

        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';
        $this->assertArrayHasKey('api-tools-rest', $config);
        $this->assertArrayHasKey($original->controllerServiceName, $config['api-tools-rest']);
        $test = $config['api-tools-rest'][$original->controllerServiceName];

        foreach ($options as $key => $value) {
            $this->assertEquals($value, $test[$key]);
        }
    }

    public function testCanUpdateContentNegotiationConfigForExistingService()
    {
        $details  = $this->getCreationPayload();
        $original = $this->codeRest->createService($details);

        $options = array(
            'selector'               => 'Json',
            'accept_whitelist'       => array('application/json'),
            'content_type_whitelist' => array('application/json'),
        );
        $patch = new RestServiceEntity();
        $patch->exchangeArray($options);

        $this->codeRest->updateContentNegotiationConfig($original, $patch);

        $config = include __DIR__ . '/TestAsset/module/BarConf/config/module.config.php';
        $this->assertArrayHasKey('api-tools-content-negotiation', $config);
        $config = $config['api-tools-content-negotiation'];

        $this->assertArrayHasKey('controllers', $config);
        $this->assertArrayHasKey($original->controllerServiceName, $config['controllers']);
        $this->assertEquals($options['selector'], $config['controllers'][$original->controllerServiceName]);

        $this->assertArrayHasKey('accept_whitelist', $config);
        $this->assertArrayHasKey($original->controllerServiceName, $config['accept_whitelist']);
        $this->assertEquals($options['accept_whitelist'], $config['accept_whitelist'][$original->controllerServiceName]);

        $this->assertArrayHasKey('content_type_whitelist', $config);
        $this->assertArrayHasKey($original->controllerServiceName, $config['content_type_whitelist']);
        $this->assertEquals($options['content_type_whitelist'], $config['content_type_whitelist'][$original->controllerServiceName]);
    }

    public function testUpdateServiceReturnsUpdatedRepresentation()
    {
        $details  = $this->getCreationPayload();
        $original = $this->codeRest->createService($details);

        $updates = array(
            'route_match'                => '/api/bar/foo',
            'page_size'                  => 30,
            'page_size_param'            => 'r',
            'collection_query_whitelist' => array('f', 's'),
            'collection_http_methods'    => array('GET'),
            'resource_http_methods'      => array('GET'),
            'selector'                   => 'Json',
            'accept_whitelist'           => array('application/json'),
            'content_type_whitelist'     => array('application/json'),
        );
        $patch = new RestServiceEntity();
        $patch->exchangeArray(array_merge(array(
            'controller_service_name'    => 'BarConf\V1\Rest\Foo\Controller',
        ), $updates));

        $updated = $this->codeRest->updateService($patch);
        $this->assertInstanceOf('Laminas\ApiTools\Admin\Model\RestServiceEntity', $updated);

        $values = $updated->getArrayCopy();

        foreach ($updates as $key => $value) {
            $this->assertArrayHasKey($key, $values);
            if ($key === 'route_match') {
                $this->assertEquals(0, strpos($value, $values[$key]));
                continue;
            }
            $this->assertEquals($value, $values[$key]);
        }
    }

    public function testFetchListenersCanReturnAlternateEntities()
    {
        $details = $this->getCreationPayload();
        $this->codeRest->createService($details);

        $alternateEntity = new RestServiceEntity();
        $this->codeRest->getEventManager()->attach('fetch', function ($e) use ($alternateEntity) {
            return $alternateEntity;
        });

        $result = $this->codeRest->fetch('BarConf\V1\Rest\Foo\Controller');
        $this->assertSame($alternateEntity, $result);
    }

    public function testCanDeleteAService()
    {
        $details = $this->getCreationPayload();
        $service = $this->codeRest->createService($details);

        $this->assertTrue($this->codeRest->deleteService($service->controllerServiceName));

        $this->setExpectedException('Laminas\ApiTools\Admin\Exception\RuntimeException', 'find', 404);
        $this->codeRest->fetch($service->controllerServiceName);
    }
}
