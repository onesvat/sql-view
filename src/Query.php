<?php
use RedBeanPHP\R;

/**
 * Created by PhpStorm.
 * User: onur
 * Date: 30.12.2016
 * Time: 18:01
 */
class Query
{
    private $query;
    private $query_hash;

    /**
     * @var PDO
     */
    private $connection;

    public function __construct($query, $connection)
    {
        $this->query = $query;
        $this->query_hash = md5($query);

        $this->connection = $connection;
    }

    public function getArray()
    {
        $status = true;
        $error = "";
        $start = microtime(true);

        $data = $this->getFromCache();

        if (!$data) {
            try {
                $stmt = $this->connection->prepare($this->query);
                $stmt->execute();
                $rows = $stmt->fetchAll();
                $columns = [];
                for ($i = 0; $i < $stmt->columnCount(); $i++) {
                    $col = $stmt->getColumnMeta($i);
                    $columns[] = $col['name'];
                }
                $data = ['timestamp' => date("Y-m-d H:i:s"), 'rows' => $rows, 'columns' => $columns];
            } catch (\Exception $e) {
                $status = false;
                $error = $e->getMessage();
            }
        }


        return [
            'status' => $status,
            'timestamp' => $data['timestamp'],
            'time-elapsed' => microtime(true) - $start,
            'rows' => $data['rows'],
            'columns' => $data['columns'],
            'error' => $error
        ];
    }

    private function getFromCache()
    {
        return R::getRow("SELECT cch_result, cch_created_date FROM caches WHERE cch_query_hash = :query_hash ORDER BY cch_created_date DESC LIMIT 1", ['query_hash' => $this->query_hash]);
    }

}