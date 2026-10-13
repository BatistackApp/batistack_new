<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Resources\ConsultationResource\Pages\ListConsultations;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ConsultationOffer;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('sous-traitant');
});

function authenticateConsultationSubcontractor(): array
{
    $user = User::factory()->create(['is_tiers' => true]);
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $subcontractor->id,
        'email' => null,
    ]);

    test()->actingAs($user);

    return [$user, $subcontractor];
}

it('executes the real response action and persists an offer for the authenticated subcontractor', function () {
    [, $subcontractor] = authenticateConsultationSubcontractor();
    $consultation = Consultation::factory()->create(['status' => 'published']);

    livewire(ListConsultations::class)
        ->assertCanSeeTableRecords([$consultation])
        ->assertActionVisible(TestAction::make('respond')->table($consultation))
        ->callAction(TestAction::make('respond')->table($consultation), [
            'amount' => 1250.50,
            'message' => 'Offre transmise depuis le test Filament.',
        ]);

    $offer = ConsultationOffer::query()
        ->where('consultation_id', $consultation->id)
        ->where('third_party_id', $subcontractor->id)
        ->first();

    expect($offer)->not->toBeNull()
        ->and((float) $offer->amount)->toBe(1250.50)
        ->and($offer->message)->toBe('Offre transmise depuis le test Filament.')
        ->and($offer->status)->toBe('submitted');
});

it('updates the existing offer instead of creating a duplicate', function () {
    [, $subcontractor] = authenticateConsultationSubcontractor();
    $consultation = Consultation::factory()->create(['status' => 'published']);

    $existingOffer = ConsultationOffer::query()->create([
        'consultation_id' => $consultation->id,
        'third_party_id' => $subcontractor->id,
        'amount' => 900,
        'message' => 'Ancienne offre',
        'status' => 'submitted',
    ]);

    livewire(ListConsultations::class)
        ->callAction(TestAction::make('respond')->table($consultation), [
            'amount' => 1400,
            'message' => 'Offre révisée.',
        ]);

    expect(ConsultationOffer::query()
        ->where('consultation_id', $consultation->id)
        ->where('third_party_id', $subcontractor->id)
        ->count())->toBe(1)
        ->and($existingOffer->fresh()->amount)->toBe('1400.00')
        ->and($existingOffer->fresh()->message)->toBe('Offre révisée.');
});

it('does not submit an offer if a consultation closes after the action modal is opened', function () {
    [, $subcontractor] = authenticateConsultationSubcontractor();
    $consultation = Consultation::factory()->create(['status' => 'published']);

    $component = livewire(ListConsultations::class)
        ->mountAction(TestAction::make('respond')->table($consultation))
        ->fillForm([
            'amount' => 1500,
            'message' => 'Cette offre ne doit pas être enregistrée.',
        ]);

    $consultation->update(['status' => 'closed']);

    $component->callMountedAction();

    expect(ConsultationOffer::query()
        ->where('consultation_id', $consultation->id)
        ->where('third_party_id', $subcontractor->id)
        ->exists())->toBeFalse();
});

it('hides the response action for consultations that are no longer published', function () {
    authenticateConsultationSubcontractor();
    $consultation = Consultation::factory()->create(['status' => 'closed']);

    livewire(ListConsultations::class)
        ->assertCanSeeTableRecords([$consultation])
        ->assertActionHidden(TestAction::make('respond')->table($consultation));

    expect(ConsultationOffer::query()
        ->where('consultation_id', $consultation->id)
        ->exists())->toBeFalse();
});
