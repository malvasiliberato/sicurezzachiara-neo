<?php

namespace App\Console\Commands;

use App\Actions\User\EnsureSystemAdminUser;
use Illuminate\Console\Command;

class EnsureSystemAdminUserCommand extends Command
{
    protected $signature = 'sicurezzachiara:ensure-system-admin
                            {email : Email dell\'utente amministratore}
                            {--name= : Nome da usare se l\'utente non esiste ancora}
                            {--password= : Password da impostare o aggiornare}';

    protected $description = 'Crea o aggiorna un utente come amministratore di sistema';

    public function handle(EnsureSystemAdminUser $ensureSystemAdminUser): int
    {
        $result = $ensureSystemAdminUser->handle(
            $this->argument('email'),
            $this->option('name') ?: null,
            $this->option('password') ?: null,
        );

        $user = $result['user'];

        $this->info(sprintf(
            'Utente amministratore pronto: #%d %s <%s>',
            $user->id,
            $user->name,
            $user->email,
        ));

        if ($result['created']) {
            $this->warn('L\'utente non esisteva ed e\' stato creato ex novo.');
            $this->line(sprintf('Password temporanea generata: %s', $result['generated_password']));
        } else {
            $this->line('L\'utente esisteva gia\' ed e\' stato promosso a amministratore di sistema.');
            if ($this->option('password')) {
                $this->line('Password aggiornata secondo il valore richiesto.');
            }
        }

        return self::SUCCESS;
    }
}
