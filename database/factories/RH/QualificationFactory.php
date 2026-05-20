<?php

namespace Database\Factories\RH;

use App\Enums\RH\CacesSymbol;
use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\ElectricalCertification;
use App\Enums\RH\MedicalVisiteType;
use App\Enums\RH\QualificationType;
use App\Enums\RH\SafetyAidSymbol;
use App\Models\RH\Employee;
use App\Models\RH\Qualification;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualificationFactory extends Factory
{
    protected $model = Qualification::class;

    public function definition(): array
    {
        \Notification::fake();
        $obtainedAt = $this->faker->dateTimeBetween('-3 years', 'now');
        $type = $this->faker->randomElement(QualificationType::class);
        // Ces variables contiennent les instances d'énumérations spécifiques (ex: CacesSymbol, ElectricalCertification)
        $caces = $this->faker->randomElement(CacesSymbol::class);
        $electrical = $this->faker->randomElement(ElectricalCertification::class);
        $safety = $this->faker->randomElement(SafetyAidSymbol::class);
        $medicat = $this->faker->randomElement(MedicalVisiteType::class);

        $specificEnumInstance = match ($type) {
            QualificationType::CACES, QualificationType::PERMIS => $caces,
            QualificationType::ELECTRICAL => $electrical,
            QualificationType::MEDICAL => $medicat,
            QualificationType::SAFETY => $safety,
        };

        // Convertissez l'instance d'énumération spécifique en CertificationSymbol pour l'attribut 'label' du modèle.
        // Cette conversion nécessitera des méthodes statiques dans votre enum CertificationSymbol.
        $certificationLabel = match ($type) {
            QualificationType::CACES, QualificationType::PERMIS => CertificationSymbol::fromCacesSymbol($caces),
            QualificationType::ELECTRICAL => CertificationSymbol::fromElectricalCertification($electrical),
            QualificationType::MEDICAL => CertificationSymbol::fromMedicalVisiteType($medicat),
            QualificationType::SAFETY => CertificationSymbol::fromSafetyAidSymbol($safety),
        };

        $validity = $specificEnumInstance->validityPeriodInMonths() ?? 2;

        return [
            'employee_id' => Employee::factory(),
            'type' => $type,
            'label' => $certificationLabel,
            'reference_number' => $this->faker->bothify('CERT-####-????'),
            'obtained_at' => $obtainedAt,
            'expires_at' => (clone $obtainedAt)->modify('+'.$validity.' months'),
        ];
    }
}
