<?php
namespace Fphone\Controller;

use Silex\Application;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CallController
{
   private function __validPhone ($phone) {
        $phone = trim($phone);
        $reg = "/((\(\+4\)|\+4|4))?[-\s]?\(?((07)\d{2})?\)?[-\s]?\d{3}[-\s]?\d{3}$/";
        $matches = array();
        $no_of_matches = preg_match_all($reg, $phone, $matches);
        if(!$no_of_matches) return FALSE;
        if($matches[0][0] != $phone) return FALSE;
        return TRUE;
    }
    public function checkCall(Application $app,Request $request){
        $fbid = $app['security']->getToken()->getUser()->getUsername();


        if(!$fbid){
            return JsonResponse::create(array("error"=>true,"message"=>'Nu esti autentificat'),400);
        }
        $id = $request->get("callId");

        $call = $app['db']->findLast("call","fbid=?",array($fbid));
        if(!$call){
            return JsonResponse::create(array("error"=>true,"message"=>"Nu exista apelul",400));
        }
        $ecall = $app['telapi']->get(array("calls",$call->sid));
        $end = $ecall->end_time;

        return JsonResponse::create(array("error"=>false,"status"=>$ecall->status,"ends"=>$end));
    }
    public function addCall(Application $app, Request $request){
        $payload = json_decode($request->getContent());
        $fbid = $app['security']->getToken()->getUser()->getUsername();


        if(!$fbid){
            return JsonResponse::create(array("error"=>true,"message"=>'Nu esti autentificat'),400);

        }
        if($this->__validPhone($payload->to) === false){
            return JsonResponse::create(array("error"=>true,"message"=>"Numarul pe care vrei sa il suni nu este corect"),400);
        }
        if($this->__validPhone($payload->from) === false){
            return JsonResponse::create(array("error"=>true,"message"=>'Numarul fals nu este corect'),400);
        }
       if(!in_array($payload->tipul,array(1,2))){
            return JsonResponse::create(array("error"=>true,"message"=>"Dc esti rau?"),400);
        }
        if(strlen($payload->to) == 10){
            $payload->to = "4".$payload->to;
        }
        if(strlen($payload->from) == 10){
            $payload->from = "4".$payload->from;
        }
        $user = $app['db']->findOne("user","fbid=?",array($fbid));
        if($user->withCondition("statusText = ?",array("in_progress"))->ownCall){
            return JsonResponse::create(array("error"=>true,"message"=>"Exista un apel in desfasurare !"));
        }

        $calls = $user->withCondition("date = ?",array(date("Y-m-d", time())))->ownCall;
        if(count($calls)>10000){
            return JsonResponse::create(array("error"=>true,"message"=>"Ai atins limita de apeluri pe ziua de azi (4)"),402);
        } else {
            foreach($calls as $call){
                if(!$call->timestamp) continue;
                $var = (time()-$call->timestamp);
              
                if($var < 600){
                    //return JsonResponse::create(array("error"=>true,"message"=>"Te mai joci in exact ".round(10-$var/60)." minute de acum"),402);
                }
            }
        }
        try{
            $tcall = $app['telapi']->create('calls',array('From'=>$payload->from,'To'=>$payload->to,'Url'=>'http://cumload.endemic.ro/inbound'));
        } catch(Exception $e){
            return JsonResponse::create(array("error"=>true,"message"=>"Eroare de provider"),400);
        }

        $call = $app['db']->dispense("call");
        $call->from = $tcall->from;
        $call->to = $tcall->to;
        $call->sid = $tcall->sid;
        $call->timestamp = time();
        $call->uri = $tcall->uri;
        $call->date = date("Y-m-d");
        $call->status = "";
        $call->statusUpdate(false);
        $call->fbid = $fbid;
        $call->tipul = $payload->tipul;
        $user->ownCall[] = $call;
        $app['db']->store($user);
        return JsonResponse::create(array("error"=>false,"call"=>$call->id));
    }
}
