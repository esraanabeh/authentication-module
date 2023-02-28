<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeToVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('verifications', function (Blueprint $table) {
        //     $table->enum('type',['register', 'change_password'])->default('register')->change();

        // });
        DB::statement("ALTER TABLE verifications MODIFY COLUMN type ENUM('register', 'change_password') DEFAULT 'register'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn('type');

        });
    }
}
