<?php
namespace Fphone\Provider;
use TelApi\TelApi;
use TelApi\TelApiInbound;
use Silex\Application;
use Silex\ServiceProviderInterface;
class TelApiServiceProvider implements ServiceProviderInterface{
    public function register(Application $app){
        $app['telapi'] = $app->share(function() use ($app) {
            return new TelApi($app['telapi.config']);
        });
        $app['telapi.inbound'] = $app->share(function() use ($app){
            return new TelApiInbound();
        });
    }
    public function boot(Application $app){
    }
}
