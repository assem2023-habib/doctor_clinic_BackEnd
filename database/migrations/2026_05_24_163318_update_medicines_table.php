<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private bool $isMysql;

    public function __construct()
    {
        $this->isMysql = DB::connection()->getDriverName() === 'mysql';
    }

    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (Schema::hasColumn('medicines', 'price')) {
                $table->dropColumn('price');
            }

            if (!Schema::hasColumn('medicines', 'barcode')) {
                $table->string('barcode')->nullable();
            }

            if (!Schema::hasColumn('medicines', 'manufacturer')) {
                $table->string('manufacturer')->nullable();
            }
        });

        if ($this->isMysql) {
            $this->convertToJsonIfNeeded('name', 'JSON NOT NULL');
            $this->convertToJsonIfNeeded('description', 'JSON NULL');
        }
    }

    public function down(): void
    {
        if ($this->isMysql) {
            $this->convertFromJsonIfNeeded('name', 'VARCHAR(255) NOT NULL');
            $this->convertFromJsonIfNeeded('description', 'TEXT NULL');
        }

        Schema::table('medicines', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable();

            if (Schema::hasColumn('medicines', 'barcode')) {
                $table->dropColumn('barcode');
            }

            if (Schema::hasColumn('medicines', 'manufacturer')) {
                $table->dropColumn('manufacturer');
            }
        });
    }

    private function convertToJsonIfNeeded(string $column, string $type): void
    {
        if (Schema::hasColumn('medicines', $column) && !$this->isJsonColumn($column)) {
            DB::statement("ALTER TABLE medicines MODIFY {$column} {$type}");
        }
    }

    private function convertFromJsonIfNeeded(string $column, string $type): void
    {
        if (Schema::hasColumn('medicines', $column) && $this->isJsonColumn($column)) {
            DB::statement("ALTER TABLE medicines MODIFY {$column} {$type}");
        }
    }

    private function isJsonColumn(string $column): bool
    {
        if ($this->isMysql) {
            $result = DB::select('SHOW COLUMNS FROM medicines WHERE Field = ?', [$column]);
            return $result && str_contains($result[0]->Type, 'json');
        }

        $result = DB::select("PRAGMA table_info('medicines')");
        foreach ($result as $row) {
            if ($row->name === $column) {
                return str_contains(strtolower($row->type), 'json');
            }
        }

        return false;
    }
};
