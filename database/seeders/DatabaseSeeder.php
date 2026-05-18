<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            Ateco2025Seeder::class,
            ComuniElencoSeeder::class,
            TenantBootstrapSeeder::class,
            SicurezzaChiaraCoreJobRolesSeeder::class,
            SicurezzaChiaraCoreEquipmentTypesSeeder::class,
            SicurezzaChiaraCoreWorkplaceTypesSeeder::class,
            SicurezzaChiaraCoreRiskCategoriesSeeder::class,
            SicurezzaChiaraCoreRisksSeeder::class,
            SicurezzaChiaraCoreSourceRiskMappingsSeeder::class,
        ]);

        if ($this->command?->getOutput() !== null) {
            $this->command->getOutput()->writeln('');
            $this->command->getOutput()->writeln('SicurezzaChiara seed strategy:');
            $this->command->getOutput()->writeln('- static catalogs: Ateco2025Seeder + ComuniElencoSeeder');
            $this->command->getOutput()->writeln('- structural: TenantBootstrapSeeder (solo per utenti gia\' esistenti senza workspace)');
            $this->command->getOutput()->writeln('- core starter pack: cataloghi core + mapping prudenziali sorgenti -> rischi -> presidi');
            $this->command->getOutput()->writeln('- baseline/core seed: sicuro per setup locale e staging controllato');
            $this->command->getOutput()->writeln('- baseline esplicito: php artisan db:seed --class=Database\\\\Seeders\\\\SicurezzaChiaraBaselineSeeder');
            $this->command->getOutput()->writeln('- showcase demo/staging only: php artisan db:seed --class=Database\\\\Seeders\\\\SicurezzaChiaraShowcaseSeeder');
            $this->command->getOutput()->writeln('- system admin esplicito: php artisan sicurezzachiara:ensure-system-admin {email} --name=\"...\" --password=\"...\"');
        }

        if (app()->environment('local')) {
            $this->call([
                SicurezzaChiaraBaselineSeeder::class,
            ]);
        }
    }
}
