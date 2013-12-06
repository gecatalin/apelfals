<?php
namespace Fphone\Controller;

use Silex\Application;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MainController
{
    private function _redirect(){

        return new RedirectResponse("/redirectLogin");

    }

    public function bootstrap(Application $app){
        if(!$app['security']->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->_redirect();
        }

        return $app['twig']->render('bootstrap.twig',array(
            'firstname' => $app['facebook']->api('/me')['first_name'],
        ));
    }
    public function inbound(Application $app,Request $request){
        $db = $app['db'];
        $callSid = $request->get("CallSid");
        $callStatus = $request->get("CallStatus");
        $call = $db->findOne("call","sid = ?",array($callSid));
        if($call){
            $call->status = $callStatus;
            $call->statusUpdate(true);
            $db->store($call);
        }
        $c = $app['telapi.inbound'];
        if($call->tipul == 1){
            $c->play("http://cumload.endemic.ro/farse/gaze.mp3");
        } else {
            $c->play("http://cumload.endemic.ro/farse/orgasm.mp3");
        }
       return new Response($c);

    }

}
