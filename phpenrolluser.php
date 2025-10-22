<?php 
require_once 'config.php';

$apiUrl = $config['canvas']['api_url'];
$accessToken = $config['canvas']['access_token'];

$endpoint = "{$apiUrl}/courses/2681/enrollments";

$data = array(
	'course_id' => '2681',
	'enrollment' => [
		"user_id" => "157180",
		"enrollment_state" => "invited",
		// "user_id" => "157193",
		"notify" => true
	]
);
$fields = json_encode($data);

//echo($fields);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $endpoint); // defined prior to this code

curl_setopt($ch, CURLOPT_HEADER, false);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); // fields array defined and prepped with json_encode() prior to this code

curl_setopt($ch, CURLOPT_HTTPHEADER, array(

	'Content-Type: application/json',

	'Authorization: Bearer ' . $accessToken

));

$result = curl_exec($ch);
$json = json_decode($result,true);

print_r($result);
//print_r($json);


?>