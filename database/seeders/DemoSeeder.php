<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Orchestrate all demo seeders in dependency order.
     * Called conditionally from DatabaseSeeder when DEMO_SEED=true.
     *
     * Prerequisites (already seeded by DatabaseSeeder):
     * - CoreSeeder (Units, VatRates, Company)
     * - ShieldSeeder (Roles & permissions)
     * - PcgSeeder (Plan comptable)
     * - Admin User + Employee + Contract
     */
    public function run(): void
    {
        // Phase 1 - Données de base
        $this->call(TiersSeeder::class);
        $this->call(ItemSeeder::class);
        $this->call(RHSeeder::class);

        // Phase 2 - Modules métier
        $this->call(BanqueSeeder::class);
        $this->call(ChantierSeeder::class);
        $this->call(FlotteSeeder::class);
        $this->call(CommerceSeeder::class);
        $this->call(ImmobilisationSeeder::class);
        $this->call(InterventionSeeder::class);
        $this->call(LocationSeeder::class);

        // Phase 3 - Paie & Salarie
        $this->call(PayrollContributionProfileSeeder::class);
        $this->call(PaieSeeder::class);
        $this->call(SalarieSeeder::class);

        // Phase 4 - Comptabilité & GPAO
        $this->call(ComptaSeeder::class);
        $this->call(GpaoSeeder::class);
    }
}
