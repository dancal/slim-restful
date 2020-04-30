<?php

namespace SlimRestful;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use SimpleXMLElement;
use Slim\App;
use Slim\Routing\Route;
use Slim\Routing\RouteCollectorProxy;
use zpt\anno\AnnotationFactory;
use zpt\anno\Annotations;

class SlimLoader {

    protected static ?SlimLoader $instance = null;

    private function __construct(){}

    /**
     * SINGLETON
     * 
     * @return SlimLoader
     */
    public static function getInstance(): SlimLoader {
 
        if(is_null(self::$instance)) {
          self::$instance = new SlimLoader();
        }
    
        return self::$instance;
    }



    /**
     * Add middlewares to the $route
     * 
     * @param Route $route                  The route
     * @param array|SimpleXMLElement $middlewares List of middlewares
     * @param string $middlewaresNamespace  Namespace of middlewares
     * @param Annotations $annos            List of method annotations
     * 
     * @return void
     */
    protected function addMiddlewares(Route $route, $middlewares, string $middlewaresNamespace, Annotations $annos): void {

        foreach($middlewares as $middleware) {

            if(is_null($middleware['middleware'])) {
                throw new LoaderException('Middlewares loading: middleware attribute is missing');
            }

            if($middleware instanceof SimpleXMLElement) {
                $middlewareAttributes = $middleware->attributes();
                $middleware = array();
                foreach($middlewareAttributes as $key => $value) {
                    $middleware[$key] = (string) $value;
                }
            }
            
            $isReversed = array_key_exists('reversed', $middleware) ? $middleware['reversed'] : null;
            $annotation = array_key_exists('annotation', $middleware)
                ? array_key_exists($middleware['annotation'], $annos->asArray())
                    ? $annos[$middleware['annotation']]
                    : false
                : null
            ;

            if(
                is_null($annotation)
                || !is_null($annotation) && $annotation
                || !is_null($annotation) && $isReversed && !$annotation
            ) {
                $route->add($middlewaresNamespace . $middleware['middleware']);
            }
        }
    }



    /**
     * Load routes from xml or json file
     * 
     * @param App $app Slim app instance
     * @param string $filename JSON or XML file name (Example: 'routes.json')
     * 
     * @return SlimLoader
     * 
     * @throws LoaderException
     */
    public function loadRoutes(App $app, string $filename): SlimLoader {
        
        if (\file_exists($filename)) {
            $extension = pathinfo($filename)['extension'];
            $routes = null;
            $middlewares = null;
            
            switch ($extension) {
                case 'xml':
                    $file                  = simplexml_load_file($filename);
                    $routesXmlElement      = $file->routes;
                    $middlewaresXmlElement = $file->middlewares;

                    if(!is_null($routesXmlElement)) {
                        $routes               = $routesXmlElement->children();
                        $controllersNamespace = $routesXmlElement->attributes()['namespace'];
                    }
                    if(!is_null($middlewaresXmlElement)) {
                        $middlewares          = $middlewaresXmlElement->children();
                        $middlewaresNamespace = $middlewaresXmlElement->attributes()['namespace'];
                    }
                    break;
                case 'json':
                    $file                  = json_decode(file_get_contents($filename), true);
                    $routesXmlElement      = $file['routes'];
                    $middlewaresXmlElement = $file['middlewares'];

                    if(!is_null($routesXmlElement)) {
                        $routes               = $routesXmlElement['list'];
                        $controllersNamespace = $routesXmlElement['namespace'];
                    }
                    if(!is_null($middlewaresXmlElement)) {
                        $middlewares          = $middlewaresXmlElement['list'];
                        $middlewaresNamespace = $middlewaresXmlElement['namespace'];
                    }
                    break;
                default:
                    throw new LoaderException('Routes file must be of xml or json type');
                    break;
                }

            $container = $app->getContainer();
            $annoFactory = new AnnotationFactory();

            if(is_null($routes)) {
                throw new LoaderException('No routes in file');
            }

            foreach($routes as $route) {

                $routesAttributes = $extension === 'xml' ? $route->attributes() : $route;
                $controller = $controllersNamespace . $routesAttributes['controller'];
                $methodsAllowed = array('get', 'post', 'put', 'patch', 'delete');
                $self = $this;

                $app->group(
                    $routesAttributes['pattern'],
                    function(RouteCollectorProxy $group) use (
                        $self,
                        $methodsAllowed,
                        $controllersNamespace,
                        $routesAttributes,
                        $middlewares,
                        $middlewaresNamespace,
                        $annoFactory
                    ) {
                        $controller = $controllersNamespace . $routesAttributes['controller'];
                        $name = $routesAttributes['name'];
                        $controllerMethods = get_class_methods($controller);

                        foreach($methodsAllowed as $method) {
                            if(in_array($method, $controllerMethods)) {
                                $methodRoute = $group->$method(
                                    $method === 'get' ? '[/{id}]' : '',
                                    $controller . ':' . $method
                                )->setName($name . strtoupper($method));
                                
                                if(!is_null($middlewares)) {
                                    $annos = $annoFactory->get(new \ReflectionMethod($controller, $method));
                                    $self->addMiddlewares($methodRoute, $middlewares, $middlewaresNamespace, $annos);
                                }
                            }

                        }

                    }
                );

                $container->set($controller, function (ContainerInterface $c) use ($controller) {
                    return new $controller();
                });
            }
        } else {
            throw new LoaderException('Routes file loading failed.');
        }

        return $this;
    }
    
    /**
     * Add slim middlewares
     * 
     * @param App $app Slim app instance
     * 
     * @return SlimLoader
     */
    public function loadMiddlewares(App $app): SlimLoader {
        $app->addRoutingMiddleware(); //To use middlewares
        $app->addErrorMiddleware(true, true, true); //To return exact error code when throwing slim exceptions
        return $this;
    }

    /**
     * Load settings from .ini file
     * todo: Add passphrase support for rsa keys
     * 
     * @param ContainerBuilder Slim app container instance
     * @param string $filename
     * 
     * @return SlimLoader
     * 
     * @throws LoaderException
     */
    public function loadSettings(ContainerBuilder $containerBuilder, string $filename): SlimLoader {

        $settingsManager = SettingsManager::getInstance();

        if (file_exists($filename)) {
            $extension = pathinfo($filename)['extension'];
            
            switch ($extension) {
                case 'ini': 
                    $params = parse_ini_file($filename);
                    break;
                case 'json': 
                    $params = json_decode(file_get_contents($filename), true);
                    break;
                default: 
                    throw new LoaderException('Config file must be of ini or json type');
                    break;
                }

            $settingsManager->addSettings($params);

            $isDev = array_key_exists('environment', $params)
                ? $params['environment'] === 'development'
                : false;

            $containerBuilder->addDefinitions(array(
                'settings' => array(
                    'displayErrorDetails' => $isDev,
                    'determineRouteBeforeAppMiddleware' => true,
                )
            ));

        } else {
            throw new LoaderException("Failed to load config file: $filename.");
        }

        return $this;
    }
}