<?php

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Services\RH\TimeEntryService;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

describe('Workflow de Pointage', function () {

    test('un pointage peut passer de brouillon à soumis', function () {
        $entry = TimeEntry::factory()->create(['status' => TimeEntryStatus::DRAFT]);
        $service = new TimeEntryService;

        $service->submit($entry);

        expect($entry->refresh()->status)->toBe(TimeEntryStatus::SUBMITTED);
    });

    test('un conducteur de travaux peut approuver un pointage', function () {
        $entry = TimeEntry::factory()->create(['status' => TimeEntryStatus::SUBMITTED]);
        $service = new TimeEntryService;

        $service->approve($entry);

        expect($entry->refresh()->status)->toBe(TimeEntryStatus::APPROVED)
            ->and($entry->approved_at)->not->toBeNull()
            ->and($entry->approved_by_id)->toBe($this->admin->id);
    });

    test('on ne peut pas modifier un pointage déjà approuvé', function () {
        $entry = TimeEntry::factory()->create(['status' => TimeEntryStatus::APPROVED]);
        $service = new TimeEntryService;

        $this->expectException(Exception::class);
        $service->submit($entry);
    });

    test('un pointage soumis peut être refusé', function () {
        $entry = TimeEntry::factory()->create(['status' => TimeEntryStatus::SUBMITTED]);
        $service = new TimeEntryService;
        $reason = 'Motif de refus test';

        $service->refuse($entry, $reason);

        expect($entry->refresh()->status)->toBe(TimeEntryStatus::DRAFT)
            ->and($entry->refusal_reason)->toBe($reason);
    });
});
