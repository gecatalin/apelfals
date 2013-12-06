<?php
namespace Silex;
require_once(__DIR__."/vendor/autoload.php");
use Fphone\Provider\RedBeanServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Silex\Provider\FacebookServiceProvider;
use Silex\Application;
use RedBean_Facade as R;
$app = new Application();
$app->register(new RedBeanServiceProvider());
$app['debug'] = true;
$app->register(new \Silex\Provider\SessionServiceProvider());
$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));
$app->register(new FacebookServiceProvider(), array(
    'facebook.config' => array(
        'appId'      => '',
        'secret'     => '',
        'fileUpload' => false, // optional
    ),
    'facebook.permissions' => array('email'),
));

$app['routes'] = $app->extend('routes', function (RouteCollection $routes, Application $app) {
        $loader     = new YamlFileLoader(new FileLocator(__DIR__ . '/config'));
        $collection = $loader->load('routes.yml');
        $routes->addCollection($collection);
        return $routes;
    }
);


$app['security.firewalls'] = array(
    'inbound' => array(
        'pattern' => '^/inbound',
        'anonymous' => true,
    ),
    'private' => array(
        'pattern' => '^/',
        'anonymous' => false,
        'facebook' => array(
            'check_path' => '/login_check'
        ),
        'users'=> $app->share(function () use ($app) {
            return new \Fphone\Provider\FacebookUserProvider($app);
        }),
    )

);


$app->register(new \Silex\Provider\SecurityServiceProvider());
$app->register(new \Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app->boot();
$app->register(new \Fphone\Provider\TelApiServiceProvider(), array('telapi.config'=>array('account_sid' => '',
    'auth_token'  => '')));

return $app;
