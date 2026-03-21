<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->text('key')->change();
        });

        DB::table('sites')->orderBy('id')->chunk(100, function ($sites) {
            foreach ($sites as $site) {
                DB::table('sites')
                    ->where('id', $site->id)
                    ->update([
                        'key' => Crypt::encryptString($site->key)
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sites')->orderBy('id')->chunk(100, function ($sites) {
            foreach ($sites as $site) {
                DB::table('sites')
                    ->where('id', $site->id)
                    ->update([
                        'key' => Crypt::decryptString($site->key)
                    ]);
            }
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('key')->change();
        });
    }
};
