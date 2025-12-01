<?php

namespace Omatech\BulkImporter;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BulkImporter
{
    /**
     * @var string
     */
    private $table = '';

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $initialSql = '';

    /**
     * @var int
     */
    public $batchsExecuted = 0;

    /**
     * @var int
     */
    public $recordsInserted = 0;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function import($rows, $batch_size = 1000)
    {
        assert($rows);
        $this->batchsExecuted = 0;
        $this->recordsInserted = 0;
        $this->initFields($rows[0]);
        $batch = [];
        foreach ($rows as $row) {
            $batch[] = $this->extractValueStringFromRow($row);
            if (count($batch) % $batch_size == 0) {
                $this->insertBatch($batch);
                $batch = [];
            }
        }
        $this->insertBatch($batch);
    }

    public function delete()
    {
        $sql = "delete from " . $this->table;
        $this->run($sql);
    }

    public function count()
    {
        $sql = "select count(*) count from " . $this->table;
        return $this->run($sql)[0]->count;
    }

    private function initFields($row)
    {
        $fields = [];
        foreach ($row as $key => $val) {
            // Escape fields
            $fields[] = "`{$key}`";
        }
        $this->initialSql = "INSERT INTO `{$this->table}` (" . implode(',', $fields) . ") VALUES ";
        $this->fields = $fields;
    }

    private function extractValueStringFromRow($row)
    {
        $values = [];
        foreach ($row as $val) {
            $value = $this->cleanValue($val);
            $values[] = $value;
        }
        return '(' . implode(',', $values) . ')';
    }

    /**
     * Cleans the value for insertion into the database.
     *
     * @param mixed $val The value to be cleaned.
     * @return string The cleaned value.
     */
    private function cleanValue($val)
    {
        // Explicit NULL check - also checks for null or empty string
        if (is_null($val) || $val === null || $val === '') {
            return 'NULL';
        }

        // "now()" function
        if (is_string($val) && strtolower($val) === 'now()') {
            return 'NOW()';
        }

        // Numeric values (int and float)
        if (is_numeric($val)) {
            return $val;
        }

        // String values - use correct escaping
        if (is_string($val)) {
            return DB::connection()->getPdo()->quote($val);
        }

        // Fallback for any other type
        return 'NULL';
    }

    private function insertBatch(array $batch)
    {
        if (!$batch) {
            return;
        }
        $sql = $this->initialSql . implode(',', $batch);
        $this->run($sql);
        $this->batchsExecuted++;
        $this->recordsInserted += count($batch);
    }

    private function run($sql)
    {
        try {
            $ret = DB::select($sql);
            return $ret;
        } catch (QueryException $ex) {
            dd($ex->getMessage());
        }
    }
}