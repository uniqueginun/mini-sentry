<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('project_keys')->orderBy('id')->each(function (object $row): void {
            if (! is_string($row->public_key) || strlen($row->public_key) === 64) {
                return;
            }

            DB::table('project_keys')->where('id', $row->id)->update([
                'prefix' => substr($row->public_key, 0, 8),
                'public_key' => hash('sha256', $row->public_key),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Irreversible: SHA-256 hashes cannot be converted back to plaintext keys.
     */
    public function down(): void
    {
        //
    }
};
