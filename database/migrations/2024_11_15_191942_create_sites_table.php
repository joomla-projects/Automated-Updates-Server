<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('url')->index();
            $table->string('key');
            $table->string('php_version');
            $table->string('ddb_type');
            $table->string('db_version');
            $table->string('cms_version')->index();
            $table->string('server_os');
            $table->boolean('update_patch');
            $table->boolean('update_minor');
            $table->boolean('update_major');
            $table->dateTime('last_seen');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('sites');
    }
};
