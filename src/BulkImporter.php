<?php

namespace Omatech\BulkImporter;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BulkImporter
{
    private string $table='';
    private array $fields;
    private string $initialSql='';
    public int $batchsExecuted=0;
    public int $recordsInserted=0;

    public function __construct(string $table)
    {
        $this->table=$table;
    }

    public function import($rows, $batch_size = 1000)
    {
        assert($rows);
        $this->batchsExecuted=0;
        $this->recordsInserted=0;

        $this->initFields($rows[0]);

        $batch=[];
        foreach ($rows as $row) {
            $batch[]=$this->extractValueStringFromRow($row);
            if (count($batch) % $batch_size == 0) {
                $this->insertBatch($batch);
                $batch=[];
            }
        }
        $this->insertBatch($batch);
    }

    public function delete()
    {
        $sql="delete from ".$this->table;
        $this->run($sql);
    }

    public function count()
    {
        $sql="select count(*) count from ".$this->table;
        return $this->run($sql)[0]->count;
    }

    private function initFields($row)
    {
        $fields = [];
        foreach ($row as $key => $val) {
            // scape fields
            $fields[] = '`'.$key.'`';
        }
        $this->initialSql = "insert into ".$this->table." (".implode(',', $fields).")	values ";
        $this->fields=$fields;
    }

    private function extractValueStringFromRow($row)
    {
        $values=[];
        foreach ($row as $val) {
            $value=$this->cleanValue($val);
            $values[]=$value;
        }
        return '('.implode(',', $values).')';
    }

    private function cleanValue($val)
    {
        if (isset($val) && !is_numeric($val) && $val!='now()') {
            $val=DB::connection()->getPdo()->quote($val);
        }
        if (!$val) {
            if ($val===0)
            {
                $val=0;
            }
            else
            {
                $val='null';
            }
        }
        return $val;
    }

    private function insertBatch(array $batch)
    {
        if (!$batch) {
            return;
        }

        $sql=$this->initialSql.implode(',', $batch);
        $this->run($sql);
        $this->batchsExecuted++;
        $this->recordsInserted+=count($batch);
    }

    private function run($sql)
    {
        try {
            $ret=DB::select($sql);
            return $ret;
        } catch (QueryException $ex) {
            dd($ex->getMessage());
        }
    }
}
