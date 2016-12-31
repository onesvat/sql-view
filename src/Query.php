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

    private $connection_id;

    public function __construct($query, $connection, $connection_id)
    {
        $this->query = $query;
        $this->query_hash = md5($query);

        $this->connection = $connection;
        $this->connection_id = $connection_id;
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

        $this->addToQueries($data['columns'], $data['rows']);


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
        $result = R::getRow("SELECT que_result, que_updated_date FROM queries WHERE que_hash = :que_hash AND que_connection = :que_connection LIMIT 1", ['que_hash' => $this->query_hash, 'que_connection' => $this->connection_id]);

        if ($result) {
            $data = json_decode($result['que_result'], true);

            return ['timestamp' => $result['que_updated_date'], 'columns' => $data['columns'], 'rows' => $data['rows']];
        }

        return false;
    }

    private function addToQueries($columns, $rows)
    {

        $result_string = json_encode(['columns' => $columns, 'rows' => $rows]);

        R::exec("REPLACE INTO queries (que_connection, que_string, que_hash, que_cache, que_result, que_result_hash, que_updated_date, que_created_date) VALUES(:que_connection, :que_string, :que_hash, 0, :que_result, :que_result_hash,:que_updated_date, :que_created_date)", [
            'que_connection' => $this->connection_id,
            'que_string' => $this->query,
            'que_hash' => $this->query_hash,
            'que_result' => $result_string,
            'que_result_hash' => md5($result_string),
            'que_updated_date' => date('Y-m-d H:i:s'),
            'que_created_date' => date('Y-m-d H:i:s')
        ]);
    }

}