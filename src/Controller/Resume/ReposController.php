<?php
/**
 * Created by PhpStorm.
 * User: rolandoyjustyna
 * Date: 31/12/17
 * Time: 1:03
 */

namespace App\Controller\Resume;

use GuzzleHttp\Psr7\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ReposController extends Controller {
	/**
	 * @Route("/repos/{user}", name="user_repos")
	 * @param $user
	 * @return mixed
	 */
	public function showAction($user) {
		$req    = new Request('GET', sprintf('%susers/%s/repos', $this->getParameter('api'), $user));
		$params = [
			'per_page' => 100,
			'page'     => 1,
		];
        $cliID = $this->getParameter('client_id');
		if ($cliID !== null) {
			$params['client_id']  = $cliID;
			$params['client_secret'] = $this->getParameter('secret_key');
		}
		$info = [];
		$l    = [];
		$rsp  = $this->getData($req, $params);
		check:
		if ($rsp->getStatusCode() === 200) {
			$data = json_decode($rsp->getBody()->getContents());
			$info += $data;
			if (count($data) == 100) {
				$params['page'] = $params['page'] + 1;
				$rsp            = $this->getData($req, $params);
				goto check;
			}
			foreach ($info as &$item) {
				if (is_string($item->language)) {
					if (!array_key_exists($item->language, $l)) {
						$l[$item->language] = 1;
					} else {
						$l[$item->language]++;
					}
				}
				$item->created_at = new \DateTime($item->created_at);
				$item->pushed_at  = new \DateTime($item->pushed_at);
				$item->homepage = htmlspecialchars_decode($item->homepage);
			}
			asort($l);
			$l = array_reverse($l);
			usort($info, function ($a, $b) {
				$ap = $a->watchers + $a->forks;
				$bp = $b->watchers + $b->forks;
				if ($ap > $bp) {
					return -1;
				} else if ($ap == $bp) {
					return 0;
				}
				return 1;
			});
		}
		return $this->render('resume/repos.html.twig', [
			'repos' => $info,
			'langs' => $l,
			'total' => array_sum(array_values($l)),
		]);
	}

	/**
	 * @param Request $req
	 * @param $params
	 * @return mixed
	 */
	private function getData(Request $req, $params) {
		$cli = $this->get('guzzle_client');
		return $cli->send($req, [
			'query' => $params,
		]);
	}
}