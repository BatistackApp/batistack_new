<?php

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Chantiers\DoeDocumentCategory;
use App\Enums\Chantiers\ReserveSeverity;
use ToneGabes\Filament\Icons\Enums\Phosphor;

it('verifie les valeurs de lenum ChantierStatus', function () {
    expect(ChantierStatus::STUDY->value)->toBe('study')
        ->and(ChantierStatus::PLANNED->value)->toBe('planned')
        ->and(ChantierStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(ChantierStatus::AWAITING_RECEPTION->value)->toBe('waiting')
        ->and(ChantierStatus::FINISHED->value)->toBe('finished')
        ->and(ChantierStatus::ARCHIVED->value)->toBe('archived')
        ->and(ChantierStatus::SUSPENDED->value)->toBe('suspended');

    expect(ChantierStatus::STUDY->getLabel())->toBe('En Étude')
        ->and(ChantierStatus::PLANNED->getLabel())->toBe('Programmé')
        ->and(ChantierStatus::IN_PROGRESS->getLabel())->toBe('En Cours')
        ->and(ChantierStatus::FINISHED->getLabel())->toBe('Terminé');

    expect(ChantierStatus::STUDY->getColor())->toBe('gray')
        ->and(ChantierStatus::FINISHED->getColor())->toBe('success')
        ->and(ChantierStatus::SUSPENDED->getColor())->toBe('danger');
});

it('verifie les valeurs de lenum DoeDocumentCategory', function () {
    expect(DoeDocumentCategory::PLAN->value)->toBe('plan')
        ->and(DoeDocumentCategory::NOTICE->value)->toBe('notice')
        ->and(DoeDocumentCategory::FICHE_TECHNIQUE->value)->toBe('fiche_technique')
        ->and(DoeDocumentCategory::CONFORMITE->value)->toBe('conformite')
        ->and(DoeDocumentCategory::AUTRE->value)->toBe('autre');

    expect(DoeDocumentCategory::PLAN->getLabel())->toBe('Plan')
        ->and(DoeDocumentCategory::NOTICE->getLabel())->toBe('Notice')
        ->and(DoeDocumentCategory::FICHE_TECHNIQUE->getLabel())->toBe('Fiche Technique')
        ->and(DoeDocumentCategory::CONFORMITE->getLabel())->toBe('Conformité')
        ->and(DoeDocumentCategory::AUTRE->getLabel())->toBe('Autre');
});

it('verifie les valeurs de lenum ChantierReserveStatus', function () {
    expect(ChantierReserveStatus::OPEN->value)->toBe('open')
        ->and(ChantierReserveStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(ChantierReserveStatus::RESOLVED->value)->toBe('resolved')
        ->and(ChantierReserveStatus::LIFTED->value)->toBe('lifted');

    expect(ChantierReserveStatus::OPEN->getLabel())->toBe('Ouverte')
        ->and(ChantierReserveStatus::IN_PROGRESS->getLabel())->toBe('En cours')
        ->and(ChantierReserveStatus::RESOLVED->getLabel())->toBe('Résolue')
        ->and(ChantierReserveStatus::LIFTED->getLabel())->toBe('Levée');

    expect(ChantierReserveStatus::OPEN->getColor())->toBe('danger')
        ->and(ChantierReserveStatus::IN_PROGRESS->getColor())->toBe('warning')
        ->and(ChantierReserveStatus::RESOLVED->getColor())->toBe('info')
        ->and(ChantierReserveStatus::LIFTED->getColor())->toBe('success');

    expect(ChantierReserveStatus::OPEN->getIcon())->toBe(Phosphor::WarningCircle)
        ->and(ChantierReserveStatus::IN_PROGRESS->getIcon())->toBe(Phosphor::HardHat)
        ->and(ChantierReserveStatus::RESOLVED->getIcon())->toBe(Phosphor::CheckCircle)
        ->and(ChantierReserveStatus::LIFTED->getIcon())->toBe(Phosphor::Stamp);
});

it('verifie les valeurs de lenum ReserveSeverity', function () {
    expect(ReserveSeverity::INFO->value)->toBe('info')
        ->and(ReserveSeverity::MINOR->value)->toBe('minor')
        ->and(ReserveSeverity::MAJOR->value)->toBe('major')
        ->and(ReserveSeverity::CRITICAL->value)->toBe('critical');

    expect(ReserveSeverity::INFO->getLabel())->toBe('Informatif')
        ->and(ReserveSeverity::MINOR->getLabel())->toBe('Mineur')
        ->and(ReserveSeverity::MAJOR->getLabel())->toBe('Majeur')
        ->and(ReserveSeverity::CRITICAL->getLabel())->toBe('Critique');

    expect(ReserveSeverity::INFO->getColor())->toBe('gray')
        ->and(ReserveSeverity::MINOR->getColor())->toBe('info')
        ->and(ReserveSeverity::MAJOR->getColor())->toBe('warning')
        ->and(ReserveSeverity::CRITICAL->getColor())->toBe('danger');
});
