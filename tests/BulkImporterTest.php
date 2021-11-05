<?php
namespace Tests;

use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Omatech\BulkImporter\BulkImporter;

class BulkImporterTest extends \Orchestra\Testbench\TestCase
{
    protected $loadEnvironmentVariables = true;

    private $data=[];
    private $limit=1000;
    private $table='test_table';
    private $db='bulkimporter';

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', $this->db);
        $app['config']->set('database.connections.'.$this->db, [
                'driver'   => 'mysql',
                'database' => $this->db,
                'host' => 'database.local',
                'username' => 'root',
                'password' => 'root',
                'prefix'   => '',
            ]);
    }

    // When testing inside of a Laravel installation, this is not needed
    protected function setUp() :void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate', ['--database' => $this->db])->run();
        $this->initTestData();
    }

    private function initTestData()
    {
        $faker = Factory::create();
        $data=[];
        for ($i = 0; $i < $this->limit; $i++) {
            $item=[];
            $item['key']=$faker->uuid();
            $item['name']=$faker->name();
            $item['date']=$this->getValidFakerDate($faker);
            $item['another_date']=$this->getValidFakerDate($faker);
            $item['bigtext']=$faker->text(250);
            $item['created_at']=$this->getValidFakerDate($faker);
            $item['updated_at']='now()';
            $data[]=$item;
        }
        $this->data=$data;
    }

    public function testDataGenerator()
    {
        $bulkImporter=new BulkImporter($this->table);
        $bulkImporter->delete();
        $this->assertEquals($bulkImporter->count(), 0);
        $bulkImporter->import($this->data);
        $this->assertEquals($bulkImporter->count(), $this->limit);
        $this->assertEquals($bulkImporter->count(), count($this->data));
    }

    public function testDataIntegrity()
    {
        $bulkImporter=new BulkImporter($this->table);
        $bulkImporter->delete();
        $bulkImporter->import($this->data);
        $i=0;
        foreach ($this->data as $row) {
            $ret=DB::table($this->table)->where('key', $row['key'])->get();
            $this->assertTrue($ret[0]->name==$row['name']);
            $this->assertTrue($ret[0]->date==$row['date']);
            $this->assertTrue($ret[0]->another_date==$row['another_date']);
            $this->assertTrue($ret[0]->bigtext==$row['bigtext']);
            //$this->assertTrue($ret[0]->created_at==$row['created_at']);
            $i++;
        }
        $this->assertTrue($i==$this->limit);
    }

    public function testInsertedItems()
    {
        $bulkImporter=new BulkImporter($this->table);
        $bulkImporter->delete();
        $bulkImporter->import($this->data);
        $this->assertTrue($bulkImporter->recordsInserted==count($this->data));

        $this->limit=999;
        $this->initTestData();
        $bulkImporter->import($this->data);
        $this->assertTrue($bulkImporter->recordsInserted==count($this->data));
        $this->assertTrue($bulkImporter->recordsInserted==$this->limit);

        $this->limit=1001;
        $this->initTestData();
        $bulkImporter->import($this->data, 1000);
        $this->assertTrue($bulkImporter->recordsInserted==count($this->data));
        $this->assertTrue($bulkImporter->recordsInserted==$this->limit);
        $this->assertTrue($bulkImporter->batchsExecuted==2);


        $this->limit=50;
        $this->initTestData();
        for ($i = 2; $i <= $this->limit; $i++) {
            $bulkImporter->import($this->data, $i);
            //echo "limit=".$this->limit." size=$i batchsExecuted=".$bulkImporter->batchsExecuted." expected=".ceil($this->limit / $i)."\n";
            $this->assertTrue($bulkImporter->recordsInserted==$this->limit);
            $this->assertTrue($bulkImporter->batchsExecuted==ceil($this->limit / $i));
        }
    }

    public function testPerformance()
    {
        $bulkImporter=new BulkImporter($this->table);
        $bulkImporter->delete();
        $this->limit=1000;
        $this->initTestData();

        $initial_seconds=0;
        echo "\nPerformance test output:\n";
        for ($i = 1; $i <= $this->limit; $i=$i*5) {
            $time_start = microtime(true);
            $bulkImporter->import($this->data, $i);
            $time_end = microtime(true);
            $seconds=round(($time_end - $time_start), 2);
            if ($initial_seconds==0) {
                $initial_seconds=$seconds;
            }
            echo "limit=".$this->limit." size=$i batchsExecuted=".$bulkImporter->batchsExecuted." expected=".ceil($this->limit / $i)." seconds=$seconds minutes=".round($seconds/60, 2)."\n";
            $this->assertTrue($bulkImporter->recordsInserted==$this->limit);
            $this->assertTrue($bulkImporter->batchsExecuted==ceil($this->limit / $i));
        }

        // last run
        $time_start = microtime(true);
        $bulkImporter->import($this->data, $this->limit);
        $time_end = microtime(true);
        $seconds=round(($time_end - $time_start), 2);
        echo "limit=".$this->limit." size=".$this->limit." batchsExecuted=".$bulkImporter->batchsExecuted." expected=1 seconds=$seconds minutes=".round($seconds/60, 2)."\n";
        $this->assertTrue($bulkImporter->recordsInserted==$this->limit);
        $this->assertTrue($bulkImporter->batchsExecuted==1);


        echo "Compare $initial_seconds to $seconds, OMFG! it's ".round($initial_seconds/$seconds, 2)." times faster\n";
    }

    private function getValidFakerDate($faker)
    {
        $generatedDateTime=$faker->date('Y-m-d H:i:s');
        // avoid problem with daylight savings
        return str_replace(' 02:', ' 04:', $generatedDateTime);
    }
}
