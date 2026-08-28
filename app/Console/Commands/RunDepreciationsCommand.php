<?php

namespace App\Console\Commands;

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Immobilisation\Depreciation;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class RunDepreciationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'immobilisations:run-depreciations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clôture les dotations aux amortissements arrivées à échéance (fin d\'année).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();

        $depreciations = Depreciation::where('is_passed', false)
            ->where('period_date', '<=', $today)
            ->get();

        foreach ($depreciations as $depreciation) {
            $depreciation->update([
                'is_passed' => true,
                'chantier_id' => $depreciation->fixedAsset->chantier_id,
            ]);

            // If VNC is 0, update asset status
            if ($depreciation->remaining_vnc <= 0) {
                $depreciation->fixedAsset->update(['status' => AssetStatus::DEPRECIATED]);
            }
        }

        $this->info("{$depreciations->count()} dotations ont été passées en comptabilité.");
    }
}
