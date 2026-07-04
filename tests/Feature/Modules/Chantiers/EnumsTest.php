<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Chantiers\DoeDocumentCategory;

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
