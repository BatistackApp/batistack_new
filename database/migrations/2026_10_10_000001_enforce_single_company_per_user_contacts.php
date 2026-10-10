<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Never choose an arbitrary company when legacy data violates the rule.
        $conflictingUsers = DB::table('contacts')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT third_party_id) > 1')
            ->pluck('user_id');

        if ($conflictingUsers->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot enforce one company per user: users linked to multiple companies: '
                .$conflictingUsers->implode(', ')
                .'. Resolve these contacts before rerunning this migration.'
            );
        }

        Schema::create('user_third_party_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('third_party_id')->constrained('third_parties')->restrictOnDelete();
            $table->unique(['user_id', 'third_party_id'], 'user_third_party_accounts_user_company_unique');
            $table->timestamps();
        });

        // Keep one mapping per user/company while allowing multiple contacts.
        $mappings = DB::table('contacts')
            ->select('user_id', 'third_party_id')
            ->whereNotNull('user_id')
            ->distinct()
            ->get();

        foreach ($mappings as $mapping) {
            DB::table('user_third_party_accounts')->insert([
                'user_id' => $mapping->user_id,
                'third_party_id' => $mapping->third_party_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Prevent deleting a mapping from implicitly deleting its contacts.
        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreign(
                ['user_id', 'third_party_id'],
                'contacts_user_company_fk'
            )->references(
                ['user_id', 'third_party_id']
            )->on('user_third_party_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropForeign('contacts_user_company_fk');
        });

        Schema::dropIfExists('user_third_party_accounts');
    }
};
