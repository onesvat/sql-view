<?php

/**
 * Created by PhpStorm.
 * User: onur
 * Date: 31.12.2016
 * Time: 17:11
 */
class Connection
{
    private $id, $type, $settings;

    /**
     * @var PDO
     */
    private $connection;

    public function __construct($id, $type, $settings)
    {
        $this->id = $id;
        $this->type = $type;
        $this->settings = $settings;

        $this->connection = $this->createPDO($type, $settings);
    }

    public function getFields()
    {
        if ($this->type == "mysql") {
            return $this->getMysqlFields();
        } else if ($this->type == "postgresql") {
            return $this->getPostgresqlFields();
        }
    }

    private function createPDO($type, $settings)
    {
        if ($type == "mysql") {
            return new PDO(
                "mysql:host=" . $settings['host'] . ';port=' . $settings['port'] . ";dbname=" . $settings['database'],
                $settings['username'],
                $settings['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } else if ($type == "postgresql") {
            return new PDO(
                "pgsql:host=" . $settings['host'] . ';port=' . $settings['port'] . ";dbname=" . $settings['database'],
                $settings['username'],
                $settings['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } else {
            throw new Exception("Type is invalid");
        }
    }

    private function getMysqlFields()
    {
        $stmt = $this->connection->prepare("SELECT TABLE_NAME table_name, COLUMN_NAME column_name, COLUMN_TYPE column_type, DATA_TYPE column_data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:table_schema");
        $stmt->execute(['table_schema' => $this->settings['database']]);
        $fields = $stmt->fetchAll();

        $tables = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field['table_name'], $tables)) {
                $tables[$field['table_name']] = ['table_name' => $field['table_name'], 'columns' => []];
            }

            $tables[$field['table_name']]['columns'][] = ['column_name' => $field['column_name'], 'column_type' => $field['column_type'], 'column_data_type' => $field['column_data_type']];
        }

        return $tables;
    }

    private function getPostgresqlFields()
    {
        return [];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

}