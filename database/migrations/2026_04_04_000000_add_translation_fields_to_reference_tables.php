<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds name_zh (nullable varchar) and translated_at (nullable timestamp)
     * to the four reference tables used by the LLM translation module.
     *
     * Guards with hasTable() because these tables are owned by the data
     * collection project and may not exist in test environments.
     */
    public function up(): void
    {
        foreach (['departments', 'jobs', 'keywords', 'languages'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                if (! Schema::hasColumn($blueprint->getTable(), 'name_zh')) {
                    $blueprint->string('name_zh')->nullable()->after('name');
                }
                if (! Schema::hasColumn($blueprint->getTable(), 'translated_at')) {
                    $blueprint->timestamp('translated_at')->nullable()->after('name_zh');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['departments', 'jobs', 'keywords', 'languages'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $cols = array_filter(
                    ['name_zh', 'translated_at'],
                    fn (string $col) => Schema::hasColumn($blueprint->getTable(), $col)
                );

                if (! empty($cols)) {
                    $blueprint->dropColumn(array_values($cols));
                }
            });
        }
    }
};
