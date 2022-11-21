<?php
# Подключаем данные для БД
include_once("db.php");

# Устанавливаем соединение с MySQL-сервером
$dbConnection = new PDO('mysql:dbname=' . dbName . ';host=' . dbHost . ';charset=utf8mb4', dbUser, dbPass);
$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_agent = '';
if (isset($_SERVER['HTTP_SEC_CH_UA'])) {
    $user_agent = $_SERVER['HTTP_SEC_CH_UA'];
} elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
}

$page_url = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $page_url = $_SERVER['HTTP_REFERER'];
}

$stmt = $dbConnection->prepare('INSERT INTO
    infuse
    (
        ip_address,
        user_agent,
        view_date,
        page_url,
        views_count
    )
    VALUES
    (
        :ip_address,
        :user_agent,
        NOW(),
        :page_url,
        1
    ) ON DUPLICATE KEY UPDATE
        view_date=NOW(),
        views_count=views_count+1');
$stmt->execute([
    'ip_address' => FunctionsUser::getRealIp(),
    'user_agent' => $user_agent,
    'page_url' => $page_url
]);

# Выдаем изображение
$fname = 'spider.webp';
$fp = fopen($fname, 'rb');
header("Content-Type: image/webp");
header("Content-Length: " . filesize($fname));
fpassthru($fp);
exit;

class FunctionsUser
{
    /**
     * Получение "реального" IP пользователя
     */
    public static function getRealIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}