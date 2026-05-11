@extends('pdf.layout')

@section('content')
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Généré le : {{ $generated_at }}</p>
    </div>

    <div class="contract-parties">
        <div class="party">
            <h3>L'ENTREPRISE PRINCIPALE</h3>
            <p><strong>{{ $company->name }}</strong></p>
            <p>{{ $company->address }}</p>
            <p>{{ $company->zip_code }} {{ $company->city }}</p>
            <p>SIRET : {{ $company->siret }}</p>
        </div>

        <div class="party" style="margin-top: 20px;">
            <h3>LE SOUS-TRAITANT</h3>
            <p><strong>{{ $tiers->name }}</strong></p>
            @if($tiers->addresses->where('is_primary', true)->first())
                @php $addr = $tiers->addresses->where('is_primary', true)->first(); @endphp
                <p>{{ $addr->address }}</p>
                <p>{{ $addr->zip_code }} {{ $addr->city }}</p>
            @endif
            <p>SIRET : {{ $tiers->siret }}</p>
            <p>Email : {{ $tiers->email }}</p>
        </div>
    </div>

    <div class="contract-body" style="margin-top: 30px;">
        <h2>ARTICLE 1 - OBJET DU CONTRAT</h2>
        <p>Le présent contrat a pour objet de définir les conditions dans lesquelles l'entreprise principale confie au sous-traitant l'exécution de travaux ou prestations de services définis en annexe ou par bon de commande.</p>

        <h2>ARTICLE 2 - OBLIGATIONS DU SOUS-TRAITANT</h2>
        <p>Le sous-traitant s'engage à réaliser les travaux conformément aux règles de l'art et aux normes en vigueur. Il déclare être en possession de toutes les assurances obligatoires (décennale, responsabilité civile professionnelle) et être à jour de ses obligations sociales (vigilance).</p>

        <h2>ARTICLE 3 - PRIX ET MODALITÉS DE PAIEMENT</h2>
        <p>Les prix sont définis sur la base du devis accepté. Le règlement s'effectuera selon les modalités prévues aux conditions générales de l'entreprise principale, sous réserve de la réception des factures et de la validation des travaux.</p>

        <h2>ARTICLE 4 - CONFORMITÉ ET VIGILANCE</h2>
        <p>Conformément au Code du Travail, le sous-traitant doit fournir tous les 6 mois les documents attestant de sa régularité fiscale et sociale. Le défaut de fourniture de ces pièces constitue un motif de résiliation immédiate du présent contrat.</p>
    </div>

    <div class="signatures" style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="float: left; width: 45%;">
            <p>Fait à ............................, le ............................</p>
            <p><strong>Pour l'Entreprise Principale</strong></p>
            <div style="height: 100px; border: 1px solid #ccc; margin-top: 10px;"></div>
        </div>
        <div style="float: right; width: 45%;">
            <p>Fait à ............................, le ............................</p>
            <p><strong>Pour le Sous-Traitant</strong></p>
            <div style="height: 100px; border: 1px solid #ccc; margin-top: 10px;"></div>
        </div>
        <div style="clear: both;"></div>
    </div>
@endsection
