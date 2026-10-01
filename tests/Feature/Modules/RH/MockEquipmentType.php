<?php

namespace Tests\Feature\Modules\RH;

enum MockEquipmentType: string
{
    case EPI = 'epi';
    case TOOL = 'tool';

    public function getLabel(): string
    {
        return match ($this) {
            self::EPI => 'Équipement de Protection Individuelle',
            self::TOOL => 'Outil',
        };
    }
}
