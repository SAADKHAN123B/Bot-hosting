<?php
$TOKEN = "8468425137:AAGg98TKK2cvomay1zuEMzQWE74b_i0Ba7Q";
$OWNER_ID = 8027087942;
$API_URL = "https://api.telegram.org/bot$TOKEN/";

// Store mapping in file
$MAP_FILE = __DIR__ . "/map.json";
if (!file_exists($MAP_FILE)) file_put_contents($MAP_FILE, json_encode([]));

function loadMap() {
    global $MAP_FILE;
    return json_decode(file_get_contents($MAP_FILE), true);
}
function saveMap($map) {
    global $MAP_FILE;
    file_put_contents($MAP_FILE, json_encode($map));
}

function sendMessage($chat_id, $text) {
    global $API_URL;
    $data = ["chat_id" => $chat_id, "text" => $text];

    $ch = curl_init($API_URL . "sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    curl_close($ch);

    return json_decode($res, true);
}

function forwardMessage($to_chat, $from_chat, $msg_id) {
    global $API_URL;
    $data = [
        "chat_id" => $to_chat,
        "from_chat_id" => $from_chat,
        "message_id" => $msg_id
    ];

    $ch = curl_init($API_URL . "forwardMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    curl_close($ch);

    return json_decode($res, true);
}

// Webhook input
$update = json_decode(file_get_contents("php://input"), true);
if (!$update || !isset($update["message"])) exit;

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$msg_id  = $message["message_id"];
$text    = $message["text"] ?? "";
$from_id = $message["from"]["id"];
$username = isset($message["from"]["username"]) ? "@" . $message["from"]["username"] : $message["from"]["first_name"];

$map = loadMap();

// /start command
if ($text == "/start") {
    $welcome = "✨ Welcome, SRC HUB Support Bot ✨\n\n" .
               "⚠️ Use: /help your message\n\n" .
               "📩 Your message will be forwarded to the owner.\n" .
               "✅ Owner will reply back to you here.";
    sendMessage($chat_id, $welcome);
    exit;
}

// Agar OWNER reply kare
if ($from_id == $OWNER_ID && isset($message["reply_to_message"])) {
    $reply_msg_id = $message["reply_to_message"]["message_id"];

    if (isset($map[$reply_msg_id])) {
        $user_chat_id = $map[$reply_msg_id];

        // ON indicator
        sendMessage($user_chat_id, "✅ Owner is typing...");

        // 0.1 sec delay
        usleep(100000);

        // Owner reply send
        sendMessage($user_chat_id, "📩 Owner Reply:\n" . $text);

        // OFF indicator
        sendMessage($user_chat_id, "❌ Owner stopped replying");
    }
}
// Agar USER msg bheje
else if ($from_id != $OWNER_ID) {
    sendMessage($chat_id, "✅ Your message has been sent to the owner.");

    // Forward user msg to OWNER
    $fwd = forwardMessage($OWNER_ID, $chat_id, $msg_id);
    if (isset($fwd["result"]["message_id"])) {
        $map[$fwd["result"]["message_id"]] = $chat_id;
        saveMap($map);
    }
}
?>