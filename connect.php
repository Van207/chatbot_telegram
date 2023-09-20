<?php
session_start();
include_once 'vendor/autoload.php';


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vannt_chatbot";
$conn = mysqli_connect($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8");
date_default_timezone_set('Asia/Ho_Chi_Minh');

use Telegram\Bot\Api;


if (!isset($_SESSION['messages'])) {
	$_SESSION['messages'] = array();
}

function send($token, $chatId, $text)
{
	$telegram = new Api($token);
	$telegram->sendMessage([
		'chat_id' => $chatId,
		'text' => $text
	]);
}
function senImages($token, $chatId, $img_url, $caption)
{
	$telegram = new Api($token);
	$telegram->sendPhoto([
		'chat_id' => $chatId,
		'photo' => $img_url,
		'caption' => $caption
	]);
}


function selectDB($conn, $name)
{
	$sql = $sql = "SELECT * FROM `thongtin` WHERE ten LIKE '%$name%'";
	$result = mysqli_query($conn, $sql);
	return $result;
}


function bongda()
{
	$api_key = '8dc0093111944cb7a9828f60ec8d03c2';
	$team_id = '65';
	$now = time();
	$dateTo = date('Y-m-d', $now + 2592000);
	$dateFrom = date('Y-m-d', $now);
	echo $dateFrom . '<br>';
	$url = "https://api.football-data.org/v4/teams/$team_id/matches?status=SCHEDULED&dateFrom=$dateFrom&dateTo=$dateTo";
	$headers = array('X-Auth-Token: ' . $api_key);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);

	curl_close($curl);
	$data = json_decode($response, true);

	$info = "";
	foreach ($data['matches'] as $match) {
		$competition = $match['competition']['name'];
		$home_team = $match['homeTeam']['name'];
		$away_team = $match['awayTeam']['name'];

		// Conver UTC +7
		// $vn_timestamp = strtotime($match['utcDate']) + (7 * 3600);
		$vn_time = date('H:i A d/m/Y', strtotime($match['utcDate']));
		// echo $vn_time;
		$info .= $competition . "\n" . $home_team . ' - ' . $away_team . "\n($vn_time)\n\n";
	}

	return $info;
}
