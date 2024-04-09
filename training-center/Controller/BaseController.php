<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Model\GeneralLog;

class BaseController extends Controller
{
    public function ajaxFriendlyRedirect(Request $request, $url)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response(json_encode('200 OK'));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $this->redirect($url);
        }
    }

    protected function logUserAction($User, $event_type, $request, $args = [])
    {
        $GeneralLog = new GeneralLog();
        $GeneralLog->User()->associate($User);
        $GeneralLog->ip_address = $request->getClientIp();
        $GeneralLog->user_agent = $request->headers->get('User-Agent');
        $GeneralLog->recordEvent($event_type, $args);
        $GeneralLog->save();
    }
}
