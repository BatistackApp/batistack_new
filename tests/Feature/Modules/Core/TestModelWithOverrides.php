<?php

namespace Tests\Feature\Modules\Core;

use App\Models\Core\Signature;
use App\Traits\Core\HasSignature;
use Illuminate\Database\Eloquent\Model;

class TestModelWithOverrides extends Model
{
    use HasSignature;

    protected $table = 'customers';

    protected $fillable = ['name'];

    public function getSignatureUrl(Signature $signature): ?string
    {
        return 'https://example.com/sign/'.$signature->token;
    }

    public function getSignaturePath(): ?string
    {
        return '/tmp/test.pdf';
    }

    public function getSignatoryDisplayName(): ?string
    {
        return 'Test User';
    }

    public function onPostSignature(Signature $signature): void
    {
        // Post-signature hook
    }

    protected function getSignatureMediaCollection(): ?string
    {
        return 'test_documents';
    }
}
