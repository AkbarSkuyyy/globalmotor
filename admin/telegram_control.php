<?php
$token = "8633892536:AAERhCmx52Mqk6pgYsEFM6yjywH-eIEJF3g";
$admin_chat_id = "1151150926"; // ID kamu

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

$message = $update['message']['text'] ?? '';
$chat_id = $update['message']['chat']['id'] ?? '';

if ($chat_id != $admin_chat_id) exit;

if ($message == '/down') {

    file_put_contents('../maintenance.flag','ON');

    $reply = "⚠️ Website berhasil di-LOCK (Maintenance Mode Aktif)";

}

elseif ($message == '/up') {

    if (file_exists('../maintenance.flag')) {
        unlink('../maintenance.flag');
    }

    $reply = "✅ Website kembali NORMAL";
}

else {
    $reply = "Perintah tersedia:\n/down\n/up";
}

file_get_contents("https://api.telegram.org/bot$token/sendMessage?".http_build_query([
    'chat_id'=>$chat_id,
    'text'=>$reply
]));