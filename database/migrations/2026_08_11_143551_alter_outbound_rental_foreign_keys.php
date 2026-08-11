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
        Schema::table('outbound_rental_contracts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['third_party_id']);
            $table->dropForeign(['chantier_id']);

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('third_party_id')->references('id')->on('third_parties')->restrictOnDelete();
            $table->foreign('chantier_id')->references('id')->on('chantiers')->restrictOnDelete();

            $table->foreignId('last_invoice_id')->nullable()->constrained('customer_invoices')->nullOnDelete();
        });

        Schema::table('outbound_rental_lines', function (Blueprint $table) {
            $table->dropForeign(['outbound_rental_contract_id']);
            $table->dropForeign(['fixed_asset_id']);

            $table->foreign('outbound_rental_contract_id')->references('id')->on('outbound_rental_contracts')->restrictOnDelete();
            $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_rental_lines', function (Blueprint $table) {
            $table->dropForeign(['outbound_rental_contract_id']);
            $table->dropForeign(['fixed_asset_id']);

            $table->foreign('outbound_rental_contract_id')->references('id')->on('outbound_rental_contracts')->cascadeOnDelete();
            $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->cascadeOnDelete();
        });

        Schema::table('outbound_rental_contracts', function (Blueprint $table) {
            $table->dropForeign(['last_invoice_id']);
            $table->dropColumn('last_invoice_id');

            $table->dropForeign(['company_id']);
            $table->dropForeign(['third_party_id']);
            $table->dropForeign(['chantier_id']);

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('third_party_id')->references('id')->on('third_parties')->cascadeOnDelete();
            $table->foreign('chantier_id')->references('id')->on('chantiers')->cascadeOnDelete();
        });
    }
};
