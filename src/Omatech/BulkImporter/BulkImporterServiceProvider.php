<?php

namespace Omatech\Bulkimporter;

use Illuminate\Support\ServiceProvider;

final class BulkImporterServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function provides()
    {
        return [
            BulkImporter::class
        ];
    }
}
