<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formas_pagamento', function (Blueprint $table) {
            if (!Schema::hasColumn('formas_pagamento', 'pix_chaves')) {
                $table->json('pix_chaves')->nullable()->after('conta_bancaria_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('formas_pagamento', function (Blueprint $table) {
            if (Schema::hasColumn('formas_pagamento', 'pix_chaves')) {
                $table->dropColumn('pix_chaves');
            }
        });
    }
};
