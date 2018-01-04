<?php
/**
 * Created by PhpStorm.
 * User: rolandoyjustyna
 * Date: 31/12/17
 * Time: 2:50
 */

namespace App\Controller\Resume;


use GuzzleHttp\Psr7\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OrgsController extends Controller
{
    public function showAction($user)
    {
        $req = new Request('GET', sprintf("%susers/%s/orgs", $this->getParameter('api'), $user));
        $cli = $this->get('guzzle_client');
        $params = [
            'per_page' => 100,
            'page'     => 1,
        ];
        $cliID = $this->getParameter('client_id');
        if ($cliID !== null) {
            $params['client_id']  = $cliID;
            $params['client_secret'] = $this->getParameter('secret_key');
        }
        $rsp = $cli->send($req,[
            'query' => $params
        ]);
        $orgs = [];
        if ($rsp->getStatusCode() === 200) {
            $orgs += json_decode($rsp->getBody()->getContents());
        }
        return $this->render('resume/orgs.html.twig', [
            'orgs' => $orgs
        ]);
}
}