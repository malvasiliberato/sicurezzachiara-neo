<?php

namespace Database\Seeders;

use App\Actions\User\EnsureSystemAdminUser;
use Illuminate\Database\Seeder;

class SystemAdminSeeder extends Seeder
{
    /**
     * Seeder manuale e protetto da env dedicate.
     * Non viene richiamato automaticamente da DatabaseSeeder.
     */
    public function run(EnsureSystemAdminUser $ensureSystemAdminUser): void
    {
        $email = trim((string) env('SC_SYSTEM_ADMIN_EMAIL', ''));
        $password = trim((string) env('SC_SYSTEM_ADMIN_PASSWORD', ''));

        if ($email === '') {
            $this->command?->getOutput()?->writeln('- system admin skipped: SC_SYSTEM_ADMIN_EMAIL non configurata');

            return;
        }

        if ($password === '') {
            $this->command?->getOutput()?->writeln('- system admin skipped: SC_SYSTEM_ADMIN_PASSWORD non configurata');

            return;
        }

        $ensureSystemAdminUser->handle(
            $email,
            env('SC_SYSTEM_ADMIN_NAME') ?: null,
            $password,
        );
    }
}
