<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

final class ContactImport implements ToCollection
{
    public function collection(Collection $collection)
    {
        //
    }
}
