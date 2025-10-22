<?php 
require_once 'config.php';

$apiUrl = $config['canvas']['api_url'];
$accessToken = $config['canvas']['access_token'];

$endpoint = "{$apiUrl}/courses/2681/quizzes?page=3&perpage=10";

/*
$data = array(
	'account_id' => '1',
	'user' => [
		"name" => "Test Student 25",
		"terms_of_use" => true
	],
	'pseudonym' => [
		"unique_id" => "demostudent+25@webaim.org"
	]
);
$fields = json_encode($data);
*/
//echo($fields);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $endpoint); // defined prior to this code

curl_setopt($ch, CURLOPT_HEADER, false);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


//curl_setopt($ch, CURLOPT_POST, true);

//curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); // fields array defined and prepped with json_encode() prior to this code

curl_setopt($ch, CURLOPT_HTTPHEADER, array(

	'Content-Type: application/json',

	'Authorization: Bearer ' . $accessToken

));

$result = curl_exec($ch);

header('Content-Type: application/json; charset=utf-8');
$json = json_decode($result,true);
print_r($result);
//print_r($json);

?>