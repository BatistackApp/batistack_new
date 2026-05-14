<?php

namespace App\Services\RH;

use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Services\Core\DocumentService;
use Illuminate\Support\Str;

class ContractDocumentService extends DocumentService
{
    public function generateContract(Contract $contract): string
    {
        $data = [
            'company' => Company::first(),
            'contract' => $contract,
            'title' => $contract->type->getDescription(),
        ];

        return $this->generate(
            view: 'pdf.rh.contract',
            data: $data,
            filename: 'contract_'.Str::slug($contract->employee->full_name)."_{$contract->created_at->format('Ymd')}.pdf",
            type: 'rh',
        );
    }
}
