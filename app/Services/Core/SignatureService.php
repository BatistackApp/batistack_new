<?php

namespace App\Services\Core;

use App\Contracts\Core\SignatureProviderInterface;
use App\Services\Core\Providers\DocusealProvider;
use App\Services\Core\Providers\LocalSignatureProvider;
use Illuminate\Support\Manager;

class SignatureService extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('signature.default', 'local');
    }

    /**
     * Create an instance of the "local" signature driver.
     *
     * @return SignatureProviderInterface
     */
    protected function createLocalDriver()
    {
        return new LocalSignatureProvider;
    }

    /**
     * Create an instance of the "docuseal" signature driver.
     *
     * @return SignatureProviderInterface
     */
    protected function createDocusealDriver()
    {
        return new DocusealProvider;
    }
}
