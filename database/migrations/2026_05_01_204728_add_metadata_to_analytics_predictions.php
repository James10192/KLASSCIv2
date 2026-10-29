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
        Schema::table('analytics_predictions', function (Blueprint $table) {
            if (!Schema::hasColumn('analytics_predictions', 'metadata_json')) {
                $table->json('metadata_json')->nullable()->after('explanation_json');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('analytics_predictions', function (Blueprint $table) {
            if (Schema::hasColumn('analytics_predictions', 'metadata_json')) {
                $table->dropColumn('metadata_json');
            }
        });
    }
};
