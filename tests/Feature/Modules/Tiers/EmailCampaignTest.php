<?php

use App\Models\Tiers\EmailCampaign;
use App\Models\Tiers\EmailCampaignRecipient;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Enums\Tiers\ThirdPartyType;
use App\Enums\Tiers\EmailCampaignStatus;
use App\Enums\Tiers\EmailCampaignRecipientStatus;
use App\Jobs\Tiers\ProcessEmailCampaignJob;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    \App\Models\Core\Company::create([
        'name' => 'Test Company',
        'legal_name' => 'Test Company',
    ]);
});

it('can generate recipients based on third party type', function () {
    // Create third parties
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);

    // Create contacts with emails
    $contactClient1 = Contact::factory()->create(['third_party_id' => $client->id, 'email' => 'client1@test.com']);
    $contactClient2 = Contact::factory()->create(['third_party_id' => $client->id, 'email' => 'client2@test.com']);
    $contactSupplier = Contact::factory()->create(['third_party_id' => $supplier->id, 'email' => 'supplier@test.com']);
    
    // Create a contact without email
    Contact::factory()->create(['third_party_id' => $client->id, 'email' => null]);

    // Create campaign
    $campaign = EmailCampaign::create([
        'name' => 'Promo',
        'subject' => 'Hello',
        'body' => '<p>Promo</p>',
    ]);

    // Manually trigger the logic that would be in the Filament action
    $types = [ThirdPartyType::CLIENT->value];
    
    $contacts = Contact::with('thirdParty')
        ->whereHas('thirdParty', function($q) use ($types) {
            $q->whereIn('type', $types);
        })
        ->whereNotNull('email')
        ->where('email', '!=', '')
        ->get();

    foreach($contacts as $contact) {
        EmailCampaignRecipient::firstOrCreate([
            'email_campaign_id' => $campaign->id,
            'email' => $contact->email,
        ], [
            'third_party_id' => $contact->third_party_id,
            'contact_id' => $contact->id,
            'status' => EmailCampaignRecipientStatus::PENDING,
        ]);
    }

    // Assert only clients with email were added
    expect($campaign->recipients()->count())->toBe(2);
    $emails = $campaign->recipients->pluck('email')->toArray();
    expect($emails)->toContain('client1@test.com', 'client2@test.com')
        ->not->toContain('supplier@test.com');
});

it('sends emails and updates status', function () {
    Mail::fake();

    $campaign = EmailCampaign::create([
        'name' => 'Promo',
        'subject' => 'Hello',
        'body' => '<p>Promo</p>',
        'status' => EmailCampaignStatus::SCHEDULED,
    ]);

    $recipient = EmailCampaignRecipient::create([
        'email_campaign_id' => $campaign->id,
        'email' => 'test@test.com',
        'status' => EmailCampaignRecipientStatus::PENDING,
    ]);

    $job = new ProcessEmailCampaignJob($campaign);
    $job->handle();

    Mail::assertSent(\App\Mail\Tiers\GenericCampaignEmail::class, function ($mail) {
        return $mail->hasTo('test@test.com') &&
               $mail->campaignSubject === 'Hello';
    });

    $recipient->refresh();
    $campaign->refresh();

    expect($recipient->status)->toBe(EmailCampaignRecipientStatus::SENT)
        ->and($campaign->status)->toBe(EmailCampaignStatus::COMPLETED);
});
