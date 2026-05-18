<?php

use Database\Seeders\Ateco2025Seeder;
use Database\Seeders\ComuniElencoSeeder;
use Illuminate\Support\Facades\DB;

test('static catalog seeders populate ateco and comuni tables from versioned csv assets', function () {
    $this->seed([
        Ateco2025Seeder::class,
        ComuniElencoSeeder::class,
    ]);

    expect(DB::table('ateco_2025')->count())->toBe(3257)
        ->and(DB::table('comuni_elenco')->count())->toBe(7978)
        ->and(DB::table('ateco_2025')->where('codice', 'A')->value('titolo_it'))->toBe('AGRICOLTURA, SILVICOLTURA E PESCA')
        ->and(DB::table('comuni_elenco')->where('istat', 1001)->value('comune_provincia'))->toBe('Agliè (TO)');
});
