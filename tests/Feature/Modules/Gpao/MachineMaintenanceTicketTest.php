<?php

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Enums\Gpao\MachineStatus;
use App\Models\Gpao\Machine;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Models\User;
use App\Notifications\Gpao\MachineMaintenanceTicketNotification;
use App\Services\Gpao\MachineMaintenanceTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new MachineMaintenanceTicketService;
    $this->machine = Machine::factory()->create([
        'maintenance_interval_hours' => 500,
        'usage_hours' => 0,
    ]);
    $this->user = User::factory()->create();
});

describe('MachineMaintenanceTicketStatus enum', function () {
    test('has correct labels', function () {
        expect(MachineMaintenanceTicketStatus::OPEN->getLabel())->toBe('Ouvert')
            ->and(MachineMaintenanceTicketStatus::IN_PROGRESS->getLabel())->toBe('En cours')
            ->and(MachineMaintenanceTicketStatus::RESOLVED->getLabel())->toBe('Résolu')
            ->and(MachineMaintenanceTicketStatus::CANCELED->getLabel())->toBe('Annulé');
    });

    test('has correct colors', function () {
        expect(MachineMaintenanceTicketStatus::OPEN->getColor())->toBe('warning')
            ->and(MachineMaintenanceTicketStatus::IN_PROGRESS->getColor())->toBe('primary')
            ->and(MachineMaintenanceTicketStatus::RESOLVED->getColor())->toBe('success')
            ->and(MachineMaintenanceTicketStatus::CANCELED->getColor())->toBe('gray');
    });

    test('has correct icons', function () {
        expect(MachineMaintenanceTicketStatus::OPEN->getIcon())->toBe('heroicon-o-exclamation-triangle')
            ->and(MachineMaintenanceTicketStatus::IN_PROGRESS->getIcon())->toBe('heroicon-o-play')
            ->and(MachineMaintenanceTicketStatus::RESOLVED->getIcon())->toBe('heroicon-o-check-circle')
            ->and(MachineMaintenanceTicketStatus::CANCELED->getIcon())->toBe('heroicon-o-x-circle');
    });
});

describe('MachineMaintenanceTicketType enum', function () {
    test('has correct labels', function () {
        expect(MachineMaintenanceTicketType::PREVENTIVE->getLabel())->toBe('Préventif')
            ->and(MachineMaintenanceTicketType::CURATIVE->getLabel())->toBe('Curatif')
            ->and(MachineMaintenanceTicketType::CORRECTIVE->getLabel())->toBe('Correctif');
    });
});

describe('MachineMaintenanceTicketService', function () {
    test('start transitions OPEN to IN_PROGRESS', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::OPEN,
        ]);

        $this->service->start($ticket);

        $ticket->refresh();
        expect($ticket->status)->toBe(MachineMaintenanceTicketStatus::IN_PROGRESS);
    });

    test('start throws on invalid status transition', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::RESOLVED,
        ]);

        $this->service->start($ticket);
    })->throws(LogicException::class, 'Transition de statut non autorisée');

    test('resolve transitions OPEN to RESOLVED with cost and provider', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::OPEN,
        ]);

        $this->service->resolve($ticket, 150.00, 'TechPro', 'Pièce remplacée');

        $ticket->refresh();
        expect($ticket->status)->toBe(MachineMaintenanceTicketStatus::RESOLVED)
            ->and($ticket->resolved_at)->not->toBeNull()
            ->and((float) $ticket->cost_ht)->toBe(150.00)
            ->and($ticket->provider_name)->toBe('TechPro')
            ->and($ticket->notes)->toBe('Pièce remplacée');
    });

    test('resolve transitions IN_PROGRESS to RESOLVED', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        $this->service->resolve($ticket);

        $ticket->refresh();
        expect($ticket->status)->toBe(MachineMaintenanceTicketStatus::RESOLVED);
    });

    test('resolve throws on CANCELED status', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::CANCELED,
        ]);

        $this->service->resolve($ticket);
    })->throws(LogicException::class);

    test('resolve restores machine to OPERATIONAL when no more open tickets', function () {
        $this->machine->update(['status' => MachineStatus::MAINTENANCE]);

        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        $this->service->resolve($ticket);

        $this->machine->refresh();
        expect($this->machine->status)->toBe(MachineStatus::OPERATIONAL);
    });

    test('resolve does not restore machine status if other open tickets remain', function () {
        $this->machine->update(['status' => MachineStatus::MAINTENANCE]);

        $ticket1 = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::OPEN,
        ]);

        $this->service->resolve($ticket1);

        $this->machine->refresh();
        expect($this->machine->status)->toBe(MachineStatus::MAINTENANCE);
    });

    test('cancel transitions OPEN to CANCELED', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::OPEN,
        ]);

        $this->service->cancel($ticket);

        $ticket->refresh();
        expect($ticket->status)->toBe(MachineMaintenanceTicketStatus::CANCELED);
    });

    test('cancel throws on RESOLVED status', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::RESOLVED,
        ]);

        $this->service->cancel($ticket);
    })->throws(LogicException::class);

    test('cancel restores machine to OPERATIONAL when no more open tickets', function () {
        $this->machine->update(['status' => MachineStatus::MAINTENANCE]);

        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::OPEN,
        ]);

        $this->service->cancel($ticket);

        $this->machine->refresh();
        expect($this->machine->status)->toBe(MachineStatus::OPERATIONAL);
    });

    test('notifyAdmins sends notification to admin users', function () {
        $admin = User::factory()->create(['is_admin' => true]);
        $regular = User::factory()->create(['is_admin' => false]);

        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
        ]);

        $this->service->notifyAdmins($ticket);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => MachineMaintenanceTicketNotification::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $regular->id,
        ]);
    });
});

describe('MachineMaintenanceTicket model', function () {
    test('casts status and type to enums', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'status' => MachineMaintenanceTicketStatus::OPEN,
            'type' => MachineMaintenanceTicketType::PREVENTIVE,
        ]);

        expect($ticket->status)->toBeInstanceOf(MachineMaintenanceTicketStatus::class)
            ->and($ticket->type)->toBeInstanceOf(MachineMaintenanceTicketType::class);
    });

    test('machine relationship works', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
        ]);

        expect($ticket->machine->id)->toBe($this->machine->id);
    });

    test('reportedBy relationship works', function () {
        $ticket = MachineMaintenanceTicket::factory()->create([
            'machine_id' => $this->machine->id,
            'reported_by_id' => $this->user->id,
        ]);

        expect($ticket->reportedBy->id)->toBe($this->user->id);
    });
});
