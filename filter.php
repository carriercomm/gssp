<?php

/*
The MIT License (MIT)

Copyright (c) 3013, 2014 Martin Maly

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.


 */

define("TIMEOUT", 600);
function get($key, $sheet = 1, $skey='') {

$url = "https://spreadsheets.google.com/feeds/list/$key/$sheet/public/values?alt=json";

// Initialize session and set URL.
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);

// Set so curl_exec returns the result instead of outputting it.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
// Get the response and close the channel.
$response = curl_exec($ch);
curl_close($ch);
file_put_contents("./data/".$skey, $response);
file_put_contents("./data/".$skey.'.stamp', time());
return $response;
}

function filt($a) {
	$out = array();
	$col = 0;
	foreach ($a as $id=>$data) {
		if (strpos($id, 'gsx$')===false) continue;
		$n = substr($id,4);
		if(isset($_GET['cols'])) {$n = 'c'.$col;}
		if(isset($_GET['nocols'])) {
			$out[] = trim($data['$t']);
		} else {
			$out[$n] = trim($data['$t']);
		}
		$col++;
	}
	return $out;
}


$key = $_GET['key'];

$sheet = isset($_GET['sheet'])?intval($_GET['sheet']):1;
if ($sheet<1) $sheet = 1;

$key=preg_replace("/[^a-zA-Z0-9\-\_]/is","",$key);

//allow test
$allowed = file("./allowed.keys");
$go=false;
foreach($allowed as $allow) {
	if (trim($allow)==$key) {
		$go = true; break;
	}
}
if (!$go) {
	header('HTTP/1.0 403 Forbidden', true, 403);
	die();
}
//--allow test


$skey = $key;
if ($sheet > 1) $skey.='-sheet'.$sheet;

$last = file_exists("./data/$skey.stamp") ? file_get_contents("./data/$skey.stamp") : 0;

$now = time();

if (($last<($now - TIMEOUT) || isset($_GET['nocache'])) && !isset($_GET['forcecache'])) {get($key, $sheet, $skey);header('x-goog-cached: none');} else {header('x-goog-cached: cache');}

$response = file_get_contents("./data/".$skey);

$go = json_decode($response, true);
$out = array();
$data = $go['feed']['entry'];
for ($i=0;$i<count($data);$i++) {
	$out[$i] = filt($data[$i]);
}

//print_r($out);

$response = json_encode($out);

$response = preg_replace_callback('/(?:\\\\u[0-9a-fA-Z]{4})+/', function ($v) {
    $v = strtr($v[0], array('\\u' => ''));
    return mb_convert_encoding(pack('H*', $v), 'UTF-8', 'UTF-16BE');
}, $response);

if (isset($_GET['cb'])) {
	$response = $_GET['cb'].'('.$response.');';
} else if (isset($_GET['var'])) {
	$response = $_GET['var'].' = '.$response.';';
}  else if (isset($_GET['php'])) {
	$response = serialize($out);
}

if (isset($_GET['win'])){ $response = iconv("UTF-8", "Windows-1250", $response);header("Content-Type: text/javascript; charset=windows-1250");} 
else {
	header("Content-Type: text/javascript; charset=utf-8");
}

echo $response;