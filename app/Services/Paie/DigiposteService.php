<?php

namespace App\Services\Paie;

use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigiposteService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $clientId;

    protected string $clientSecret;

    protected string $partnerId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.digiposte.base_url', ''), '/');
        $this->apiKey = config('services.digiposte.api_key', '');
        $this->clientId = config('services.digiposte.client_id', '');
        $this->clientSecret = config('services.digiposte.client_secret', '');
        $this->partnerId = config('services.digiposte.partner_id', '');
    }

    protected function isConfigured(): bool
    {
        return ! empty($this->baseUrl);
    }

    public function authenticate(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::connectTimeout(3)->timeout(10)->withHeaders([
            'X-Okapi-Key' => $this->apiKey,
            'Authorization' => 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ])->asForm()->post("{$this->baseUrl}/token", [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        if ($response->serverError() || $response->status() === 429) {
            $response->throw();
        }

        Log::error('Digiposte authentication failed', ['response' => $response->body()]);

        return null;
    }

    public function createOrGetSafe(Employee $employee): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if ($employee->digiposte_id) {
            return true;
        }

        $token = $this->authenticate();
        if (! $token) {
            return false;
        }

        // Digiposte B2B API to create an active membership and safe
        $response = Http::connectTimeout(3)->timeout(10)->withHeaders([
            'X-Okapi-Key' => $this->apiKey,
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/partner/{$this->partnerId}/memberships", [
            'partner_user_id' => $employee->registration_number,
            'email' => $employee->email,
            'firstname' => $employee->first_name,
            'lastname' => $employee->last_name,
        ]);

        if ($response->successful()) {
            // we use the registration_number as the Digiposte link ID
            $employee->update(['digiposte_id' => $employee->registration_number]);

            return true;
        }

        // If it already exists (409 Conflict)
        if ($response->status() === 409) {
            $employee->update(['digiposte_id' => $employee->registration_number]);

            return true;
        }

        if ($response->serverError() || $response->status() === 429) {
            $response->throw();
        }

        Log::error('Digiposte safe creation failed', ['response' => $response->body()]);

        return false;
    }

    public function depositPayslip(Payslip $payslip): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $employee = $payslip->employee;

        if ($employee->refuses_electronic_payslip) {
            Log::info('Digiposte deposit prevented: Employee refused electronic payslip', ['employee_id' => $employee->id]);
            $payslip->update(['digiposte_status' => 'failed']);

            return false;
        }

        if (! $employee->digiposte_id) {
            if (! $this->createOrGetSafe($employee)) {
                $payslip->update(['digiposte_status' => 'failed']);

                return false;
            }
        }

        $token = $this->authenticate();
        if (! $token) {
            $payslip->update(['digiposte_status' => 'failed']);

            return false;
        }

        if (! $payslip->pdf_path) {
            Log::error('Payslip PDF path empty', ['payslip_id' => $payslip->id]);
            $payslip->update(['digiposte_status' => 'failed']);

            return false;
        }

        $disk = Storage::disk('public');

        if (! Str::startsWith($payslip->pdf_path, 'documents/payslips/') || ! $disk->exists($payslip->pdf_path)) {
            Log::error('Payslip PDF not found or path invalid for Digiposte deposit', ['payslip_id' => $payslip->id]);
            $payslip->update(['digiposte_status' => 'failed']);

            return false;
        }

        $response = Http::connectTimeout(3)->timeout(10)->withHeaders([
            'X-Okapi-Key' => $this->apiKey,
            'Authorization' => "Bearer {$token}",
        ])
            ->attach('file', $disk->get($payslip->pdf_path), 'bulletin.pdf')
            ->post("{$this->baseUrl}/partner/{$this->partnerId}/memberships/{$employee->digiposte_id}/documents/certified", [
                'title' => 'Bulletin de paie '.$payslip->period,
                'type' => 'BULL_PAIE',
                'size' => $disk->size($payslip->pdf_path),
                'algo' => 'SHA256',
                'hash' => hash('sha256', $disk->get($payslip->pdf_path)),
            ]);

        if ($response->successful()) {
            $payslip->update([
                'digiposte_status' => 'deposited',
            ]);

            return true;
        }

        if ($response->serverError() || $response->status() === 429) {
            $response->throw();
        }

        Log::error('Digiposte deposit failed', ['response' => $response->body()]);
        $payslip->update(['digiposte_status' => 'failed']);

        return false;
    }
}
