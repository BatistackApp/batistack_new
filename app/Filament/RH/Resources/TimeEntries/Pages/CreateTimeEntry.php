<?php

namespace App\Filament\RH\Resources\TimeEntries\Pages;

use App\Enums\RH\TimeEntryStatus;
use App\Filament\RH\Resources\TimeEntries\TimeEntryResource;
use App\Models\RH\TimeEntry;
use Carbon\Carbon;
use DB;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    /**
     * @throws \Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $weekStart = Carbon::parse($data['week_start']);
            $days = [
                0 => 'monday',
                1 => 'tuesday',
                2 => 'wednesday',
                3 => 'thursday',
                4 => 'friday',
                5 => 'saturday',
                6 => 'sunday',
            ];

            $lastCreatedRecord = null;

            foreach ($days as $index => $day) {
                $workHours = (float) ($data["{$day}_hours"] ?? 0);
                $travelHours = (float) ($data["{$day}_travel"] ?? 0);

                // On ne crée un enregistrement que s'il y a du temps saisi (travail ou trajet)
                if ($workHours > 0 || $travelHours > 0) {
                    $lastCreatedRecord = TimeEntry::create([
                        'employee_id' => $data['employee_id'],
                        'job_site_id' => $data['job_site_id'],
                        'date' => $weekStart->copy()->addDays($index),
                        'hours' => $workHours,
                        'travel_hours' => $travelHours,
                        'type' => $data['type'],
                        'description' => $data['description'] ?? null,
                        'status' => TimeEntryStatus::DRAFT, // Par défaut en brouillon
                    ]);
                }
            }

            // Filament a besoin qu'on lui retourne un modèle pour finaliser la page.
            // On retourne le dernier créé (ou on lève une exception si aucun n'a été saisi).
            if (! $lastCreatedRecord) {
                throw new \Exception('Veuillez saisir au moins une heure de travail ou de trajet.');
            }

            return $lastCreatedRecord;
        });
    }

    /**
     * Redirection après création vers la liste.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
