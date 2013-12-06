<?php
namespace Fphone\Provider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use RedBean_Facade;
class RedBeanServiceProvider extends RedBean_Facade implements ServiceProviderInterface
{
    public function boot(Application $app){
    }
    public function register(Application $app)
    {
        $this->setup();
        $app['db'] = $this;
        return $this;
    }
}