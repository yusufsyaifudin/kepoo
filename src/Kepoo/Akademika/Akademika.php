<?php namespace Kepoo;

class Akademika {

	public $username;
	public $password;
	private $kepooTarget;
	private $login_url = "https://akademika.ugm.ac.id/index.php?pModule=b3JqbHE=&pSub=b3JqbHE=&pAct=c3Vydmh2";

	protected $cookies = "my_cookies.txt";

	/**
	 * Return value if class is treated as string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->kepooTarget;
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->curl = curl_init();
		$this->kepooTarget = "";
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

			// check the occurence of keyword "Login tidak berhasil"
			if (strpos($output, 'Login tidak berhasil.') !== false) {
				throw new \Exception("Login is not succesfull.");
			}

			return true;
		}
		
	}


	/**
	 * Get html from dashboard or homepage
	 *
	 * @return string html
	 */
	private function dirtyDashboard()
	{
		if ($this->login()) {
			return $this->login(true);
		} else {
			return false;
		}
		
	}

	/**
	 * Get sidebar menu + link
	 *
	 * @return array
	 */
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

	/**
	 * Get link
	 *
	 * @return string link
	 */
	private function getLink($endpoint)
	{
		$menu = $this->sidebarMenu();

		$link = null;
		for ($i=0; $i<count($menu); $i++) {
			if ($menu[$i]["menu_link_endpoint_api"] == $endpoint) {
				$link = $menu[$i]["menu_link"];
			}
		}

		return $link;
	}

	/**
	 * Get transkrip
	 *
	 * @return mixed
	 */
	public function getTranscript()
	{
		try {
			
			$link = $this->getLink('transkrip_nilai');

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


	/**
	 * Get Kartu Rencana Studi
	 *
	 * @return array
	 */
	public function getKrs()
	{
		try {
			$link = $this->getLink('kartu_rencana_studi');

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
			$nodes = $xpath->query('/html/body/div/div[2]/div[1]/form/table[2]/tr');

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

		} catch (Exception $e) {
			
		}
	}

	/**
	 * Set the kepoo target
	 *
	 * @param string niu
	 *
	 * @return $this
	 */
	public function kepooTarget($kepooTarget = '')
	{
		$ans = '';
		for($i=0;$i < strlen($kepooTarget);$i++)
		{
			$tmp =  substr($kepooTarget, $i, 1);
			$tmp = ord($tmp);
			$ans = $ans . chr((int) $tmp+3);
		}
		$ans = base64_encode($ans);

		$this->kepooTarget = $ans;

		return $this;
	}

	/**
	 * Decode NIU
	 *
	 * @return string
	 */
	private function decodeKepooTarget($kepooTarget)
	{
		$string = base64_decode($kepooTarget);
		$ans = '';
		for($i=0;$i < strlen($string);$i++)
		{
			$tmp =  substr($string, $i, 1);
			$tmp = ord($tmp);
			$ans = $ans . chr((int) $tmp-3);
		}

		return $ans;
	}

	/**
	 * Get Indeks Prestasi Sementara Semester Lalu
	 *
	 * @return array
	 */
	public function getIps()
	{
		// login before doing any action
		$this->login();

		$kepooTarget = $this->kepooTarget;
		$printKrsUrl = 'https://akademika.ugm.ac.id/index.php?pModule=ZGZkZ2hwbGZic29kcQ==&pSub=ZGZkZ2hwbGZic29kcQ==&pAct=c3VscXc=&niu='.urlencode($kepooTarget);

		curl_setopt($this->curl, CURLOPT_URL, $printKrsUrl);
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
		$nodes = $xpath->query('/html/body/div/div/div/table[4]/tr[1]/td[2]');

		$grade = '';
		foreach ($nodes as $node) {
			$grade = $node->nodeValue;
		}

		$grade = preg_replace('/\:\s+/', '', $grade);

		$response = [
			'niu' => $this->decodeKepooTarget($kepooTarget),
			'ips' => $grade
		];

		return $response;
	}

}