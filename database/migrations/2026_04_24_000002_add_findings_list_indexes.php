<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            if (!Schema::hasIndex('vuln_findings', 'vfl_severity_id_idx')) {
                $table->index(['severity', 'id'], 'vfl_severity_id_idx');
            }
            if (!Schema::hasIndex('vuln_findings', 'vfl_created_at_idx')) {
                $table->index('created_at', 'vfl_created_at_idx');
            }
            if (!Schema::hasIndex('vuln_findings', 'vfl_vuln_name_idx')) {
                $table->index('vuln_name', 'vfl_vuln_name_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            if (Schema::hasIndex('vuln_findings', 'vfl_severity_id_idx'))  $table->dropIndex('vfl_severity_id_idx');
            if (Schema::hasIndex('vuln_findings', 'vfl_created_at_idx'))   $table->dropIndex('vfl_created_at_idx');
            if (Schema::hasIndex('vuln_findings', 'vfl_vuln_name_idx'))    $table->dropIndex('vfl_vuln_name_idx');
        });
    }
};
