<?php

namespace Database\Seeders;

use App\Enums\Accounting\JournalType;
use App\Enums\Accounting\LettrageStatus;
use App\Models\Accounting\CompteComptable;
use App\Models\Accounting\EcritureComptable;
use Illuminate\Database\Seeder;

class ComptaSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = CompteComptable::all();
        if ($comptes->isEmpty()) {
            return;
        }

        $journaux = JournalType::cases();
        $mois = collect([
            now()->subMonths(5)->format('Y-m-d'),
            now()->subMonths(4)->format('Y-m-d'),
            now()->subMonths(3)->format('Y-m-d'),
            now()->subMonths(2)->format('Y-m-d'),
            now()->subMonths(1)->format('Y-m-d'),
        ]);

        // Écritures de vente (journaux Ventes)
        foreach ($mois as $date) {
            for ($i = 0; $i < rand(3, 8); $i++) {
                $montant = rand(500, 15000) / 100;
                $compteClient = $comptes->where('numero', '411000')->first() ?? $comptes->random();
                $compteVente = $comptes->where('numero', '707000')->first() ?? $comptes->random();
                $compteTVA = $comptes->where('numero', '445660')->first() ?? $comptes->random();

                // Débit client
                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::VENTES,
                    'numero_piece' => 'VNT-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteClient->numero,
                    'libelle' => 'Facture client #'.($i + 1),
                    'debit' => round($montant * 1.2, 2),
                    'credit' => 0,
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);

                // Crédit vente
                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::VENTES,
                    'numero_piece' => 'VNT-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteVente->numero,
                    'libelle' => 'Vente #'.($i + 1),
                    'debit' => 0,
                    'credit' => $montant,
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);

                // Crédit TVA
                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::VENTES,
                    'numero_piece' => 'VNT-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteTVA->numero,
                    'libelle' => 'TVA collectée #'.($i + 1),
                    'debit' => 0,
                    'credit' => round($montant * 0.2, 2),
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);
            }
        }

        // Écritures d'achat (journaux Achats)
        foreach ($mois as $date) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                $montant = rand(200, 5000) / 100;
                $compteFournisseur = $comptes->where('numero', '401000')->first() ?? $comptes->random();
                $compteAchat = $comptes->where('numero', '607000')->first() ?? $comptes->random();
                $compteTVADeductible = $comptes->where('numero', '445660')->first() ?? $comptes->random();

                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::ACHATS,
                    'numero_piece' => 'ACH-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteAchat->numero,
                    'libelle' => 'Achat fournisseur #'.($i + 1),
                    'debit' => $montant,
                    'credit' => 0,
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);

                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::ACHATS,
                    'numero_piece' => 'ACH-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteTVADeductible->numero,
                    'libelle' => 'TVA déductible #'.($i + 1),
                    'debit' => round($montant * 0.2, 2),
                    'credit' => 0,
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);

                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::ACHATS,
                    'numero_piece' => 'ACH-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteFournisseur->numero,
                    'libelle' => 'Fournisseur #'.($i + 1),
                    'debit' => 0,
                    'credit' => round($montant * 1.2, 2),
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);
            }
        }

        // Écritures de paie (journaux OD)
        foreach ($mois as $date) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                $montant = rand(2000, 5000) / 100;
                $compteCharge = $comptes->where('numero', '641100')->first() ?? $comptes->random();
                $compteTiers = $comptes->where('numero', '421000')->first() ?? $comptes->random();

                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::OD,
                    'numero_piece' => 'PAIE-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteCharge->numero,
                    'libelle' => 'Salaires '.$date,
                    'debit' => $montant,
                    'credit' => 0,
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);

                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::OD,
                    'numero_piece' => 'PAIE-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'compte_numero' => $compteTiers->numero,
                    'libelle' => 'Tiers salarié '.$date,
                    'debit' => 0,
                    'credit' => $montant,
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);
            }
        }
    }
}
