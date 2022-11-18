<?php
# Подключаем данные для БД
include_once("db.php");

# Устанавливаем соединение с MySQL-сервером
$dbLink = new dbClass();

$user_agent = '';
if (isset($_SERVER['HTTP_SEC_CH_UA'])) {
    $user_agent = FunctionsText::encode($_SERVER['HTTP_SEC_CH_UA']);
} elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = FunctionsText::encode($_SERVER['HTTP_USER_AGENT']);
}

$page_url = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $page_url = FunctionsText::encode($_SERVER['HTTP_REFERER']);
}

$dbLink->query('INSERT INTO
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
        "' . FunctionsUser::getRealIp() . '",
        "' . $user_agent . '",
        NOW(),
        "' . $page_url . '",
        1
    ) ON DUPLICATE KEY UPDATE
        view_date=NOW(),
        views_count=views_count+1');

# Завершаем соединение с MySQL-сервером
$dbLink->stopMysql();

# Выдаем изображение
$fname = 'spider.webp';
$fp = fopen($fname, 'rb');
header("Content-Type: image/webp");
header("Content-Length: " . filesize($fname));
fpassthru($fp);
exit;

class dbClass
{
    private bool|mysqli $dbLink = false;

    /**
     * Запуск соединения с БД
     */
    function __construct()
    {
        if (!$this->dbLink) {
            $this->dbLink = new mysqli(dbHost, dbUser, dbPass, dbName, dbPort);
            $this->dbLink->set_charset('utf8');
        }
    }

    /**
     * Прекращение соединения с БД
     */
    public function stopMysql(): void
    {
        $this->dbLink->close();
    }

    /**
     * Запрос к БД
     *
     * @param string $query
     *
     * @return mysqli_result|bool
     * @throws Exception
     */
    public function query(string $query): mysqli_result|bool
    {
        try {
            $result = $this->dbLink->query($query);
        } catch (mysqli_sql_exception $e) {
            throw new Exception('Error: ' . $e->getMessage() . ' Query: ' . $query, $e->getCode());
        }

        return $result;
    }
}

class FunctionsText
{
    /**
     * Вычистка и шифровка в безопасный формат поступающих переменных
     *
     * @param string $str
     *
     * @return string
     */
    public static function encode(string $str): string
    {
        $str = str_replace('&gt;', '>', $str);
        $str = str_replace('&lt;', '<', $str);
        $str = preg_replace('/<\s*\/?[a-z0-9]+(\s*[a-z\-0-9]+)*(\s*[a-z\-0-9]+\="[^"]*")*\s*\/?>/ims', '', $str);

        $str = str_replace('[', '&open;', $str);
        $str = str_replace(']', '&close;', $str);
        $str = str_replace("\\'", '&#39', $str);
        $str = str_replace("'", '&#39', $str);
        $str = str_replace('\\"', '&quot;', $str);
        $str = str_replace('"', '&quot;', $str);
        $str = str_replace('<br>', '&br;', $str);
        $str = str_replace("\r\n", '<br>', $str);
        $str = str_replace("\n", '<br>', $str);
        $str = str_replace('>', '&gt;', $str);
        $str = str_replace('<', '&lt;', $str);
        $str = str_replace('\\', '\\\\', $str);

        return $str;
    }
}

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