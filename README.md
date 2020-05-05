# Slim restful

[![License](https://poser.pugx.org/adasilva/slim-restful/license)](https://packagist.org/packages/adasilva/slim-restful)
[![Latest Stable Version](https://poser.pugx.org/adasilva/slim-restful/version)](https://packagist.org/packages/adasilva/slim-restful)
[![Latest Unstable Version](https://poser.pugx.org/adasilva/slim-restful/v/unstable)](//packagist.org/packages/adasilva/slim-restful)

This library provides useful methods to simplify the use of Slim framework to create REST APIs.

## Getting Started

Install via [Composer](http://getcomposer.org)
```bash
$ composer require adasilva/slim-restful:^v1.0-beta
```

This will install Slim restful and all required dependencies (Including slim framework). Slim restful requires PHP 7.4 or newer.

## Hello world

### Slim restful skeleton(WIP)

To start a new project, you can use the skeleton(WIP).

### On an existing project

Use the SlimLoader object to load your controllers/Middlewares and settings.
- SlimLoader::loadSettings() initialize the SettingsManager from an ini or json file. It also set container settings :
    determineRouteBeforeAppMiddleware to true
    displayErrorDetails to true if setting "environment" = "development"

- SlimLoader::loadRoutes() initialize routes from an xml or json file.
Use SlimLoader::loadMiddlewares() to add slim RoutingMiddleware and ErrorMiddleware (Necessary to use your own middlewares).

A controller should extend BaseController and contains get,post,put,patch or delete methods with traditional slim parameters (request, response and arguments).
It should return the response.
To use the controller, you just have to add it on your routes file.

## Routes file

The routes file is used to declare routes but also middlewares 

### JSON

```json
{
    "middlewares": {
        "namespace": "Routes\\",
        "list": [
            { "middleware": "TestMiddleware", "annotation": "test" }
        ]
    },
    "routes" : {
        "namespace": "Controllers\\",
        "list": [
            { "name": "hello",   "pattern": "/hello",   "controller": "HelloController" }
        ]
    }
}
```

### XML

```xml
<?xml version="1.0" encoding="utf-8"?>
<!-- Routes file example with xml -->

<routing>
    <middlewares namespace="Routes\">
        <add middleware="TestMiddleware" annotation="test" />
    </middlewares>

    <routes namespace="Controllers\">
        <add name="hello" pattern="/hello" controller="HelloController"/>
    </routes>
</routing>
```

## Settings file

These files are juste for syntax example. Only "environment" setting is necessary.

### JSON

```json
{
    "environment": "development",
    "application": "Application name",

    "driver": "pdo_pgsql",
    "host": "127.0.0.1",
    "port": "5432",
    "database": "MyDatabase",
    "username": "postgres",
    "password": "password1234",
    "charset" : "utf-8"
}
```

### INI

```ini
environment = "development" ;NECESSARY development|production
application = "Application name"

secretKey = "randomly generated string"

;Database infos example
driver   = "pdo_pgsql" ;idm_db2|pdo_sqlsrv|pdo_mysql|pdo_pgsql|pdo_sqlite
host     = "127.0.0.1"
port     = "5432"
database = "MyDatabase"
username = "postgres"
password = "password1234"
charset  = "utf-8"
```

## Authors

* **Antoine Da Silva**

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
