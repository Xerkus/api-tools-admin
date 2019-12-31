Laminas API Tools Admin
===============

[![Build Status](https://travis-ci.org/laminas-api-tools/api-tools-admin.png)](https://travis-ci.org/laminas-api-tools/api-tools-admin)
[![Coverage Status](https://coveralls.io/repos/laminas-api-tools/api-tools-admin/badge.png?branch=master)](https://coveralls.io/r/laminas-api-tools/api-tools-admin)

This module provides the admin interface and API service for the
[Laminas API Tools](https://api-tools.getlaminas.org) project.

API Resources
-------------

### `authentication`

- HTTP basic authentication:
  ```javascript
  {
      "accept_schemes": [ "basic" ],
      "realm": "The HTTP authentication realm to use",
      "htpasswd": "path on filesystem to htpasswd file"
  }
  ```

- HTTP digest authentication:
  ```javascript
  {
      "accept_schemes": [ "digest" ],
      "realm": "The HTTP authentication realm to use",
      "htdigest": "path on filesystem to htdigest file",
      "nonce_timeout": "integer; seconds",
      "digest_domains": "Space-separated list of URIs under authentication"
  }
  ```

- OAuth2 authentication:
  ```javascript
  {
      "dsn": "PDO DSN of database containing OAuth2 schema",
      "username": "Username associated with DSN",
      "password": "Password associated with DSN",
      "route_match": "Literal route to match indicating where OAuth2 login/authorization exists"
  }
  ```

### `authorization`

```javascript
{
    "Rest\Controller\Service\Name::__resource__": {
        "GET": bool,
        "POST": bool,
        "PUT": bool,
        "PATCH": bool,
        "DELETE": bool
    },
    "Rest\Controller\Service\Name::__collection__": {
        "GET": bool,
        "POST": bool,
        "PUT": bool,
        "PATCH": bool,
        "DELETE": bool
    },
    "Rpc\Controller\Service\Name::actionName": {
        "GET": bool,
        "POST": bool,
        "PUT": bool,
        "PATCH": bool,
        "DELETE": bool
    }
}
```

REST services have an entry for each of their resource and collection instances.
RPC services have an entry per action name that is exposed (this will typically
only be one). Each service has a list of HTTP methods, with a flag. A `false`
value indicates that no authorization is required; a `true` value indicates that
authorization is required.

**Note**: If the `deny_by_default` flag is set in the application, then the
meaning of the flags is reversed; `true` then means the method is public,
`false` means it requires authentication.

### `db-adapter`

```javascript
{
    "adapter_name": "Service name for the DB adapter",
    "database": "Name of the database",
    "driver": "Driver used to make the connection"
}
```

Additionally, any other properties used to create the `Laminas\Db\Adapter\Adapter`
instance may be composed: e.g., "username", "password", etc.

### `inputfilter`

```javascript
{
    "input_name": {
        "name": "name of the input; should match key of object",
        "validators": [
            {
                "name": "Name of validator service",
                "options": {
                    "key": "value pairs to specify behavior of validator"
                }
            }
        ]
    }
}
```

An input filter may contain any number of inputs, and the format follows that
used by `Laminas\InputFilter\Factory` as described here:
http://laminas.readthedocs.org/en/latest/modules/laminas.input-filter.intro.html

Currently, we do not expose the `required` flag for inputs, utilize filters, nor
allow nesting input filters.

### `module`

```javascript
{
    "name": "normalized module name",
    "namespace": "PHP namespace of the module",
    "is_vendor": "boolean value indicating whether or not this is a vendor (3rd party) module",
    "versions": [
        "Array",
        "of",
        "available versions"
    ]
}
```

Additionally, the `module` resource composes relational links for [RPC](#rpc)
and [REST](#rest) resources; these use the relations "rpc" and "rest",
respectively.

### `rpc`

```javascript
{
    "controller_service_name": "name of the controller service; this is the identifier, and required",
    "accept_whitelist": [
        "(Optional)",
        "List",
        "of",
        "whitelisted",
        "Accept",
        "mediatypes"
    ],
    "content_type_whitelist": [
        "(Optional)",
        "List",
        "of",
        "whitelisted",
        "Content-Type",
        "mediatypes"
    ],
    "http_options": [
        "(Required)",
        "List",
        "of",
        "allowed",
        "Request methods"
    ],
    "input_filter": "(Optional) Present in returned RPC services, when one or more input filters are present; see the inputfilter resource for details",
    "route_match": "(Required) String indicating Segment route to match",
    "route_name": "(Only in representation) Name of route associated with endpoint",
    "selector": "(Optional) Content-Negotiation selector to use; Json by default"
}
```

### `rest`

```javascript
{
    "controller_service_name": "name of the controller service; this is the identifier, and required",
    "accept_whitelist": [
        "(Optional)",
        "List",
        "of",
        "whitelisted",
        "Accept",
        "mediatypes"
    ],
    "adapter_name": "(Only in DB-Connected resources) Name of Laminas\\DB adapter service used for this resource",
    "collection_class": "(Only in representation) Name of class representing collection",
    "collection_http_options": [
        "(Required)",
        "List",
        "of",
        "allowed",
        "Request methods",
        "on collections"
    ],
    "collection_query_whitelist": [
        "(Optional)",
        "List",
        "of",
        "whitelisted",
        "query string parameters",
        "to pass to resource for collections"
    ],
    "content_type_whitelist": [
        "(Optional)",
        "List",
        "of",
        "whitelisted",
        "Content-Type",
        "mediatypes"
    ],
    "entity_class": "(Only in representation) Name of class representing resource entity",
    "entity_identifier_name": "(Optional) Name of entity field representing the identifier; defaults to 'id'",
    "hydrator_name": "(Only in DB-Connected resources) Name of Laminas\\Stdlib\\Hydrator service used for this resource",
    "route_identifier_name": "(Optional) Name of route parameter representing the resource identifier; defaults to resource_name + _id",
    "input_filter": "(Optional) Present in returned REST services, when one or more input filters are present; see the inputfilter resource for details",
    "module": "(Only in representation) Name of module in which resource resides",
    "page_size": "(Optional) Integer representing number of entities to return in a given page in a collection; defaults to 25",
    "page_size_param": "(Optional) Name of query string parameter used for pagination; defaults to 'page'",
    "resource_class": "(Only in representation) Name of class representing resource handling operations",
    "resource_http_options": [
        "(Required)",
        "List",
        "of",
        "allowed",
        "Request methods",
        "on individual resources"
    ],
    "route_match": "(Optional) String indicating Segment route to match; defaults to /resource_name[/:route_identifier_name]",
    "route_name": "(Only in representation) Name of route associated with api service",
    "selector": "(Optional) Content-Negotiation selector to use; HalJson by default",
    "table_name": "(Only in DB-Connected resources) Name of database table used for this resource",
    "table_service": "(Only in DB-Connected resources) Name of TableGateway service used for this resource"
}
```

API services
------------

### `/admin/api/config`

This endpoint is for examining the application configuration, and providing
overrides of individual values in it. All overrides are written to a single
file, `config/autoload/development.php`; you can override that location in your
configuration via the `api-tools-configuration/config-file` key.

- Accept: `application/json`, `application/vnd.laminascampus.v1.config+json`

  The first will deliver representations as a flat array of key/value pairs,
  with the keys being dot-separated values, just as you would find in INI.

  The second will deliver the configuration as a tree.

- Content-Type: `application/json`, `application/vnd.laminascampus.v1.config+json`

  The first expects key/value pairs, with keys being dot-separated values, as
  you would find in INI files.

  The second expects a nested array/object of configuration.

- Methods: `GET`, `PATCH`

- Errors: `application/problem+json`

### `/admin/api/authentication`

This REST endpoint is for creating, updating, and deleting the authentication
configuration for your application. It uses the [authentication
resource](#authentication).

- Accept `application/json`

  Returns an [authentication resource](#authentication) on success.

- Content-Type: `application/json`

  Expects an [authentication resource](#authentication) with all details
  necessary for establishing HTTP authentication.

- HTTP methods: `GET`, `POST`, `PATCH`, `DELETE`

  `GET` returns a `404` response if no authentication has previously been setup.
  `POST` will return a `201` response on success. `PATCH` will return a `200`
  response on success. `DELETE` will return a `204` response on success.

- Errors: `application/problem+json`

### `/admin/api/db-adapter[/:adapter\_name]`

This REST endpoint is for creating, updating, and deleting named `Laminas\Db`
adapters; it uses the [db-adapter resource](#db-adapter).

- Accept `application/json`

  Returns a [db-adapter resource](#db-adapter) on success.

- Content-Type: `application/json`

  Expects [db-adapter resource](#db-adapter) with all details necessary for
  creating a DB connection.

- Collection Methods: `GET`, `POST`

- Resource Methods: `GET`, `PATCH`, `DELETE`

- Errors: `application/problem+json`

### `/admin/api/module/:name/authorization?version=:version`

This REST endpoint is for fetching and updating the authorization
configuration for your application. It uses the [authorization
resource](#authorization).

- Accept `application/json`

  Returns an [authorization resource](#authorization) on success.

- Content-Type: `application/json`

  Expects an [authorization resource](#authorization) with all details
  necessary for establishing HTTP authentication.

- HTTP methods: `GET`, `PUT`

  `GET` will always return an entity; if no configuration existed previously
  for the module, or if any given service at the given version was not listed
  in the configuration, it will provide the default values.

  `PUT` will return a `200` response on success, along with the updated
  entity.

- Errors: `application/problem+json`

### `/admin/api/db-adapter[/:adapter\_name]`

This REST endpoint is for creating, updating, and deleting named `Laminas\Db`
adapters; it uses the [db-adapter resource](#db-adapter).

- Accept `application/json`

  Returns a [db-adapter resource](#db-adapter) on success.

- Content-Type: `application/json`

  Expects [db-adapter resource](#db-adapter) with all details necessary for
  creating a DB connection.

- Collection Methods: `GET`, `POST`

- Resource Methods: `GET`, `PATCH`, `DELETE`

- Errors: `application/problem+json`


### `/admin/api/config/module?module={module name}`

This operates exactly like the `/admin/api/config` endpoint, but expects a known
module name. When provided, it allows you to introspect and manipulate the
configuration file for that module.

### `/admin/api/module.enable`

This endpoint will Laminas API Tools-enable (Apigilify) an existing module.

- Accept: `application/json`

  Returns a [Module resource](#module) on success.

- Content-Type: `application/json`

  Expects an object with the property "module" describing an existing Laminas module.

  ```javascript
  {
  "module": "Status"
  }
  ```

- Methods: `PUT`

- Errors: `application/problem+json`

### `/admin/api/validators`

This endpoint provides a sorted list of all registered validator plugins; the
use case is for building a drop-down of available plugins when creating an
input filter for a service.

- Accept: `application/json`

  Returns an `application/json` response with the following format on success:

  ```javascript
  {
    "validators": [
      "list",
      "of",
      "validators"
    ]
  }
  ```

- Methods: `GET`

- Errors: `application/problem+json`

### `/admin/api/versioning`

This endpoint is for adding a new version to an existing API. If no version is
passed in the payload, the version number is simply incremented.

- Accept: `application/json`

  Returns the response `{ "success": true, "version": :version: }` on success,
  an API-Problem payload on error.

- Content-Type: `application/json`

  Expects an object with the property "module", providing the name of a Laminas,
  Laminas API Tools-enabled module; optionally, a "version" property may also be
  provided to indicate the specific version string to use.

  ```javascript
  {
    "module": "Status",
    "version": 10
  }
  ```

- Methods: `PATCH`

- Errors: `application/problem+json`

### `/admin/api/module[/:name]`

This is the canonical endpoint for [Module resources](#module).

- Accept: `application/json`

  Returns either a single [Module resource](#module) (when a `name` is provided)
  or a collection of Module resources (when no `name` is provided) on success.

- Content-Type: `application/json`

  Expects an object with the property "name" describing the module to create:

  ```javascript
  {
  "name": "Status"
  }
  ```

- Collection Methods: `GET`, `POST`

- Resource Methods: `GET`

- Errors: `application/problem+json`

### `/admin/api/module/:name/rpc[/:controller\_service\_name]`

This is the canonical endpoint for [RPC resources](#rpc).

- Accept: `application/json`

  Returns either a single [RPC resource](#rpc) (when a `controller\_service\_name`
  is provided) or a collection of RPC resources (when no
  `controller\_service\_name` is provided) on success.

- Content-Type: `application/json`

  Expects an object with the property "service_name" describing the endpoint to
  create:

  ```javascript
  {
    "service_name": "Status"
  }
  ```

  You may also provide any other options listed in the [RPC resource](#rpc).

- Collection Methods: `GET`, `POST`

- Resource Methods: `GET`, `PATCH`

- The query string variable `version` may be passed to the collection to filter
  results by version: e.g., `/admin/api/module/:name/rpc?version=2`.

- Errors: `application/problem+json`

### `/admin/api/module/:name/rpc/:controller\_service\_name/inputfilter[/:input\_filter\_name]`

This service is for creating, updating, and deleting named [input filters](#inputfilter)
associated with a given RPC service.

- Accept: `application/json`

  Returns either single [input filter](#inputfilter) (when an
  `input\_filter\_name` is provided) or a collection of input filters (when no
  `input\_filter\_name` is provided) on success. Typically, only one input
  filter will be associated with a given RPC service.

  Input filters returned will also compose a property `input\_filter\_name`,
  which is the identifier for the given input filter.

- Content-Type: `aplication/json`

  Expects an [input filter](#inputfilter).

- Collection Methods: `GET`, `POST`

- Resource Methods: `GET`, `PUT`, `DELETE`

- Errors: `application/problem+json`

### `/admin/api/module/:name/rest[/:controller\_service\_name]`

This is the canonical endpoint for [REST resources](#rest).

Can be used for any type of REST resource, including DB-Connected (and, in the
future, Mongo-Connected).

DB-Connected resources expect the following additional properties (and will
return them as well):

- `adapter\_name`: A named DB adapter service.
- `table\_name`: The DB table associated with this service.
- `hydrator\_name`: Optional; the name of a hydrator service used to hydrate rows
  returned by the database; defaults to ArraySerializable.
- `table\_service`: Optional; this is auto-generated by default, but an alternate
  TableGateway service may be provided.

- Accept: `application/json`

  Returns either a single [REST resource](#rest) (when a `controller\_service\_name`
  is provided) or a collection of REST resources (when no
  `controller\_service\_name` is provided) on success.

- Content-Type: `application/json`

  Expects an object with the property `resource\_name` describing the module to create:

  ```javascript
  {
    "resource_name": "Status"
  }
  ```

  You may also provide any other options listed in the [REST resource](#rest).

- Collection Methods: `GET`, `POST`, `DELETE`

- Resource Methods: `GET`, `PATCH`

- The query string variable `version` may be passed to the collection to filter
  results by version: e.g., `/admin/api/module/:name/rest?version=2`.

- Errors: `application/problem+json`

### `/admin/api/module/:name/rest/:controller\_service\_name/inputfilter[/:input\_filter\_name]`

This service is for creating, updating, and deleting named [input filters](#inputfilter)
associated with a given REST service.

- Accept: `application/json`

  Returns either single [input filter](#inputfilter) (when an
  `input\_filter\_name` is provided) or a collection of input filters (when no
  `input\_filter\_name` is provided) on success. Typically, only one input
  filter will be associated with a given REST service.

  Input filters returned will also compose a property `input\_filter\_name`,
  which is the identifier for the given input filter.

- Content-Type: `aplication/json`

  Expects an [input filter](#inputfilter).

- Collection Methods: `GET`, `POST`

- Resource Methods: `GET`, `PUT`, `DELETE`

- Errors: `application/problem+json`
