<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_settings', function (Blueprint $table) {
            $table->text('scope_en')->nullable()->after('scope');
            $table->longText('description_en')->nullable()->after('description');
            $table->longText('ethics_policy_en')->nullable()->after('ethics_policy');
            $table->longText('oa_policy_en')->nullable()->after('oa_policy');
            $table->longText('apc_policy_en')->nullable()->after('apc_policy');
        });
    }

    public function down(): void
    {
        Schema::table('journal_settings', function (Blueprint $table) {
            $table->dropColumn(['scope_en', 'description_en', 'ethics_policy_en', 'oa_policy_en', 'apc_policy_en']);
        });
    }
};
