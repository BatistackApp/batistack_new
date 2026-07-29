<?php

namespace App\Services\Core;

use Illuminate\Support\Manager;
use App\Services\Core\Providers\LocalSignatureProvider;
use App\Services\Core\Providers\DocusealProvider;

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
     * @return \App\Contracts\Core\SignatureProviderInterface
     */
    protected function createLocalDriver()
    {
        return new LocalSignatureProvider();
    }

    /**
     * Create an instance of the "docuseal" signature driver.
     *
     * @return \App\Contracts\Core\SignatureProviderInterface
     */
    protected function createDocusealDriver()
    {
        return new DocusealProvider();
    }
}
