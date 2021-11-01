## Objective

Insert records in a table massively in a very easy way, you can control the batch size with the optional parameter.

## Usage

Init the BulkImporter:
```
$bulkImporter=new BulkImporter(<TABLE_NAME>);
```

Import massively all the records:
```
$bulkImporter->import($rows);
```

Optionally pass the batch size (default: 1000 records per batch):
```
$bulkImporter->import($rows, 500);
```

$rows should be in the form of an array where the key is the name of the database field and the value can be one of the following:

- string
- date (in mysql format)
- number
- "now()" --> automatically insert the current date.

For example:
[
  0=>[
    'key'=>'1111111',
    'name'=>'Name 1',
    'date1'=>'2021-10-29 01:03:20',
    'created_at'=>'now()'
  ],
  1=>[
    'key'=>'222222',
    'name'=>'Name 2',
    'date1'=>'2021-08-03 09:03:20',
    'created_at'=>'now()'
  ]
  ...
]

Optionally you can use the method delete to remove all the records from the table:

```
$bulkImporter->delete();
```

For easy testing a count method is included too:

```
$num=$bulkImporter->count();
```

## Run tests with

```
php vendor/bin/phpunit
```

## Performance

Bulk import in MySQL is one of the most important performance improvements you can make.

You can see the output of the performance test with 100.000 records and compare the performance with different batch sizes:

limit=100000 size=1 batchsExecuted=100000 expected=100000 seconds=3995.7 minutes=66.6
limit=100000 size=5 batchsExecuted=20000 expected=20000 seconds=890.88 minutes=14.85
limit=100000 size=25 batchsExecuted=4000 expected=4000 seconds=206.85 minutes=3.45
limit=100000 size=125 batchsExecuted=800 expected=800 seconds=57.15 minutes=0.95
limit=100000 size=625 batchsExecuted=160 expected=160 seconds=20.96 minutes=0.35
limit=100000 size=3125 batchsExecuted=32 expected=32 seconds=10.52 minutes=0.18
limit=100000 size=15625 batchsExecuted=7 expected=7 seconds=6.88 minutes=0.11
limit=100000 size=78125 batchsExecuted=2 expected=2 seconds=6.13 minutes=0.1
limit=100000 size=100000 batchsExecuted=1 expected=1 seconds=8.6 minutes=0.14
Compare 3995.7 to 8.6, OMFG! it's 464.62 times faster
