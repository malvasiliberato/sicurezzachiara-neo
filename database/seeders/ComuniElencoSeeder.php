<?php

namespace Database\Seeders;

use Database\Seeders\Support\StaticCsvTableSeeder;

class ComuniElencoSeeder extends StaticCsvTableSeeder
{
    protected function tableName(): string
    {
        return 'comuni_elenco';
    }

    protected function csvRelativePath(): string
    {
        return 'data/comuni_elenco.csv';
    }

    protected function primaryKey(): string
    {
        return 'istat';
    }

    protected function integerColumns(): array
    {
        return ['istat'];
    }

    protected function nullableColumns(): array
    {
        return ['comune', 'regione', 'provincia', 'provincia_esteso', 'cod_fisco', 'comune_provincia'];
    }
}
