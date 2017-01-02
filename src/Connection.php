<?php
use RedBeanPHP\R;

/**
 * Created by PhpStorm.
 * User: onur
 * Date: 31.12.2016
 * Time: 17:11
 */
class Connection
{
    private $connection, $user;

    /**
     * @var PDO
     */
    private $pdo;

    private $permission_type, $permission;

    public function __construct($connection, $user = null)
    {
        $this->connection = $connection;
        $this->user = $user;

        $this->pdo = $this->createPDO($connection['cnn_type'], $connection['cnn_settings']);

        if ($user == null || $user['usr_type'] == "admin") {
            $this->permission_type = "full";
        } else {
            $permission = R::getRow("SELECT * FROM permissions WHERE prm_user = :usr_id AND prm_connection = :cnn_id", ['usr_id' => $user['usr_id'], 'cnn_id' => $connection['cnn_id']]);

            if ($permission) {
                $this->permission_type = $permission['prm_permission_type'];

                if ($permission['prm_permission_type'] == "partial") {
                    $this->permission = json_decode($permission['prm_permission'], true);
                }
            } else {
                $this->permission_type = "none";
            }
        }
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->connection['cnn_id'];
    }

    /**
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    private function createPDO($type, $settings)
    {
        if ($type == "mysql") {
            return new PDO(
                "mysql:host=" . $settings['cnn_host'] . ';port=' . $settings['cnn_port'] . ";dbname=" . $settings['cnn_database'],
                $settings['cnn_username'],
                $settings['cnn_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } else if ($type == "postgresql") {
            return new PDO(
                "pgsql:host=" . $settings['cnn_host'] . ';port=' . $settings['cnn_port'] . ";dbname=" . $settings['cnn_database'],
                $settings['cnn_username'],
                $settings['cnn_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } else {
            throw new Exception("Type is invalid");
        }
    }


    public function getFields()
    {
        $tables = [];

        if ($this->connection['cnn_type'] == "mysql") {
            $tables = $this->getMysqlFields();
        } else if ($this->connection['cnn_type'] == "postgresql") {
            $tables = $this->getPostgresqlFields();
        }

        if ($this->permission_type == "full") {
            return $tables;
        } else {

            $partial_tables = [];

            foreach ($tables as $table) {
                if (array_key_exists($table['table_name'], $this->permission)) {
                    $partial_tables[$table['table_name']] = ['table_name' => $table['table_name'], 'columns' => []];

                    foreach ($table['columns'] as $column) {
                        if (in_array($column['column_name'], $this->permission[$table['table_name']])) {
                            $partial_tables[$table['table_name']]['columns'][] = ['column_name' => $column['column_name'], 'column_type' => $column['column_type'], 'column_data_type' => $column['column_data_type']];
                        }
                    }
                }


            }

            return $partial_tables;
        }
    }


    private function getMysqlFields()
    {
        $stmt = $this->pdo->prepare("SELECT TABLE_NAME table_name, COLUMN_NAME column_name, COLUMN_TYPE column_type, DATA_TYPE column_data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:table_schema");
        $stmt->execute(['table_schema' => $this->connection['cnn_settings']['cnn_database']]);
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
        $stmt = $this->pdo->prepare("SELECT TABLE_NAME table_name, COLUMN_NAME column_name, DATA_TYPE column_data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG=:table_schema AND TABLE_SCHEMA='public' AND TABLE_NAME != 'pg_stat_statements'");
        $stmt->execute(['table_schema' => $this->connection['cnn_settings']['cnn_database']]);
        $fields = $stmt->fetchAll();

        $tables = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field['table_name'], $tables)) {
                $tables[$field['table_name']] = ['table_name' => $field['table_name'], 'columns' => []];
            }

            $tables[$field['table_name']]['columns'][] = ['column_name' => $field['column_name'], 'column_type' => $field['column_data_type'], 'column_data_type' => $field['column_data_type']];
        }

        return $tables;
    }

    public static function getConnectionsWithPermissions($usr_id)
    {
        $connections = [];
        $permissions = [];

        foreach (R::getAll("SELECT * FROM permissions WHERE prm_user = :usr_id", ['usr_id' => $usr_id]) as $permission) {
            $permissions[$permission['prm_connection']] = $permission;
        }

        foreach (R::getAll("SELECT * FROM connections") as $connection) {
            if (array_key_exists($connection['cnn_id'], $permissions)) {

                $connection['permission_type'] = $permissions[$connection['cnn_id']]['prm_permission_type'];

                if ($permissions[$connection['cnn_id']]['prm_permission_type'] == "partial") {
                    $connection['permission'] = json_decode($permissions[$connection['cnn_id']]['prm_permission'], true);
                }
            } else {
                $connection['permission_type'] = "none";
            }

            $connections[] = $connection;
        }

        return $connections;
    }

    public static function getConnections()
    {
        $connections = [];

        foreach (R::getAll("SELECT * FROM connections") as $connection) {
            $settings = json_decode($connection['cnn_connection'], true);

            $conn_object = new Connection($connection['cnn_id'], $connection['cnn_type'], [
                'host' => $settings['cnn_host'],
                'port' => $settings['cnn_port'],
                'username' => $settings['cnn_username'],
                'password' => $settings['cnn_password'],
                'database' => $settings['cnn_database']
            ]);

            $connection['tables'] = $conn_object->getFields();

            $connections[] = $connection;
        }

        return $connections;
    }

    public static function getConnectionFromId($cnn_id)
    {
        $connection = R::getRow("SELECT * FROM connections WHERE cnn_id = :cnn_id", ['cnn_id' => $cnn_id]);

        if ($connection) {

            $connection['cnn_settings'] = json_decode($connection['cnn_connection'], true);

            $conn_object = new Connection($connection);

            return $conn_object;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getPermissionType()
    {
        return $this->permission_type;
    }

}