<?php namespace PalPalych\AutoInterlinking\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateKeywordsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('palpalych_autointerlinking_keywords', function(Blueprint $table) {
            $table->id();
            $table->string('keyword')->nullable();
            $table->string('url')->nullable();
            $table->boolean('active')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('palpalych_autointerlinking_keywords');
    }
};
