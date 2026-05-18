<?php

namespace Database\Seeders;

use Database\Seeders\Support\StaticCsvTableSeeder;

class Ateco2025Seeder extends StaticCsvTableSeeder
{
    protected function tableName(): string
    {
        return 'ateco_2025';
    }

    protected function csvRelativePath(): string
    {
        return 'data/ateco_2025.csv';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    protected function integerColumns(): array
    {
        return ['id', 'livello', 'livello_padre', 'ordine'];
    }

    protected function nullableColumns(): array
    {
        return ['titolo_en', 'codice_padre', 'livello_padre'];
    }

    protected function sequenceColumn(): ?string
    {
        return 'id';
    }
}
