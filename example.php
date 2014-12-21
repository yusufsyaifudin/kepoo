<?php namespace Kepoo;

set_time_limit(0);

require 'src/Kepoo/Akademika/Akademika.php';

$dir    = './data/Ilmu Komputer/*.{json}';
$files = glob($dir, GLOB_BRACE);

$akademika = new Akademika;
$akademika->username = 'YOUR_USERNAME';
$akademika->password = 'YOUR_PASSWORD';

foreach ($files as $file) {

	$angkatan = json_decode(file_get_contents($file));

	$result = [];
	for ($i=0; $i < count($angkatan); $i++) { 
		$res = $akademika->kepooTarget($angkatan[$i]->niu)->getIps();
		$result[] = array_merge((array)$angkatan[$i], $res);

		// debugging
		print_r( array_merge((array)$angkatan[$i], $res) );
	}

	// save to a file
	$outputFile = pathinfo($file);
	$outDir = './result/Ilmu Komputer/Genap_2013_2014/' . $outputFile['basename'];

	$fp = fopen($outDir,"wb");
	fwrite($fp, json_encode($result));
	fclose($fp);

	// debugging
	print_r('saved to ' . $outputFile['basename']);
}

// header('Content-Type: application/json');

print_r(true);

?>