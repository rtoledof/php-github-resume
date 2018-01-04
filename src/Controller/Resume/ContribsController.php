<?php
/**
 * Created by PhpStorm.
 * User: rolandoyjustyna
 * Date: 31/12/17
 * Time: 3:17
 */

namespace App\Controller\Resume;

use GuzzleHttp\Psr7\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContribsController extends Controller {
	/**
	 * @param $user
	 * @return mixed
	 */
	public function showAction($user) {
		$page  = 1;
		$keys  = '';
		$cliID = $this->getParameter('client_id');
		if ($cliID) {
			$keys .= sprintf('&client_id=%s&client_secret=%s', $cliID, $this->getParameter('secret_key'));
		}
		$req      = new Request('GET', sprintf('%ssearch/issues?q=type:pr+is:merged+author:%s&per_page=100&page=%d%s', $this->getParameter('api'), $user, $page, $keys));
		$cli      = $this->get('guzzle_client');
		$contribs = [];
		send:
		$rsp = $cli->send($req);
		if ($rsp->getStatusCode() == 200) {
			$d          = json_decode($rsp->getBody()->getContents());
			$contribs   = array_merge($contribs, $d->items);
			$totalAdded = count($contribs);
			$page++;
			if ($d->total_count > 100 && $totalAdded !== $d->total_count && $page <= 10) {
				$req = new Request('GET', sprintf('%ssearch/issues?q=type:pr+is:merged+author:%s&per_page=100&page=%d%s', $this->getParameter('api'), $user, $page, $keys));
				goto send;
			}
		}
		$popularity = [];
		foreach ($contribs as $contrib) {
			if (!array_key_exists($contrib->repository_url, $popularity)) {
				$popularity[$contrib->repository_url] = 0;
			}
			$popularity[$contrib->repository_url]++;
		}
        asort($popularity);
		return $this->render('resume/contribs.html.twig', [
			'contribs'   => $contribs,
			'popularity' => array_reverse($popularity),
			'usr'        => $user,
		]);
	}
}