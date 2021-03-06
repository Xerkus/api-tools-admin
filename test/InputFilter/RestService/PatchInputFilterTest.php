<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-admin for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-admin/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-admin/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Admin\InputFilter\RestService;

use Laminas\InputFilter\Factory;
use PHPUnit\Framework\TestCase;

class PatchInputFilterTest extends TestCase
{
    public function getInputFilter()
    {
        $factory = new Factory();
        return $factory->createInputFilter([
            'type' => 'Laminas\ApiTools\Admin\InputFilter\RestService\PatchInputFilter',
        ]);
    }

    public function dataProviderIsValidTrue()
    {
        return [
            'all-inputs-present' => [[
                'accept_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/hal+json',
                    2 => 'application/json',
                ],
                'collection_class' => 'Laminas\Paginator\Paginator',
                'collection_http_methods' => [
                    0 => 'GET',
                    1 => 'POST',
                ],
                'collection_name' => 'foo_bar',
                'collection_query_whitelist' => [
                ],
                'content_type_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/json',
                ],
                'entity_class' => 'StdClass',
                'entity_http_methods' => [
                    0 => 'GET',
                    1 => 'PATCH',
                    2 => 'PUT',
                    3 => 'DELETE',
                ],
                'entity_identifier_name' => 'id',
                'hydrator_name' => 'Laminas\\Hydrator\\ArraySerializable',
                'page_size' => 25,
                'page_size_param' => null,
                'resource_class' => 'Foo_Bar\\V1\\Rest\\Baz_Bat\\Baz_BatResource',
                'route_identifier_name' => 'foo_bar_id',
                'route_match' => '/foo_bar[/:foo_bar_id]',
                'selector' => 'HalJson',
                'service_name' => 'Baz_Bat',
            ]],
            'page_size-negative' => [[
                'accept_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/hal+json',
                    2 => 'application/json',
                ],
                'collection_class' => 'Laminas\Paginator\Paginator',
                'collection_http_methods' => [
                    0 => 'GET',
                    1 => 'POST',
                ],
                'collection_name' => 'foo_bar',
                'collection_query_whitelist' => [
                ],
                'content_type_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/json',
                ],
                'entity_class' => 'StdClass',
                'entity_http_methods' => [
                    0 => 'GET',
                    1 => 'PATCH',
                    2 => 'PUT',
                    3 => 'DELETE',
                ],
                'entity_identifier_name' => 'id',
                'hydrator_name' => 'Laminas\\Hydrator\\ArraySerializable',
                'page_size' => -1,
                'page_size_param' => null,
                'resource_class' => 'Foo_Bar\\V1\\Rest\\Baz_Bat\\Baz_BatResource',
                'route_identifier_name' => 'foo_bar_id',
                'route_match' => '/foo_bar[/:foo_bar_id]',
                'selector' => 'HalJson',
                'service_name' => 'Baz_Bat',
            ]],
        ];
    }

    public function dataProviderIsValidFalse()
    {
        return [
            'missing-service-name' => [[
                'accept_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/hal+json',
                    2 => 'application/json',
                ],
                'collection_class' => null,
                'collection_http_methods' => [
                    0 => 'GET',
                    1 => 'POST',
                ],
                'collection_query_whitelist' => [
                ],
                'content_type_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/json',
                ],
                'entity_class' => null,
                'entity_http_methods' => [
                    0 => 'GET',
                    1 => 'PATCH',
                    2 => 'PUT',
                    3 => 'DELETE',
                ],
                'hydrator_name' => null,
                'page_size' => null,
                'page_size_param' => null,
                'resource_class' => null,
                'route_match' => null,
                'selector' => null,
            ], [
                'service_name',
            ]],
            'empty-inputs' => [[
                'accept_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/hal+json',
                    2 => 'application/json',
                ],
                'collection_class' => null,
                'collection_http_methods' => [
                    0 => 'GET',
                    1 => 'POST',
                ],
                'collection_name' => null,
                'collection_query_whitelist' => [
                ],
                'content_type_whitelist' => [
                    0 => 'application/vnd.foo_bar.v1+json',
                    1 => 'application/json',
                ],
                'entity_class' => null,
                'entity_http_methods' => [
                    0 => 'GET',
                    1 => 'PATCH',
                    2 => 'PUT',
                    3 => 'DELETE',
                ],
                'entity_identifier_name' => null,
                'hydrator_name' => null,
                'page_size' => null,
                'page_size_param' => null,
                'resource_class' => null,
                'route_identifier_name' => null,
                'route_match' => null,
                'selector' => null,
                'service_name' => 'Foo_Bar',
            ], [
                'collection_class',
                'collection_name',
                'entity_class',
                'entity_identifier_name',
                'page_size',
                // 'resource_class', // Resource class is allowed to be empty
                'route_identifier_name',
                'route_match',
            ]],
        ];
    }

    /**
     * @dataProvider dataProviderIsValidTrue
     */
    public function testIsValidTrue($data)
    {
        $filter = $this->getInputFilter();
        $filter->setData($data);
        $this->assertTrue($filter->isValid(), var_export($filter->getMessages(), 1));
    }

    /**
     * @dataProvider dataProviderIsValidFalse
     */
    public function testIsValidFalse($data, $expectedInvalidKeys)
    {
        $filter = $this->getInputFilter();
        $filter->setData($data);
        $this->assertFalse($filter->isValid());

        $messages = $filter->getMessages();
        $testKeys = array_keys($messages);
        sort($expectedInvalidKeys);
        sort($testKeys);
        $this->assertEquals($expectedInvalidKeys, $testKeys);
    }
}
