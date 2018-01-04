<?php
/**
 * Created by PhpStorm.
 * User: rolandoyjustyna
 * Date: 30/12/17
 * Time: 1:13
 */

namespace App\Controller\Resume;

use App\Form\UserType;
use GuzzleHttp\Psr7\Request as GRequest;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller {
	/**
	 * @Route("/", name="home_page")
	 * @param Request $r
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $r) {
		$user = null;
		$frm  = $this->createForm(UserType::class);
		$frm->handleRequest($r);
		if ($frm->isSubmitted() && $frm->isValid()) {
			try {
				$user   = $frm->get('user')->getData();
				$cli    = $this->get('guzzle_client');
				$params = [];
				$cliID  = $this->getParameter('client_id');
				if (null != $cliID) {
					$params['client_id']     = $cliID;
					$params['client_secret'] = $this->getParameter('secret_key');
				}
				$req = new GRequest('GET', sprintf('%susers/%s', $this->getParameter('api'), $user));
				$rsp = $cli->send($req, [
					'query' => $params,
				]);
				if ($rsp->getStatusCode() !== 200) {
					goto noUser;
				}
				$info = json_decode($rsp->getBody()->getContents());
				return $this->render('resume/index.html.twig', [
					'info' => $info,
					'user' => $user,
					'frm'  => $frm->createView(),
				]);
			} catch (RequestException $exc) {
				return $this->render('resume/index.html.twig', [
					'user'  => $user,
					'frm'   => $frm->createView(),
					'limit' => true,
					'msg'   => $exc->getMessage(),
				]);
			}
		}
		noUser:
		return $this->render('resume/index.html.twig', [
			'user' => $user,
			'frm'  => $frm->createView(),
		]);
	}
}