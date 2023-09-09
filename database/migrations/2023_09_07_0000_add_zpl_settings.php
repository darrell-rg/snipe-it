<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZPLSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        // Schema::table('companies', function (Blueprint $table) {
        //     $table->string('email', 150)->after('fax')->nullable()->default(null);
        // });

        Schema::table('settings', function (Blueprint $table) { 
            $table->boolean('use_zpl')->nullable()->default(0);
            $table->string('zpl_printer_address')->nullable()->default('127.0.0.1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('use_zpl');
            $table->dropColumn('zpl_printer_address');
        });
    }

    
}