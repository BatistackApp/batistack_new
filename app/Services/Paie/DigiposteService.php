<?php

namespace App\Services\Paie;

use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiposteService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $clientId;
    protected string $clientSecret;
    protected string $partnerId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.digiposte.base_url', 'https://api.sandbox.digiposte.fr/digiposte/v3'), '/');
        $this->apiKey = config('services.digiposte.api_key', '');
        $this->clientId = config('services.digiposte.client_id', '');
        $this->clientSecret = config('services.digiposte.client_secret', '');
        $this->partnerId = config('services.digiposte.partner_id', '');
    }

    public function authenticate(): ?string
    {
        $response = Http::withHeaders([
            'X-Okapi-Key' => $this->apiKey,
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ])->asForm()->post("{$this->baseUrl}/token", [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('Digiposte authentication failed', ['response' => $response->body()]);
        return null;
    }

    public function createOrGetSafe(Employee $employee): bool
    {
        if ($employee->digiposte_id) {
            return true;
        }

        $token = $this->authenticate();
        if (!$token) return false;

        // Digiposte B2B API to create an active membership and safe
        $response = Http::withHeaders([
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

        Log::error('Digiposte safe creation failed', ['response' => $response->body()]);
        return false;
    }

    public function depositPayslip(Payslip $payslip): bool
    {
        $employee = $payslip->employee;
        if (!$employee->digiposte_id) {
            if (!$this->createOrGetSafe($employee)) {
                $payslip->update(['digiposte_status' => 'failed']);
                return false;
            }
        }

        $token = $this->authenticate();
        if (!$token) {
            $payslip->update(['digiposte_status' => 'failed']);
            return false;
        }

        if (!$payslip->pdf_path || !file_exists(storage_path('app/public/' . $payslip->pdf_path))) {
            Log::error('Payslip PDF not found for Digiposte deposit', ['payslip_id' => $payslip->id]);
            $payslip->update(['digiposte_status' => 'failed']);
            return false;
        }

        $response = Http::withHeaders([
            'X-Okapi-Key' => $this->apiKey,
            'Authorization' => "Bearer {$token}",
        ])
            ->attach('file', file_get_contents(storage_path('app/public/' . $payslip->pdf_path)), 'bulletin.pdf')
            ->post("{$this->baseUrl}/partner/{$this->partnerId}/memberships/{$employee->digiposte_id}/documents/certified", [
                'title' => "Bulletin de paie " . $payslip->period,
                'type' => 'BULL_PAIE',
                'size' => filesize(storage_path('app/public/' . $payslip->pdf_path)),
                'algo' => 'SHA256',
                'hash' => hash_file('sha256', storage_path('app/public/' . $payslip->pdf_path)),
            ]);

        if ($response->successful()) {
            $payslip->update([
                'digiposte_status' => 'deposited'
            ]);
            return true;
        }

        Log::error('Digiposte deposit failed', ['response' => $response->body()]);
        $payslip->update(['digiposte_status' => 'failed']);
        return false;
    }
}
