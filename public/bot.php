<?php

require "../src/actions.php";

openlog("owordle", LOG_ODELAY, LOG_USER);

$request = file_get_contents("php://input");

$body = json_decode($request, true);

if (is_null($body)) {
    http_response_code(400);
    exit();
}

syslog(LOG_INFO, json_encode($body));

$text = &$body["message"]["text"];
if (!isset($text) || gettype($text) != "string") {
    http_response_code(204);
    exit();
}

$chat = &$body["message"]["chat"];
$user = &$body["message"]["from"];

$msg = [
    "chat_id" => $chat["id"],
    "user_id" => $user["id"],
    "user_name" => $user["first_name"],
    "text" => $text,
];

if ((int) getenv("CHAT_ID") !== $msg["chat_id"]) {
    syslog(LOG_ERR, "Received message for wrong chat: " . $msg["chat_id"]);
    exit();
}

$result = handle_message($msg);

if ($result) {
    header("Content-Type: application/json; charset=utf-8");

    $response = json_encode([
        "method" => "sendMessage",
        "disable_notification" => true,
        "chat_id" => $msg["chat_id"],
        "text" => $result,
    ]);

    echo $response;
}
