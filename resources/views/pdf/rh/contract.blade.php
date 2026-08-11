@extends('pdf.layout')

@section('content')
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Contrat de Travail</h1>
        <p class="text-slate-500 italic">Type de contrat : {{ $contract->type->getDescription() }}</p>
    </div>

    <div class="mb-10 leading-relaxed">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4">ENTRE LES SOUSSIGNÉS :</h2>
        <p class="mb-4">
            <strong>L'Employeur :</strong> {{ $company->legal_name }}, situé au {{ $company->address }}, {{ $company->zip_code }} {{ $company->city }}.<br>
            Représenté par son dirigeant en exercice.
        </p>
        <p>
            <strong>Le Salarié :</strong> M/Mme {{ $employee->full_name }}, demeurant au {{ $employee->full_address }}, né(e) le {{ $employee->birth_date->format('d/m/Y') }}.<br>
            Numéro de Sécurité Sociale : <span class="font-mono">{{ $employee->social_security_number }}</span>
        </p>
    </div>

    <div class="space-y-6 text-justify">
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 1 : Engagement et Fonctions</h3>
            <p>Le salarié est engagé à compter du <strong>{{ $contract->start_date->format('d/m/Y') }}</strong> en qualité de <strong>{{ $contract->job_title }}</strong>, sous réserve des résultats de la visite médicale d'embauche.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 2 : Qualification et Classification</h3>
            <p>Le salarié relèvera de la convention collective nationale applicable à l'entreprise (Bâtiment). Son coefficient et sa position seront définis conformément à ladite convention collective.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 3 : Période d'essai</h3>
            <p>Le présent contrat est conclu avec une période d'essai de 2 mois, renouvelable une fois. Durant cette période, chacune des parties pourra rompre le contrat sous réserve de respecter le délai de prévenance légal ou conventionnel.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 4 : Lieu de Travail</h3>
            <p>Le lieu de travail habituel est fixé au siège de l'entreprise. Toutefois, compte tenu de la nature de l'activité du bâtiment, le salarié sera amené à se déplacer sur les différents chantiers de l'entreprise.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 5 : Mobilité et Déplacements</h3>
            <p>Le salarié s'engage à accepter les déplacements nécessaires à l'accomplissement de ses missions (petits et grands déplacements), selon les nécessités de service.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 6 : Durée du travail</h3>
            <p>La durée de travail est fixée à <strong>{{ $contract->weekly_hours }} heures</strong> hebdomadaires. Les horaires pourront être modifiés en fonction des impératifs des chantiers.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 7 : Rémunération</h3>
            <p>En contrepartie de son travail, le salarié percevra une rémunération horaire brute de <strong>{{ number_format($contract->hourly_rate, 2) }} €</strong>, versée mensuellement.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 8 : Primes et Indemnités (Panier, Trajet, Transport)</h3>
            <p>Les primes de panier, de trajet et de transport seront versées conformément au barème de la convention collective Bâtiment de la région applicable.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 9 : Congés Payés</h3>
            <p>Le salarié bénéficiera des congés payés annuels selon les dispositions légales et conventionnelles. Le paiement des indemnités de congés est assuré par la Caisse des Congés Intempéries BTP.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 10 : Frais Professionnels</h3>
            <p>Les frais professionnels engagés par le salarié dans l'exercice de ses fonctions, avec l'accord de la Direction, seront remboursés sur présentation de justificatifs valables.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 11 : Maladie et Accident</h3>
            <p>En cas d'absence pour maladie ou accident, le salarié s'engage à prévenir l'employeur dans les 48 heures et à fournir un certificat médical justifiant de son indisponibilité.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 12 : Prévoyance et Mutuelle</h3>
            <p>Le salarié sera affilié obligatoirement au régime de retraite complémentaire, au régime de prévoyance BTP-Prévoyance (PRO BTP) ainsi qu'à la mutuelle de l'entreprise.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 13 : Obligation de Loyauté et Exclusivité</h3>
            <p>Le salarié s'engage à consacrer l'intégralité de son temps de travail à l'entreprise et s'interdit d'exercer toute autre activité professionnelle non autorisée.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 14 : Clause de Confidentialité</h3>
            <p>Le salarié est tenu au secret professionnel et à une obligation de discrétion absolue sur tout ce qui concerne les activités, les méthodes et les clients de l'entreprise.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 15 : Clause de Non-Concurrence</h3>
            <p>En cas de rupture du contrat, et selon les fonctions exercées, une clause de non-concurrence pourra être appliquée, assortie d'une contrepartie financière.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 16 : Obligations Professionnelles</h3>
            <p>Le salarié s'engage à respecter les directives et instructions données par sa hiérarchie, ainsi qu'à prendre soin des outils, machines et matériaux confiés.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 17 : Utilisation du Matériel de l'Entreprise</h3>
            <p>Le matériel (téléphone, ordinateur, outillage) fourni par l'entreprise reste sa propriété et doit être utilisé exclusivement à des fins professionnelles.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 18 : Sécurité et Port des EPI</h3>
            <p>Le salarié s'engage à respecter scrupuleusement les consignes de sécurité, notamment le port obligatoire des Équipements de Protection Individuelle (casque, chaussures de sécurité).</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 19 : Règlement Intérieur</h3>
            <p>Le salarié déclare avoir pris connaissance du règlement intérieur de l'entreprise et s'engage à s'y conformer sans réserve.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 20 : Véhicule de l'Entreprise</h3>
            <p>Si un véhicule est confié au salarié, celui-ci s'engage à en faire un usage strictement professionnel (sauf autorisation contraire) et à respecter le Code de la route.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 21 : Informatique et Libertés (RGPD)</h3>
            <p>Les données personnelles du salarié sont traitées pour la gestion du personnel. Le salarié dispose d'un droit d'accès, de rectification et de suppression selon la réglementation en vigueur.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 22 : Rupture du Contrat</h3>
            <p>À l'issue de la période d'essai, toute rupture du contrat de travail sera soumise aux délais de préavis prévus par la convention collective et le Code du travail.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 23 : Conventions et Accords Collectifs</h3>
            <p>Les relations entre les parties sont régies par le présent contrat et par la Convention Collective Nationale du Bâtiment applicable.</p>
        </section>

        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 24 : Litiges et Juridiction Compétente</h3>
            <p>Tout litige relatif à l'exécution ou à la rupture du présent contrat relèvera de la compétence exclusive du Conseil de Prud'hommes du lieu de signature ou d'exécution du contrat.</p>
        </section>
    </div>

    <div class="mt-20 flex justify-between">
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Fait à {{ $company->city }}, le {{ now()->format('d/m/Y') }}</p>
            <p class="underline">Signature de l'Employeur</p>
        </div>
        <div class="text-center w-1/3">
            <p class="font-bold mb-10 text-xs uppercase">Mention "Lu et approuvé"</p>
            @if(isset($signature) && $signature->signature_data)
                <div style="font-size: 10px; color: #555; text-align: center; margin-bottom: 5px;">
                    Signé par {{ $employee->full_name }}<br>
                    le {{ $signature->signed_at ? $signature->signed_at->format('d/m/Y à H:i:s') : now()->format('d/m/Y à H:i:s') }}<br>
                    Réf : {{ $signature->token }}
                </div>
                <img src="{{ $signature->signature_data }}" style="max-height: 80px; margin: 0 auto;" alt="Signature">
            @else
                <p class="underline">Signature du Salarié</p>
                <!-- Tag DocuSeal caché pour le placement automatique de la signature -->
                <div style="color: transparent; font-size: 8px; margin-top: 40px;">
                    @{{Signature;role=Signataire;type=signature}}
                </div>
            @endif
        </div>
    </div>
@endsection
