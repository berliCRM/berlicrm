<?php

/** SQL export over a dedicated, unbuffered PHP connection; no command line client. */
class Settings_Vtiger_BackupDatabase_Model
{
    private $db;

    public function __construct(?mysqli $connection = null)
    {
        global $dbconfig;
        if ($connection) { $this->db = $connection; return; }
        $host = $dbconfig['db_server'];
        $port = ltrim((string) ($dbconfig['db_port'] ?? '3306'), ':') ?: '3306';
        $this->db = new mysqli($host, $dbconfig['db_username'], $dbconfig['db_password'],
            $dbconfig['db_name'], (int) $port, $dbconfig['db_socket'] ?? null);
        if ($this->db->connect_errno || !$this->db->set_charset('utf8mb4')) {
            throw new RuntimeException('LBL_BACKUP_DUMP_FAILED');
        }
    }

    private function query($sql)
    {
        $result = $this->db->query($sql, MYSQLI_USE_RESULT);
        if ($result === false) { throw new RuntimeException('LBL_BACKUP_DUMP_FAILED'); }
        return $result;
    }

    private static function identifier($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private function rows($sql)
    {
        $query = $this->query($sql);
        try { return $query->fetch_all(MYSQLI_ASSOC); }
        finally { $query->free(); }
    }

    private function create($type, $name, $row = null)
    {
        $row = $row ?? $this->rows('SHOW CREATE ' . $type . ' ' . self::identifier($name))[0];
        foreach ($row as $key => $value) {
            if (stripos($key, 'Create ') === 0 || $key === 'SQL Original Statement') {
                if ($value === null || $value === '') { break; }
                return $value;
            }
        }
        throw new RuntimeException('LBL_BACKUP_DUMP_FAILED');
    }

    public function export()
    {
        // Only metadata is buffered. Table contents are fetched one row at a time.
        $tables = $this->rows('SHOW FULL TABLES');
        $status = $this->rows('SHOW TABLE STATUS');
        foreach ($status as $table) {
            if (!empty($table['Engine']) && strcasecmp($table['Engine'], 'InnoDB') !== 0) {
                throw new RuntimeException('LBL_BACKUP_ENGINE');
            }
        }
        $triggers = $this->rows('SHOW TRIGGERS');
        $routines = $this->rows("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()");
        $events = $this->rows('SHOW EVENTS');
        $views = array();
        $this->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $this->query("SET SESSION time_zone = '+00:00'");
        $this->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        $committed = false;
        try {
            yield "-- berliCRM PHP backup; import into an empty compatible database.\n"
                . "SET NAMES utf8mb4;\nSET @BACKUP_OLD_FK=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\n"
                . "SET @BACKUP_OLD_MODE=@@SQL_MODE;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n"
                . "SET @BACKUP_OLD_TZ=@@TIME_ZONE;\nSET TIME_ZONE='+00:00';\n";
            foreach ($tables as $table) {
                $values = array_values($table);
                $name = $values[0];
                if ($values[1] === 'VIEW') { $views[] = $name; continue; }
                if ($values[1] !== 'BASE TABLE') { throw new RuntimeException('LBL_BACKUP_ENGINE'); }
                $quoted = self::identifier($name);
                yield 'DROP TABLE IF EXISTS ' . $quoted . ";\n" . $this->create('TABLE', $name) . ";\n";
                $columns = array();
                $textColumns = array();
                foreach ($this->rows('SHOW FULL COLUMNS FROM ' . $quoted) as $column) {
                    if (preg_match('/(?:VIRTUAL|STORED|PERSISTENT) GENERATED/i', $column['Extra'])) { continue; }
                    $columns[] = $column['Field'];
                    $textColumns[] = $column['Collation'] !== null;
                }
                if (!$columns) { throw new RuntimeException('LBL_BACKUP_DUMP_FAILED'); }
                $columnSql = implode(',', array_map(array(self::class, 'identifier'), $columns));
                $query = $this->query('SELECT ' . $columnSql . ' FROM ' . $quoted);
                try {
                    while (($row = $query->fetch_row()) !== null) {
                        if ($row === false) { throw new RuntimeException('LBL_BACKUP_DUMP_FAILED'); }
                        yield 'INSERT INTO ' . $quoted . ' (' . $columnSql . ') VALUES (';
                        foreach ($row as $i => $value) {
                            if ($i) { yield ','; }
                            if ($value === null) { yield 'NULL'; continue; }
                            // Hex strings preserve binary values and are independent of SQL escaping modes.
                            if ($textColumns[$i]) { yield 'CONVERT('; }
                            yield "X'";
                            for ($offset = 0; $offset < strlen($value); $offset += 262144) {
                                yield bin2hex(substr($value, $offset, 262144));
                            }
                            yield "'";
                            if ($textColumns[$i]) { yield ' USING utf8mb4)'; }
                        }
                        yield ");\n";
                    }
                } finally { $query->free(); }
            }
            // Functions must exist before views that invoke them are restored.
            foreach ($routines as $routine) {
                $type = $routine['ROUTINE_TYPE'];
                if (!in_array($type, array('PROCEDURE', 'FUNCTION'), true)) { throw new RuntimeException('LBL_BACKUP_DUMP_FAILED'); }
                yield from $this->program($type, $routine['ROUTINE_NAME']);
            }
            // Temporary stand-ins allow views to refer to views created later.
            foreach ($views as $view) {
                $columns = $this->rows('SHOW FULL COLUMNS FROM ' . self::identifier($view));
                yield 'CREATE TABLE ' . self::identifier($view) . ' (' . implode(',', array_map(function ($column) {
                    return self::identifier($column['Field']) . ' int';
                }, $columns)) . ");\n";
            }
            foreach ($views as $view) {
                yield 'DROP TABLE ' . self::identifier($view) . ";\n" . $this->create('VIEW', $view) . ";\n";
            }
            // Client DELIMITER commands preserve semicolons inside stored programs.
            foreach ($triggers as $trigger) { yield from $this->program('TRIGGER', $trigger['Trigger']); }
            foreach ($events as $event) { yield from $this->program('EVENT', $event['Name']); }
            yield "SET FOREIGN_KEY_CHECKS=@BACKUP_OLD_FK;\nSET SQL_MODE=@BACKUP_OLD_MODE;\nSET TIME_ZONE=@BACKUP_OLD_TZ;\n";
            $this->query('COMMIT');
            $committed = true;
        } finally {
            if (!$committed) { $this->db->rollback(); }
        }
    }

    private function program($type, $name)
    {
        $row = $this->rows('SHOW CREATE ' . $type . ' ' . self::identifier($name))[0];
        $sql = $this->create($type, $name, $row);
        if (isset($row['sql_mode'])) { yield "SET SQL_MODE=X'" . bin2hex($row['sql_mode']) . "';\n"; }
        if (isset($row['time_zone'])) { yield "SET TIME_ZONE=X'" . bin2hex($row['time_zone']) . "';\n"; }
        $delimiter = ';;BACKUP_' . bin2hex(random_bytes(8));
        yield 'DELIMITER ' . $delimiter . "\nDROP " . $type . ' IF EXISTS ' . self::identifier($name)
            . $delimiter . "\n" . $sql . $delimiter . "\nDELIMITER ;\n";
        yield "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET TIME_ZONE='+00:00';\n";
    }
}
