<?php
session_start();
include_once 'vendor/autoload.php';

use Telegram\Bot\Api;

$servername = "localhost";
$username = "vannt_24gio";
$password = "123@321";
$dbname = "vannt_testchatbot";
$conn = mysqli_connect($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8");
// Phản hồi
$update = json_decode(file_get_contents("php://input"), TRUE);
$chatId = $update["message"]["chat"]["id"];
$message = $update["message"]["text"];
$OPENAI_API_KEY = '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';
$token = "6246285087:AAEY4WnQ2A_ir6iQnqMcC4w95l6WhZgQOug";
$max_tokens = 4096;


if ($message == '/start') {
	$text = "Xin chào $first_name $last_name\nTôi có thể giúp gì cho bạn?\n Để sử dụng tôi như chatGPT, vui lòng nhập câu hỏi!";
	send($token, $chatId, $text);
} else if ($message == '/stop') {
	$delete = "DELETE FROM `message` WHERE chatid = $chatId";
	mysqli_query($conn, $delete);
	$text = "Thay đổi chủ đề khác, đã xóa hội thoại trước đó!";
	send($token, $chatId, $text);
}
// ChatGPT
if ($message != '/start' && $message != '/stop' && $message != "") {

	insertMsg($conn, $chatId, 'user', $message);

	$select = "SELECT * FROM message WHERE chatid = $chatId ";
	$result = mysqli_query($conn, $select);
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			// Mảng hội thoại
			$msg[] = ['role' => $row['role'], 'content' => $row['content']];
		}
	}

	$url = 'https://api.openai.com/v1/chat/completions';
	$model_id = 'gpt-3.5-turbo';
	if (count($msg) > 0) {
		$data = array(
			'model' => 'gpt-3.5-turbo',
			'messages' => $msg
		);
	} else {
		$data = array(
			'model' => 'gpt-3.5-turbo',
			'messages' => [
				[
					'role' => 'user',
					'content' => $message,
				]
			]
		);
	}


	$data_string = json_encode($data);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $OPENAI_API_KEY
	]);

	$response = curl_exec($ch);
	curl_close($ch);
	$result = json_decode($response);
	if (isset($result->error)) {
		send($token, $chatId, $result->error->message);
	}
	// Thành công 
	else {
		$answer = $result->choices[0]->message->content;
		$total_tokens = $result->usage->total_tokens;
		insertMsg($conn, $chatId, $result->choices[0]->message->role, $answer);
		send($token, $chatId, $answer);
		send($token, $chatId, $total_tokens);
		if (isset($total_tokens) && $total_tokens > ($max_tokens - 100)) {
			$text = "Xin lỗi, hội thoại sẽ kết thúc tại đây vì đạt giới hạn token!\nToken hiện tại là $total_tokens\nBắt đầu hội thoại mới tại đây!";

			$delete = "DELETE FROM `message` WHERE chatid = $chatId";
			mysqli_query($conn, $delete);
			send($token, $chatId, $text);
		}
	}
}



function send($token, $chatId, $text)
{
	$telegram = new Api($token);
	$telegram->sendMessage([
		'chat_id' => $chatId,
		'text' => $text
	]);
}

function insertMsg($conn, $chatId, $role, $content)
{
	$sql = "INSERT INTO `message`(`role`, `content`, `chatid`) VALUES ('$role', '$content', '$chatId')";
	mysqli_query($conn, $sql);
}
