<?php

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\TicketSeverity;

it('maps ticket severities to labels', function () {
    expect(TicketSeverity::LOW->getLabel())->toBe('Faible')
        ->and(TicketSeverity::MEDIUM->getLabel())->toBe('Moyenne')
        ->and(TicketSeverity::HIGH->getLabel())->toBe('Élevée')
        ->and(TicketSeverity::CRITICAL->getLabel())->toBe('Critique');
});

it('maps ticket severities to colors', function () {
    expect(TicketSeverity::LOW->getColor())->toBe('success')
        ->and(TicketSeverity::MEDIUM->getColor())->toBe('warning')
        ->and(TicketSeverity::HIGH->getColor())->toBe('danger')
        ->and(TicketSeverity::CRITICAL->getColor())->toBe('gray');
});

it('exposes the raw values of ticket severities', function () {
    expect(TicketSeverity::HIGH->value)->toBe('high')
        ->and(TicketSeverity::CRITICAL->value)->toBe('critical');
});

it('maps ticket statuses to labels', function () {
    expect(AssetMaintenanceTicketStatus::OPEN->getLabel())->toBe('Ouvert')
        ->and(AssetMaintenanceTicketStatus::IN_PROGRESS->getLabel())->toBe('En cours')
        ->and(AssetMaintenanceTicketStatus::RESOLVED->getLabel())->toBe('Résolu')
        ->and(AssetMaintenanceTicketStatus::CANCELED->getLabel())->toBe('Annulé');
});

it('maps ticket statuses to colors', function () {
    expect(AssetMaintenanceTicketStatus::OPEN->getColor())->toBe('warning')
        ->and(AssetMaintenanceTicketStatus::IN_PROGRESS->getColor())->toBe('primary')
        ->and(AssetMaintenanceTicketStatus::RESOLVED->getColor())->toBe('success')
        ->and(AssetMaintenanceTicketStatus::CANCELED->getColor())->toBe('gray');
});

it('maps ticket statuses to icons', function () {
    expect(AssetMaintenanceTicketStatus::OPEN->getIcon())->toBe('heroicon-o-exclamation-triangle')
        ->and(AssetMaintenanceTicketStatus::IN_PROGRESS->getIcon())->toBe('heroicon-o-play')
        ->and(AssetMaintenanceTicketStatus::RESOLVED->getIcon())->toBe('heroicon-o-check-circle')
        ->and(AssetMaintenanceTicketStatus::CANCELED->getIcon())->toBe('heroicon-o-x-circle');
});

it('exposes the raw values of ticket statuses', function () {
    expect(AssetMaintenanceTicketStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(AssetMaintenanceTicketStatus::CANCELED->value)->toBe('canceled');
});
