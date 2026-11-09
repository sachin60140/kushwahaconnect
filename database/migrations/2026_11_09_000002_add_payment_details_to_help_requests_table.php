<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_requests', function (Blueprint $table) {
            // Donation details, shown publicly only while the request is approved and public.
            $table->string('account_holder_name')->nullable()->after('description');
            $table->string('bank_account_number', 30)->nullable()->after('account_holder_name');
            $table->string('bank_ifsc', 11)->nullable()->after('bank_account_number');
            $table->string('bank_name')->nullable()->after('bank_ifsc');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('upi_id')->nullable()->after('bank_branch');
            // Private file on the local disk, served via an access-checked route.
            $table->string('upi_qr')->nullable()->after('upi_id');
        });
    }

    public function down(): void
    {
        Schema::table('help_requests', function (Blueprint $table) {
            $table->dropColumn([
                'account_holder_name',
                'bank_account_number',
                'bank_ifsc',
                'bank_name',
                'bank_branch',
                'upi_id',
                'upi_qr',
            ]);
        });
    }
};
