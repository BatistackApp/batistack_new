<?php

namespace App\Providers\Filament\Traits;

use Filament\Panel;
use Guava\FilamentKnowledgeBase\KnowledgeBaseCompanionPlugin;

trait HasKnowledgeBaseCompanion
{
    /**
     * Boot the trait on the panel provider.
     */
    protected function registerKnowledgeBaseCompanion(Panel $panel): Panel
    {
        return $panel->plugin(
            KnowledgeBaseCompanionPlugin::make()
        );
    }
}
