<?php

namespace Database\Seeders\Support;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class StaticCsvTableSeeder extends Seeder
{
    abstract protected function tableName(): string;

    abstract protected function csvRelativePath(): string;

    abstract protected function primaryKey(): string;

    protected function uniqueBy(): array|string
    {
        return $this->primaryKey();
    }

    protected function integerColumns(): array
    {
        return [];
    }

    protected function nullableColumns(): array
    {
        return [];
    }

    protected function sequenceColumn(): ?string
    {
        return null;
    }

    protected function chunkSize(): int
    {
        return 500;
    }

    public function run(): void
    {
        $absolutePath = database_path($this->csvRelativePath());

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Static CSV not found for {$this->tableName()}: {$absolutePath}");
        }

        [$headers, $rows] = $this->readRows($absolutePath);

        if ($headers === [] || $rows === []) {
            throw new RuntimeException("Static CSV is empty for {$this->tableName()}: {$absolutePath}");
        }

        $primaryKey = $this->primaryKey();
        $rowsByPrimaryKey = collect($rows)
            ->keyBy(fn (array $row) => (string) $row[$primaryKey]);

        $updateColumns = array_values(array_filter(
            $headers,
            fn (string $column) => $column !== $primaryKey,
        ));

        DB::transaction(function () use ($primaryKey, $rowsByPrimaryKey, $updateColumns) {
            foreach ($rowsByPrimaryKey->chunk($this->chunkSize()) as $chunk) {
                DB::table($this->tableName())->upsert(
                    $chunk->values()->all(),
                    $this->uniqueBy(),
                    $updateColumns,
                );
            }

            $existingPrimaryKeys = DB::table($this->tableName())
                ->pluck($primaryKey)
                ->map(fn ($value) => (string) $value)
                ->all();

            $csvPrimaryKeys = $rowsByPrimaryKey->keys()->all();
            $keysToDelete = array_values(array_diff($existingPrimaryKeys, $csvPrimaryKeys));

            foreach (array_chunk($keysToDelete, $this->chunkSize()) as $chunk) {
                DB::table($this->tableName())
                    ->whereIn($primaryKey, $chunk)
                    ->delete();
            }

            $this->syncSequence();
        });

        if ($this->command?->getOutput() !== null) {
            $this->command->getOutput()->writeln(sprintf(
                '- static catalog synced: %s (%d righe da %s)',
                $this->tableName(),
                $rowsByPrimaryKey->count(),
                $this->csvRelativePath(),
            ));
        }
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<string, int|string|null>>}
     */
    protected function readRows(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open static CSV: {$absolutePath}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return [[], []];
            }

            $headers = array_map(
                fn ($header) => trim((string) $header),
                $headers,
            );

            $rows = [];

            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }

                $row = [];

                foreach ($headers as $index => $header) {
                    $value = $values[$index] ?? null;
                    $row[$header] = $this->normalizeValue($header, $value);
                }

                $rows[] = $row;
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    protected function normalizeValue(string $column, mixed $value): int|string|null
    {
        if ($value === null) {
            return null;
        }

        $value = trim($this->normalizeEncoding((string) $value));

        if ($value === '' && in_array($column, $this->nullableColumns(), true)) {
            return null;
        }

        if ($value !== '' && in_array($column, $this->integerColumns(), true)) {
            return (int) $value;
        }

        return $value;
    }

    protected function normalizeEncoding(string $value): string
    {
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252, ISO-8859-1');
    }

    protected function syncSequence(): void
    {
        $sequenceColumn = $this->sequenceColumn();

        if ($sequenceColumn === null || DB::getDriverName() !== 'pgsql') {
            return;
        }

        $table = $this->tableName();
        $quotedTable = str_replace("'", "''", $table);
        $quotedColumn = str_replace("'", "''", $sequenceColumn);

        DB::statement(<<<SQL
            SELECT setval(
                pg_get_serial_sequence('{$quotedTable}', '{$quotedColumn}'),
                COALESCE((SELECT MAX({$sequenceColumn}) FROM {$table}), 1),
                (SELECT COUNT(*) > 0 FROM {$table})
            )
        SQL);
    }
}
