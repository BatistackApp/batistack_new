<?php

namespace App\Services\Immobilisation;

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\TicketSeverity;
use App\Enums\RH\EquipementStatus;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\User;
use App\Notifications\Immobilisation\AssetMaintenanceTicketNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AssetMaintenanceTicketService
{
    public function resolveByCode(string $code): ?Model
    {
        if (blank($code)) {
            return null;
        }

        $fixedAsset = FixedAsset::query()
            ->where(fn ($q) => $q->where('qr_token', $code)->orWhere('serial_number', $code))
            ->first();

        if ($fixedAsset) {
            return $fixedAsset;
        }

        return Equipement::query()
            ->where(fn ($q) => $q
                ->where('qr_token', $code)
                ->orWhere('serial_number', $code)
                ->orWhere('barcode', $code))
            ->first();
    }

    /**
     * @param  array{chantier_id?: int|null, description?: string|null, severity?: string}  $data
     */
    public function create(Model $asset, Employee $reporter, array $data): AssetMaintenanceTicket
    {
        if (! $asset instanceof FixedAsset && ! $asset instanceof Equipement) {
            throw new \InvalidArgumentException('L\'actif doit être une immobilisation ou un équipement RH.');
        }

        return DB::transaction(function () use ($asset, $reporter, $data) {
            $ticket = AssetMaintenanceTicket::create([
                'asset_type' => $asset::class,
                'asset_id' => $asset->getKey(),
                'chantier_id' => $data['chantier_id'] ?? null,
                'reported_by_id' => $reporter->getKey(),
                'description' => $data['description'] ?? null,
                'severity' => $data['severity'] ?? TicketSeverity::MEDIUM,
                'previous_asset_status' => $this->resolvePreviousStatus($asset),
                'status' => AssetMaintenanceTicketStatus::OPEN,
            ]);

            $this->applyMaintenanceStatus($asset);

            return $ticket;
        });
    }

    public function start(AssetMaintenanceTicket $ticket): void
    {
        $this->assertStatus($ticket, [AssetMaintenanceTicketStatus::OPEN]);

        $ticket->update(['status' => AssetMaintenanceTicketStatus::IN_PROGRESS]);
    }

    public function resolve(AssetMaintenanceTicket $ticket, ?float $costHt = null, ?string $provider = null): void
    {
        $this->assertStatus($ticket, [
            AssetMaintenanceTicketStatus::OPEN,
            AssetMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        DB::transaction(function () use ($ticket, $costHt, $provider) {
            $ticket->update([
                'status' => AssetMaintenanceTicketStatus::RESOLVED,
                'resolved_at' => now(),
                'cost_ht' => $costHt,
                'provider_name' => $provider,
            ]);

            $this->convertToMaintenance($ticket);
            $this->restoreStatus($ticket);
        });
    }

    public function cancel(AssetMaintenanceTicket $ticket): void
    {
        $this->assertStatus($ticket, [
            AssetMaintenanceTicketStatus::OPEN,
            AssetMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        DB::transaction(function () use ($ticket) {
            $ticket->update(['status' => AssetMaintenanceTicketStatus::CANCELED]);

            $this->restoreStatus($ticket);
        });
    }

    public function notifyDepot(AssetMaintenanceTicket $ticket): void
    {
        $users = User::where('is_admin', true)->get();

        Notification::send($users, new AssetMaintenanceTicketNotification($ticket));
    }

    protected function applyMaintenanceStatus(Model $asset): void
    {
        if ($asset instanceof FixedAsset) {
            $asset->update(['status' => AssetStatus::IN_MAINTENANCE]);
        } elseif ($asset instanceof Equipement) {
            $asset->update(['status' => EquipementStatus::MAINTENANCE]);
        }
    }

    protected function resolvePreviousStatus(Model $asset): ?string
    {
        if ($asset instanceof FixedAsset) {
            return $asset->status?->value;
        }

        if ($asset instanceof Equipement) {
            return $asset->status?->value;
        }

        return null;
    }

    protected function restoreStatus(AssetMaintenanceTicket $ticket): void
    {
        $asset = $ticket->asset;

        if (! $asset) {
            return;
        }

        $hasOpenTicket = AssetMaintenanceTicket::query()
            ->where('asset_type', $asset::class)
            ->where('asset_id', $asset->getKey())
            ->whereIn('status', [
                AssetMaintenanceTicketStatus::OPEN,
                AssetMaintenanceTicketStatus::IN_PROGRESS,
            ])
            ->exists();

        if ($hasOpenTicket) {
            return;
        }

        $previous = $ticket->previous_asset_status;

        if ($asset instanceof FixedAsset) {
            $asset->update([
                'status' => $previous ? AssetStatus::tryFrom($previous) ?? AssetStatus::ACTIVE : AssetStatus::ACTIVE,
            ]);
        } elseif ($asset instanceof Equipement) {
            $fallback = $asset->employee_id ? EquipementStatus::IN_USE : EquipementStatus::AVAILABLE;
            $asset->update([
                'status' => $previous ? EquipementStatus::tryFrom($previous) ?? $fallback : $fallback,
            ]);
        }
    }

    protected function convertToMaintenance(AssetMaintenanceTicket $ticket): void
    {
        $asset = $ticket->asset;

        if (! $asset instanceof FixedAsset) {
            return;
        }

        $asset->maintenances()->create([
            'chantier_id' => $ticket->chantier_id,
            'maintenance_date' => now()->toDateString(),
            'type' => 'curative',
            'description' => $ticket->description,
            'cost_ht' => $ticket->cost_ht,
            'provider_name' => $ticket->provider_name,
        ]);
    }

    protected function assertStatus(AssetMaintenanceTicket $ticket, array $allowed): void
    {
        if (! in_array($ticket->status, $allowed, true)) {
            throw new \LogicException('Transition de statut non autorisée depuis « '.$ticket->status->getLabel().' ».');
        }
    }
}
