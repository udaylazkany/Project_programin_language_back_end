<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apartment__addresses', function (Blueprint $table) {
            $table->id();
            $table->string('buildingNumber');
            $table->string('floorNumber');
            $table->string('apartmentNumber');
            $table->string('streetName');
            $table->string('city');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apartment__addresses');
    }
};
