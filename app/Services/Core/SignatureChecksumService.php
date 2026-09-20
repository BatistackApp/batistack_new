<?php

namespace App\Services\Core;

use Illuminate\Database\Eloquent\Model;

class SignatureChecksumService
{
    public function generate(Model $model): string
    {
        $attributes = $this->clean($model->getAttributes());

        if (method_exists($model, 'lines')) {
            $lines = $model->relationLoaded('lines') ? $model->lines : $model->lines()->get();
            $attributes['lines'] = $lines->map(fn (Model $line): array => $this->clean($line->getAttributes()))->values()->all();
        }

        ksort($attributes);

        return hash('sha256', json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function clean(array $attributes): array
    {
        unset($attributes['status'], $attributes['signed_at'], $attributes['created_at'], $attributes['updated_at']);
        ksort($attributes);

        return $attributes;
    }
}
