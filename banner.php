<?php
# Подключаем данные для БД
include_once("db.php");

# Устанавливаем соединение с MySQL-сервером
$dbConnection = new PDO('mysql:dbname=' . dbName . ';host=' . dbHost . ';charset=utf8mb4', dbUser, dbPass);
$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    'user_agent' => FunctionsUser::getUserAgent(),
    'page_url' => FunctionsPage::getPage()
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
     * Получение user agent пользователя
     */
    public static function getUserAgent(): string
    {
        $user_agent = '';
        if (isset($_SERVER['HTTP_SEC_CH_UA'])) {
            $user_agent = $_SERVER['HTTP_SEC_CH_UA'];
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        return $user_agent;
    }

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

class FunctionsPage
{
    /**
     * Получение страницы, с которой пришло обращение
     */
    public static function getPage(): string
    {
        $page_url = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $page_url = $_SERVER['HTTP_REFERER'];
        }

        return $page_url;
    }
}