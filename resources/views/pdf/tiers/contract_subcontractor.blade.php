@extends('pdf.layout')

@section('content')
    <div class="mt-5">
        <div class="header text-center text">
            <h1 class="font-bold text-2xl">{{ $title }}</h1>
            <p class="text-lg">Conditions Générales d'accès à sous-traitance de Niveau 2</p>
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
            <h2 class="font-bold">ARTICLE 1 - OBJET DU CONTRAT – PIÈCES CONTRACTUELLES</h2>
            <p class="mb-2">
                Les travaux faisant l'objet du présent contrat sont définis aux conditions particulières.<br />
                Les travaux sous-traités seront exécutés conformément aux conditions des pièces contractuelles définies et numérotées aux conditions particulières.<br />
                En cas de contradiction entre deux ou plusieurs documents particuliers ou entre deux ou plusieurs documents généraux du présent contrat, les indications du document portant le numéro le moins élevé dans l'énumération priment sur les autres.<br>
                En cas de contradiction entre un document général et un document particulier, ce dernier prévaut.<br />
                Il est expressément stipulé que les conditions générales habituellement utilisées par l’entrepreneur principal ou le soustraitant, ou tous autres documents similaires, ne sont pas applicables au présent contrat.<br />
                Dans le cas de signature du contrat de sous-traitance avant conclusion du marché principal, l'entrepreneur principal s'engage pour l'exécution des travaux objet du présent contrat à ne présenter à l'acceptation du maître de l'ouvrage que le seul entrepreneur désigné comme sous-traitant aux conditions particulières.<br>
                En ce cas, le présent contrat est signé sous la condition suspensive expresse que le marché principal comportant le nom et les conditions de paiement du sous-traitant soit lui-même attribué à l'entrepreneur principal par le maître de l'ouvrage.<br><br>
                Dans le cadre du présent contrat, tout délai exprimé en jours s’entend en jours calendaires, à moins qu’il n’en soit disposé autrement dans les conditions particulières.<br><br>
                Les transmissions prévues dans le présent contrat sont faites :<br>
                - soit par lettre recommandée avec demande d'avis de réception postal (LRAR) ;<br>
                - soit par lettre recommandée électronique (LRE) ;<br>
                - soit par remise contre récépissé ;<br>
                - soit par tout autre moyen faisant preuve tel que précisé aux conditions particulières.<br>
            </p>

            <h2 class="font-bold">APPLICATION DES DISPOSITIONS LÉGALES ET CONTRACTUELLES</h2>
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
                <div style="height: 100px; border: 1px solid #ccc; margin-top: 10px; position: relative;">
                    <!-- Tag DocuSeal caché pour le placement automatique de la signature -->
                    <div style="color: transparent; font-size: 8px; position: absolute; bottom: 5px; left: 5px;">
                        @{{Signature;role=Signataire;type=signature}}
                    </div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
@endsection
