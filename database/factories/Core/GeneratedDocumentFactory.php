<?php

namespace Database\Factories\Core;

use App\Models\Core\GeneratedDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneratedDocumentFactory extends Factory
{
    protected $model = GeneratedDocument::class;

    public function definition(): array
    {
        $modules = ['commerce', 'rh', 'chantiers', 'tiers', 'gpao', 'flottes', 'immobilisations', 'interventions', 'articles'];
        $typesByModule = [
            'commerce' => ['devis', 'facture', 'bon_de_commande', 'bon_de_livraison', 'situation'],
            'rh' => ['contrat', 'fiche_salarie', 'timesheet', 'attestation_salaire'],
            'chantiers' => ['ordre_de_service', 'ppsps', 'journal', 'bilan'],
            'tiers' => ['fiche_tiers', 'contrat'],
            'gpao' => ['ordre_de_fabrication'],
            'flottes' => ['fiche_vehicule', 'mise_a_disposition'],
            'immobilisations' => ['fiche_immobilisation', 'etat_dotations'],
            'interventions' => ['contrat_maintenance'],
            'articles' => ['etiquettes'],
        ];

        $module = $this->faker->randomElement($modules);
        $type = $this->faker->randomElement($typesByModule[$module]);

        return [
            'module' => $module,
            'type' => $type,
            'entity_type' => null,
            'entity_id' => null,
            'file_path' => "documents/{$module}/{$type}/".$this->faker->uuid().'.pdf',
            'file_disk' => 'public',
            'file_name' => ucfirst(str_replace('_', ' ', $type)).' '.$this->faker->bothify('####-??'),
            'file_size' => $this->faker->numberBetween(10000, 5000000),
            'generated_by' => User::factory(),
            'generated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
