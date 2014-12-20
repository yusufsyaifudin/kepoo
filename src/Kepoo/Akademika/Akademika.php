<?php namespace Kepoo;

class Akademika {

	public $username;
	public $password;
	private $login_url = "https://akademika.ugm.ac.id/index.php?pModule=b3JqbHE=&pSub=b3JqbHE=&pAct=c3Vydmh2";

	protected $cookies = "my_cookies.txt";

	public function __construct()
	{
		$this->curl = curl_init();
	}

	/**
	 * Login to akademika
	 *
	 * @param boolean $html
	 *
	 * @return mixed
	 */
	public function login($html = false)
	{
		curl_setopt($this->curl, CURLOPT_URL, $this->login_url);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS,'username='.urlencode($this->username).'&password='.urlencode($this->password));
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookies);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookies);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

		$output = curl_exec($this->curl);

		if ($output === false) {
			if ($html == true) {
				return "Fails to login";
			}

			return false;
		} else {
			if ($html == true) {
				return $output;
			}

			return true;
		}
		
	}

	private function dirtyDashboard()
	{
		if ($this->login()) {
			return $this->login(true);
		} else {
			return false;
		}
		
	}

	public function sidebarMenu()
	{
		// before doing anything, force to login
		$output = $this->dirtyDashboard();

		if ($output == false) {
			return false;
		}

		try {
			// then parse
			libxml_use_internal_errors(true);

			/* Createa a new DomDocument object */
			$dom = new \DomDocument;

			/* Load the HTML */
			$dom->loadHTML($output);

			/* Create a new XPath object */
			$xpath = new \DomXPath($dom);

			/* Query all <a> nodes containing specified in navigation menu */
			$nodes = $xpath->query('/html/body/div/div[2]/div[2]/div[2]/ul/li/a');

			/* Set HTTP response header to plain text for debugging output */
			/* Traverse the DOMNodeList object to output each DomNode's nodeValue */
			$i = 1;
			$response = [];
			foreach ($nodes as $node) {

				$apiEndpoint = preg_replace('/\s+/', '_', strtolower($node->nodeValue));
				$response[] = [
					'menu_id' => $i,
					'menu_name' => $node->nodeValue,
					'menu_link' => $node->getAttribute('href'),
					'menu_link_endpoint_api' => $apiEndpoint
					];
				$i++;
			}

			return $response;
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage()
			];
		}
	}

	public function getTranscript()
	{
		try {
			$menu = $this->sidebarMenu();

			$link = null;
			for ($i=0; $i<count($menu); $i++) {
				if ($menu[$i]["menu_name"] == "Transkrip Nilai") {
					$link = $menu[$i]["menu_link"];
				}
			}

		// now get the content
			curl_setopt($this->curl, CURLOPT_URL, $link);
			curl_setopt($this->curl, CURLOPT_HEADER, 0);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookies);
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookies);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

			$output = curl_exec($this->curl);

			/* Createa a new DomDocument object */
			$dom = new \DomDocument;

			/* Load the HTML */
			$dom->loadHTML($output);

			/* Create a new XPath object */
			$xpath = new \DomXPath($dom);
			$nodes = $xpath->query('/html/body/div/div[2]/div[1]/table[2]/tr');

			foreach ($nodes as $node) {

				$i = 0;
				foreach ($node->getElementsByTagName('th') as $key) {
					$jsonKey = preg_replace('/\s+/', '_', strtolower($key->nodeValue));
					$nodeKey[] = [
						'number' => $i,
						'name' => $key->nodeValue,
						'key' => $jsonKey
						];
					$i++;
				}
			}

			$i = 0;
			foreach ($nodes as $node) {
				$j = 0;
				foreach ($node->getElementsByTagName('td') as $col) {
					if ($j == $nodeKey[$j]['number']) {
						$value = $col->nodeValue;
						$jsonKey = $nodeKey[$j]['key'];
						$res[$i][$jsonKey] = $value;
					}
					$j++;
				}
				$i++;
			}

			// http://stackoverflow.com/questions/5217721/how-to-remove-array-element-and-then-re-index-array
			// unset($response[0]);
			$rowLists = array_values($res);

			// reindexing, pretty result
			$finalResponse = [
				'header' => $nodeKey,
				'data'   => $rowLists
				];

			return $finalResponse;
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage()
			];
		}
	}



}