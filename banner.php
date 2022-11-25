<?php
ini_set("log_errors", 1);
ini_set("display_errors", false);
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_RECOVERABLE_ERROR);

# Подключаем данные для БД
include_once("db.php");

# Устанавливаем соединение с базой данных
$dbConnection = new FunctionsDB();

# Добавляем / пополняем данные в БД: вносим IP, user agent и URL-страницы изображения. Если такие данные уже есть, обновляем дату и увеличиваем счетчик просмотров.
$dbConnection->execute(
    'INSERT INTO
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
        views_count=views_count+1',
    [
        ['ip_address', FunctionsUser::getRealIp()],
        ['user_agent', FunctionsUser::getUserAgent()],
        ['page_url', FunctionsPage::getPage(), 'url']
    ]
);

# Выдаем изображение
$fname = 'spider.webp';
$fp = fopen($fname, 'rb');
header("Content-Type: image/webp");
header("Content-Length: " . filesize($fname));
fpassthru($fp);
exit;

abstract class FunctionsUser
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

abstract class FunctionsPage
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

class FunctionsDB
{
    private PDO $dbConnection;

    /**
     * Создание соединения
     */
    public function __construct()
    {
        $this->dbConnection = new PDO(dbType . ':dbname=' . dbName . ';host=' . dbHost . ';port=' . dbPort . ';charset=utf8mb4', dbUser, dbPass);
        $this->dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Подготовка запроса
     *
     * @param string $query
     *
     * @return PDOStatement|bool
     */
    public function prepare(string $query): PDOStatement|bool
    {
        return $this->dbConnection->prepare($query);
    }

    /**
     * Исполнение запроса
     *
     * @param PDOStatement|bool|string $query
     * @param array $data
     *
     * @return bool
     */
    public function execute(PDOStatement|bool|string $query, array $data): bool
    {
        if ($query !== false) {
            $prepared_data = [];
            foreach ($data as $data_block) {
                $key = $data_block[0];
                $value = $data_block[1];

                if ($data_block[2] == 'url') {
                    $value = filter_var($value, FILTER_SANITIZE_URL);
                } else {
                    $value = htmlspecialchars($value);
                }

                $prepared_data[$key] = $value;
            }

            $stmt = $query instanceof PDOStatement ? $query : $this->prepare($query);

            return $stmt->execute(
                $prepared_data
            );
        } else {
            return false;
        }
    }
}