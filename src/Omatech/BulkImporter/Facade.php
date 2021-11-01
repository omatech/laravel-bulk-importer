<?php

namespace Omatech\Bulkimporter;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return BulkImporter::class;
    }
}
