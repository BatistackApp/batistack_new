@extends('pdf.layout')

@section('content')
    {{-- En-tête --}}
    <div class="mb-8 text-center border-b-2 border-blue-batistack pb-4">
        <h1 class="text-3xl font-bold text-blue-batistack uppercase">Contrat de Travail</h1>
        <p class="text-slate-500 italic">Type de contrat : {{ $contract->type->getDescription() }}</p>
        @if($contract->category)
            <p class="text-slate-500 italic text-sm">Catégorie : {{ $contract->category->getLabel() }}</p>
        @endif
    </div>

    {{-- Parties contractantes --}}
    <div class="mb-10 leading-relaxed">
        <h2 class="text-lg font-bold border-l-4 border-blue-batistack pl-2 mb-4">ENTRE LES SOUSSIGNÉS</h2>

        <div class="bg-slate-50 p-4 rounded mb-4">
            <p class="mb-2">
                <strong>L'Employeur :</strong> {{ $company->legal_name }}<br>
                {{ $company->address }}, {{ $company->zip_code }} {{ $company->city }}<br>
                Représenté par son dirigeant en exercice, agissant en sa qualité de représentant légal de la société.
            </p>
        </div>

        <div class="bg-slate-50 p-4 rounded">
            <p class="mb-2">
                <strong>Le Salarié :</strong><br>
                Nom et prénom : <strong>{{ $employee->full_name }}</strong><br>
                Né(e) le : {{ $employee->birth_date->format('d/m/Y') }}<br>
                Nationalité : Française<br>
                Domicilié(e) : {{ $employee->address }}, {{ $employee->postal_code }} {{ $employee->city }}<br>
                @if($employee->social_security_number)
                    N° de Sécurité Sociale : <span class="font-mono">{{ $employee->social_security_number }}</span><br>
                @endif
                @if($employee->email)
                    Email : {{ $employee->email }}<br>
                @endif
                @if($employee->phone)
                    Téléphone : {{ $employee->phone }}
                @endif
            </p>
        </div>

        <p class="mt-4 text-sm text-slate-600 italic">
            Ci-après dénommés « l'Employeur » et « le Salarié », ensemble dénommés « les Parties ».
        </p>
    </div>

    {{-- Préambule --}}
    <div class="mb-8 p-4 border border-slate-200 rounded bg-slate-50 text-sm leading-relaxed">
        <p class="font-bold mb-2">PRÉAMBULE</p>
        <p>
            Les Parties conviennent de ce qui suit : le présent contrat de travail est conclu conformément aux dispositions du Code du travail,
            de la Convention Collective Nationale du Bâtiment et des Travaux Publics (IDCC 1596) et de ses avenants, ainsi que de l'ensemble
            des textes législatifs et réglementaires en vigueur. Le salarié déclare être en mesure de remplir les fonctions qui lui sont confiées
            et justifier des qualifications requises.
        </p>
    </div>

    {{-- Articles --}}
    <div class="space-y-6 text-justify text-sm leading-relaxed">

        {{-- Article 1 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 1 : Objet du contrat – Engagement et Fonctions</h3>
            <p>
                Le salarié est engagé à compter du <strong>{{ $contract->start_date->format('d/m/Y') }}</strong> en qualité de
                <strong>{{ $contract->job_title }}</strong>, sous réserve des résultats de la visite médicale d'embauche
                ou de la visite d'Information et de Prévention (VIP) le cas échéant.
            </p>
            <p class="mt-2">
                Le salarié exercera les fonctions correspondant à son intitulé de poste, incluant notamment :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>L'exécution des travaux de bâtiment conformément aux directives de la hiérarchie ;</li>
                <li>Le respect des normes de sécurité et des consignes de chantier ;</li>
                <li>La participation aux travaux de préparation, d'exécution et de finition ;</li>
                <li>Le soin apporté aux outils, machines et matériaux confiés ;</li>
                <li>Toute autre tâche connexe relevant de sa qualification et de sa classification.</li>
            </ul>
            <p class="mt-2">
                L'Employeur se réserve la possibilité de modifier temporairement les tâches du salarié en fonction
                des besoins du service, dans le respect de sa qualification professionnelle.
            </p>
        </section>

        {{-- Article 2 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 2 : Classification et Convention Collective</h3>
            <p>
                Le salarié est classé dans la catégorie professionnelle de
                <strong>{{ $contract->category ? $contract->category->getLabel() : 'non déterminée' }}</strong>
                et relèvera de la <strong>Convention Collective Nationale du Bâtiment et des Travaux Publics (IDCC 1596)</strong>.
            </p>
            <p class="mt-2">
                Sa position professionnelle, son coefficient hiérarchique et son indice seront déterminés conformément
                à la grille salariale de ladite convention collective, en fonction de sa qualification, de son expérience
                et des fonctions effectivement exercées.
            </p>
            <p class="mt-2">
                Le cas échéant, les dispositions de l'accord de branche en faveur de l'emploi et de l'insertion
                des jeunes en bâtiment seront applicables.
            </p>
        </section>

        {{-- Article 3 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 3 : Type de contrat et Durée</h3>
            <p>
                Le présent contrat est conclu sous la forme d'un <strong>{{ $contract->type->getDescription() }}</strong>.
            </p>
            @if($contract->type === \App\Enums\RH\ContractType::CDD && $contract->end_date)
                <p class="mt-2">
                    Le contrat prendra fin le <strong>{{ $contract->end_date->format('d/m/Y') }}</strong>,
                    sauf renouvellement ou résiliation anticipée dans les conditions prévues par la loi et la convention collective.
                    La durée totale du contrat, renouvellements inclus, ne pourra excéder la durée maximale autorisée
                    pour les CDD dans le secteur du bâtiment (18 mois maximum sauf exception légale).
                </p>
            @elseif($contract->type === \App\Enums\RH\ContractType::CDI)
                <p class="mt-2">
                    Le contrat est conclu pour une durée indéterminée. Il prendra effet à compter de la date d'entrée
                    en fonction et ce, jusqu'à ce que l'une des Parties prononce sa rupture dans les conditions
                    prévues par le Code du travail et la convention collective applicable.
                </p>
            @elseif($contract->type === \App\Enums\RH\ContractType::INTERIM)
                <p class="mt-2">
                    Le contrat est conclu pour la durée de la mission déterminée par l'entreprise de travail temporaire,
                    conformément au bulletin de mission. Les conditions d'emploi sont celles du lieu d'exécution de la mission.
                </p>
            @elseif($contract->type === \App\Enums\RH\ContractType::APPRENTICE)
                <p class="mt-2">
                    Le contrat est conclu dans le cadre de l'apprentissage, pour la durée déterminée par le programme
                    de formation et l'organisme de formation concerné. Le salarié suivra un enseignement général, théorique
                    et pratique en alternance, dans les conditions prévues par le Code du travail (L. 6211 et suivants).
                </p>
            @endif
        </section>

        {{-- Article 4 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 4 : Période d'essai</h3>
            @if($contract->trial_end_date)
                <p>
                    Le présent contrat est assorti d'une période d'essai courant du <strong>{{ $contract->start_date->format('d/m/Y') }}</strong>
                    au <strong>{{ $contract->trial_end_date->format('d/m/Y') }}</strong>, soit une durée de
                    <strong>{{ $contract->start_date->diffInDays($contract->trial_end_date) }} jour(s)</strong>.
                </p>
                <p class="mt-2">
                    Durant cette période, chacune des Parties pourra rompre le contrat sans motif particulier et sans
                    indemnité, sous réserve de respecter le délai de prévenance suivant :
                </p>
                <ul class="list-disc ml-6 mt-1 space-y-1">
                    <li>Si la demande de rupture émane du salarié : 48 heures si la présence est inférieure à 8 jours, 2 semaines si la présence est comprise entre 8 jours et 1 mois, 1 mois si la présence est supérieure à 1 mois ;</li>
                    <li>Si la demande de rupture émane de l'Employeur : 24 heures si la présence est inférieure à 8 jours, 48 heures si la présence est comprise entre 8 jours et 1 mois, 2 semaines si la présence est supérieure à 1 mois, 1 mois si la présence est supérieure à 3 mois.</li>
                </ul>
                <p class="mt-2">
                    La période d'essai pourra être renouvelée une fois, pour une durée égale à la durée initiale, uniquement
                    si une clause expresse du contrat l'autorise et si les conditions légales de renouvellement sont réunies
                    (notification au salarié avant l'expiration de la première période, motif justifiant le renouvellement).
                </p>
            @else
                <p>Le contrat n'est assorti d'aucune période d'essai.</p>
            @endif
        </section>

        {{-- Article 5 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 5 : Lieu de travail</h3>
            <p>
                Le lieu de travail habituel est fixé au siège de l'entreprise ou sur les chantiers situés dans le ressort
                géographique convenu entre les Parties. Compte tenu de la nature de l'activité du bâtiment, le salarié sera
                amené à se déplacer sur les différents chantiers de l'entreprise, situés dans la région et, le cas échéant,
                en dehors de celle-ci.
            </p>
            <p class="mt-2">
                En cas de mutation temporaire ou de déplacement d'une durée supérieure à plusieurs jours, le salarié en sera
                informé dans les meilleurs délais. Tout changement définitif du lieu de travail fera l'objet d'une information
                préalable et d'une consultation du Comité Social et Économique (CSE) le cas échéant.
            </p>
        </section>

        {{-- Article 6 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 6 : Mobilité et Déplacements</h3>
            <p>
                Le salarié s'engage à accepter les déplacements nécessaires à l'accomplissement de ses missions, tant en
                petits déplacements quotidiens qu'en grands déplacements ponctuels, selon les impératifs de l'activité
                de l'entreprise.
            </p>
            <p class="mt-2">
                Les conditions de prise en charge des frais liés aux déplacements (indemnités kilométriques, hébergement,
                restauration) seront conformes aux dispositions légales, conventionnelles et à la politique interne de
                l'entreprise en la matière.
            </p>
        </section>

        {{-- Article 7 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 7 : Durée du travail</h3>
            <p>
                La durée de travail conventionnelle est fixée à <strong>{{ $contract->weekly_hours }} heures</strong>
                hebdomadaires, soit <strong>{{ number_format($contract->weekly_hours * 52 / 12, 1, ',', ' ') }} heures</strong>
                mensuelles (base légale de 35 heures hebdomadaires).
            </p>
            <p class="mt-2">
                Les horaires de travail pourront être modifiés en fonction des impératifs des chantiers, dans le respect
                des dispositions légales relatives à la durée maximale quotidienne et hebdomadaire de travail, ainsi qu'aux
                temps de repos obligatoires.
            </p>
            <p class="mt-2">
                <strong>Heures supplémentaires :</strong> Toute heure effectuée au-delà de la durée légale ou conventionnelle
                sera considérée comme heure supplémentaire et sera rémunérée conformément aux dispositions légales et
                conventionnelles en vigueur (majoration de 25% pour les 8 premières heures, puis 50% au-delà, sauf
                accord collectif différent).
            </p>
            <p class="mt-2">
                <strong>Travail de nuit :</strong> Le travail de nuit (entre 21 heures et 6 heures) pourra être occasionnellement
                organisé, dans les conditions prévues par le Code du travail et la convention collective, sous réserve d'une
                contrepartie en repos ou en rémunération.
            </p>
        </section>

        {{-- Article 8 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 8 : Rémunération</h3>
            <p>
                En contrepartie de son travail, le salarié percevra une rémunération horaire brute de
                <strong>{{ number_format($contract->hourly_rate, 2, ',', ' ') }} €</strong> par heure.
            </p>
            <p class="mt-2">
                La rémunération mensuelle brute brute estimée est de :
            </p>
            <div class="bg-slate-50 p-3 rounded my-2 text-xs font-mono">
                <p>{{ number_format($contract->hourly_rate, 2, ',', ' ') }} €/h × {{ $contract->weekly_hours }} h/semaine × 52 semaines / 12 mois = <strong>{{ number_format($contract->hourly_rate * $contract->weekly_hours * 52 / 12, 2, ',', ' ') }} €</strong></p>
            </div>
            <p class="mt-2">
                Le versement de la rémunération s'effectue mensuellement, par virement bancaire, au plus tard le
                dernier jour ouvrable de chaque mois. Un bulletin de paie détaillé sera remis au salarié à chaque
                échéance, conformément aux obligations légales.
            </p>
            <p class="mt-2">
                La rémunération pourra être révisée en fonction de l'évolution de la qualification du salarié,
                de l'ancienneté acquise ou de modification de ses fonctions, sans que cette révision puisse avoir
                pour effet de diminuer la rémunération antérieure.
            </p>
        </section>

        {{-- Article 9 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 9 : Primes et Indemnités</h3>
            <p>
                Le salarié pourra bénéficier des primes et indemnités suivantes, sous réserve des conditions d'attribution
                prévues par la convention collective et les accords internes de l'entreprise :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li><strong>Indemnité de panier :</strong> versée conformément au barème conventionnel pour les jours de déplacement ;</li>
                <li><strong>Indemnité de trajet :</strong> prise en charge des frais de transport entre le domicile ou le lieu de travail habituel et le chantier, dans les conditions prévues par la loi et la convention collective ;</li>
                <li><strong>Indemnité de transport :</strong> remboursement des frais de transport en commun ou indemnité kilométrique pour l'utilisation d'un véhicule personnel, dans les conditions de la politique interne de l'entreprise ;</li>
                <li><strong>Indemnités de chantier :</strong> le cas échéant, primes liées aux conditions de travail sur chantier (travaux en hauteur, travaux en milieu insalubre, etc.).</li>
            </ul>
        </section>

        {{-- Article 10 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 10 : Congés Payés</h3>
            <p>
                Le salarié bénéficie de congés payés annuels conformément aux dispositions légales (Code du travail,
                art. L. 3141 et suivants) et conventionnelles. La durée du congé est de <strong>2,5 jours ouvrables par mois</strong>
                de présence effective, soit <strong>30 jours ouvrables par an</strong> pour un travail à temps plein.
            </p>
            <p class="mt-2">
                L'indemnité de congés payés est calculée selon la règle du 1/10e ou selon la règle du maintien de salaire,
                la plus avantageuse pour le salarié. Le paiement des indemnités de congés est assuré par la
                <strong>Caisse des Congés Intempéries BTP</strong> pour les salariés du secteur du bâtiment.
            </p>
            <p class="mt-2">
                Le congé principal d'au moins 12 jours ouvrables consécutifs sera pris pendant la période du 1er mai au
                31 octobre, sauf accord entre les Parties. L'ordre et les dates des congés seront fixés par l'Employeur
                en tenant compte des contraintes de service et des vœux exprimés par le salarié.
            </p>
        </section>

        {{-- Article 11 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 11 : Jours Fractionnés et RTT</h3>
            <p>
                Le salarié pourra bénéficier de jours fractionnés de congés payés et de jours de réduction du temps de
                travail (RTT) en vertu des accords collectifs applicables. L'attribution et la prise de ces jours seront
                soumis à l'accord de l'Employeur et aux nécessités du service.
            </p>
        </section>

        {{-- Article 12 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 12 : Frais Professionnels</h3>
            <p>
                Les frais professionnels engagés par le salarié dans l'exercice de ses fonctions, avec l'accord préalable
                de la Direction, seront remboursés sur présentation de justificatifs valables (tickets, factures, notes
                de frais).
            </p>
            <p class="mt-2">
                La prise en charge des frais professionnels s'effectuera dans les conditions suivantes :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Frais de déplacement : remboursement selon le barème kilométrique en vigueur ou remboursement sur justificatifs ;</li>
                <li>Frais de repas : indemnité conforme au barème conventionnel lorsque le salarié est en déplacement ;</li>
                <li>Frais d'hébergement : prise en charge des frais d'hébergement sur justificatifs pour les grands déplacements.</li>
            </ul>
            <p class="mt-2">
                Le salarié s'engage à transmettre ses notes de frais dans un délai maximum de 30 jours après la date
                du déplacement ou de la dépense.
            </p>
        </section>

        {{-- Article 13 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 13 : Maladie et Accident du travail</h3>
            <p>
                <strong>En cas d'absence pour maladie ou accident :</strong>
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Le salarié s'engage à prévenir l'employeur dans les <strong>48 heures</strong> de son incapacité de travailler ;</li>
                <li>Un certificat médical justifiant de l'indisponibilité devra être adressé à l'employeur dans les <strong>48 heures</strong> suivant l'arrêt ;</li>
                <li>La prolongation de l'absence devra être justifiée dans les mêmes délais ;</li>
                <li>Le salarié devra se soumettre à la contre-visite médicale si l'employeur en fait la demande.</li>
            </ul>
            <p class="mt-2">
                <strong>En cas d'accident du travail :</strong> le salarié ou ses ayants droit devront en informer l'employeur
                dans les <strong>24 heures</strong>. L'employeur procédera à la déclaration d'accident du travail auprès de la CPAM
                dans les <strong>48 heures</strong>. Le maintien de salaire sera assuré conformément aux dispositions légales
                (indemnités journalières de la Sécurité sociale complétées par l'employeur selon la convention collective).
            </p>
        </section>

        {{-- Article 14 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 14 : Prévoyance et Mutuelle</h3>
            <p>
                Le salarié sera affilié obligatoirement aux régimes collectifs suivants :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li><strong>Retraite complémentaire :</strong> Arrco et/ou Agirc selon la catégorie professionnelle ;</li>
                <li><strong>Prévoyance :</strong> régime de prévoyance BTP-Prévoyance (PRO BTP) couvrant les garanties décès, incapacité, invalidité et absence de longue durée ;</li>
                <li><strong>Mutuelle d'entreprise :</strong> couverture complémentaire santé conformément aux obligations légales (FEPEM BTP ou organisme mutualiste choisi par l'entreprise).</li>
            </ul>
            <p class="mt-2">
                Les cotisations patronales et salariales seront prélevées sur le bulletin de paie conformément aux taux
                en vigueur. Le salarié sera informé des modalités de couverture et des garanties offertes lors de son embauche.
            </p>
        </section>

        {{-- Article 15 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 15 : Obligation de Loyauté et d'Exclusivité</h3>
            <p>
                Le salarié s'engage à consacrer l'intégralité de son temps de travail à l'entreprise et s'interdit
                d'exercer toute autre activité professionnelle, rémunérée ou non, sans l'autorisation écrite préalable
                de l'Employeur.
            </p>
            <p class="mt-2">
                Le salarié s'engage à faire preuve de loyauté envers l'Employeur, c'est-à-dire à agir de bonne foi,
                à ne pas chercher à nuire à l'entreprise et à exécuter son travail avec diligence et compétence.
                Cette obligation subsiste pendant toute la durée du contrat et peut, le cas échéant, survivre à la
                rupture du contrat pour certaines obligations (confidentialité, non-concurrence).
            </p>
        </section>

        {{-- Article 16 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 16 : Clause de Confidentialité</h3>
            <p>
                Le salarié est tenu au secret professionnel et à une obligation de discrétion absolue sur tout ce
                qui concerne les activités, les méthodes de travail, les procédés techniques, les informations
                commerciales et financières, ainsi que la clientèle de l'entreprise.
            </p>
            <p class="mt-2">
                Cette obligation s'applique pendant toute la durée du contrat et peut subsister après la rupture du
                contrat, pour une durée proportionnée à la nature des informations concernées et dans la limite
                de ce que la loi autorise.
            </p>
            <p class="mt-2">
                Le non-respect de cette clause pourra entraîner des sanctions disciplinaires pouvant aller jusqu'au
                licenciement pour faute grave, sans préjudice de dommages et intérêts.
            </p>
        </section>

        {{-- Article 17 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 17 : Clause de Non-Concurrence</h3>
            <p>
                En cas de rupture du contrat de travail, et selon les fonctions exercées par le salarié, une clause
                de non-concurrence pourra s'appliquer, sous réserve du respect cumulatif des conditions suivantes :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Un intérêt légitime de l'Employeur à protéger ses intérêts économiques ;</li>
                <li>Une restriction géographique définie ;</li>
                <li>Une durée maximale de 12 mois ;</li>
                <li>Une contrepartie financière versée au salarié, dont le montant est fixé par la convention collective ou le contrat.</li>
            </ul>
            <p class="mt-2">
                Le non-respect de la clause de non-concurrence par le salarié ouvrira droit pour l'Employeur à la
                suspension du versement de la contrepartie financière et/ou à des dommages et intérêts.
            </p>
        </section>

        {{-- Article 18 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 18 : Obligations Professionnelles</h3>
            <p>
                Le salarié s'engage à :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Respecter les directives et instructions données par sa hiérarchie ;</li>
                <li>Travailler avec conscience et diligence ;</li>
                <li>Prendre soin des outils, machines, véhicules et matériaux confiés par l'entreprise ;</li>
                <li>Respecter les règles de sécurité en vigueur sur les chantiers ;</li>
                <li>Participer activement aux réunions et formations obligatoires ;</li>
                <li>Informer immédiatement sa hiérarchie de tout dysfonctionnement ou danger potentiel ;</li>
                <li>Respecter les normes environnementales en vigueur.</li>
            </ul>
        </section>

        {{-- Article 19 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 19 : Utilisation du Matériel de l'Entreprise</h3>
            <p>
                Le matériel mis à disposition du salarié (téléphone, ordinateur, outillage, véhicule, vêtements de
                travail, EPI) reste la propriété exclusive de l'entreprise et doit être utilisé exclusivement à des
                fins professionnelles.
            </p>
            <p class="mt-2">
                Le salarié s'engage à :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>En faire un usage conforme à sa destination ;</li>
                <li>Le conserver en bon état et signaler toute défectuosité ;</li>
                <li>Le restituer en fin de contrat, sauf disposition contraire ;</li>
                <li>Ne pas le prêter à des tiers sans autorisation.</li>
            </ul>
            <p class="mt-2">
                En cas de perte, de vol ou de détérioration imputable à une faute du salarié, celui-ci pourra être tenu
                de participer aux frais de remplacement ou de réparation, dans des limites raisonnables et conformément
                à la réglementation en vigueur.
            </p>
        </section>

        {{-- Article 20 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 20 : Sécurité et Port des Équipements de Protection Individuelle (EPI)</h3>
            <p>
                Le salarié s'engage à respecter scrupuleusement les consignes de sécurité, les règles de l'art et les
                normes en vigueur sur les chantiers, notamment :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Le port obligatoire des Équipements de Protection Individuelle (casque, chaussures de sécurité, gilet, gants, lunettes, etc.) conformément à la réglementation ;</li>
                <li>Le respect des consignes de circulation et de sécurité sur les chantiers ;</li>
                <li>La vérification quotidienne de l'état des EPI et du matériel ;</li>
                <li>La signalisation des situations dangereuses ;</li>
                <li>La participation aux formations sécurité obligatoires (CACES, habilitations, SST, etc.).</li>
            </ul>
            <p class="mt-2">
                Le salarié déclare avoir été informé des risques professionnels liés à son poste de travail et avoir reçu
                la formation sécurité appropriée. Tout manquement aux règles de sécurité pourra donner lieu à des
                sanctions disciplinaires.
            </p>
        </section>

        {{-- Article 21 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 21 : Santé et Sécurité au Travail</h3>
            <p>
                L'Employeur s'engage à assurer la santé et la sécurité du salarié conformément aux dispositions du
                Code du travail (L. 4121 et suivants). Le salarié sera soumis à :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>La visite médicale d'embauche ou la visite d'Information et de Prévention (VIP) ;</li>
                <li>Les visites de reprise le cas échéant ;</li>
                <li>Les examens médicaux périodiques selon la fréquence prévue par la convention collective.</li>
            </ul>
            <p class="mt-2">
                Le salarié est informé de son droit de signaler toute situation de travail presenting un danger grave
                et imminent pour sa vie ou sa santé, conformément à l'article L. 4131-1 du Code du travail. Il benefit
                de la protection contre le licenciement prévue par la loi en cas de signalement.
            </p>
        </section>

        {{-- Article 22 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 22 : Règlement Intérieur</h3>
            <p>
                Le salarié déclare avoir pris connaissance et accepté le règlement intérieur de l'entreprise, qui lui
                a été remis à son embauche. Il s'engage à s'y conformer sans réserve.
            </p>
            <p class="mt-2">
                Le règlement intérieur définit notamment les règles d'hygiène et de sécurité, les conditions d'emploi
                des salariés, les sanctions disciplinaires applicables et les modalités de recours. Il est affiché
                dans les locaux de l'entreprise et accessible à tout moment.
            </p>
        </section>

        {{-- Article 23 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 23 : Véhicule de l'Entreprise</h3>
            <p>
                Si un véhicule est confié au salarié dans le cadre de ses fonctions, celui-ci s'engage à :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>En faire un usage strictement professionnel, sauf autorisation contraire de l'Employeur ;</li>
                <li>Respecter le Code de la route et les règles de sécurité routière ;</li>
                <li>Entretenir le véhicule conformément aux recommandations du constructeur ;</li>
                <li>Signaler immédiatement tout sinistre, dommage ou dysfonctionnement ;</li>
                <li>Ne pas conduire sous l'influence de l'alcool ou de substances stupéfiantes ;</li>
                <li>Restituer le véhicule en fin de contrat dans le même état que lors de la prise de possession.</li>
            </ul>
            <p class="mt-2">
                Les frais de carburant, d'entretien et d'assurance seront pris en charge par l'entreprise dans les
                conditions prévues par la politique interne.
            </p>
        </section>

        {{-- Article 24 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 24 : Informatique et Libertés (RGPD)</h3>
            <p>
                Les données à caractère personnel du salarié sont collectées et traitées par l'Employeur pour les
                besoins de la gestion du personnel (paie, administration, suivi médical, etc.), conformément au
                Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.
            </p>
            <p class="mt-2">
                Le salarié dispose des droits suivants :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Droit d'accès à ses données personnelles ;</li>
                <li>Droit de rectification des données inexactes ;</li>
                <li>Droit à l'effacement des données (« droit à l'oubli ») ;</li>
                <li>Droit à la limitation du traitement ;</li>
                <li>Droit à la portabilité des données ;</li>
                <li>Droit d'opposition au traitement.</li>
            </ul>
            <p class="mt-2">
                Ces droits peuvent être exercés en contactant le Délégué à la Protection des Données (DPO) de
                l'entreprise ou en adressant une demande écrite au siège social. Les données personnelles sont
                conservées pendant la durée du contrat et pendant la durée légale de conservation des documents
                sociaux et fiscaux.
            </p>
        </section>

        {{-- Article 25 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 25 : Formation Professionnelle</h3>
            <p>
                Le salarié benefit du droit à la formation professionnelle continue, conformément aux dispositions
                légales (Compte Personnel de Formation – CPF) et conventionnelles.
            </p>
            <p class="mt-2">
                L'entreprise s'engage à faciliter l'accès du salarié aux actions de formation, notamment :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Les formations obligatoires liées à la sécurité (CACES, habilitations, SST, etc.) ;</li>
                <li>Les formations liées à l'évolution du poste de travail ;</li>
                <li>Les actions de VAE (Validation des Acquis de l'Expérience) le cas échéant.</li>
            </ul>
            <p class="mt-2">
                Le plan de développement des compétences de l'entreprise sera communiqué annuellement au Comité
                Social et Économique (CSE) et sera consultable par les salariés.
            </p>
        </section>

        {{-- Article 26 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 26 : Protection Sociale</h3>
            <p>
                Le salarié benefit des garanties collectives de protection sociale mises en place par l'entreprise,
                comprenant :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>La mutuelle d'entreprise (complémentaire santé) ;</li>
                <li>Le régime de prévoyance (décès, incapacité, invalidité) ;</li>
                <li>La participation aux régimes de retraite complémentaire (Arrco/Agirc).</li>
            </ul>
            <p class="mt-2">
                Les cotisations afférentes à ces garanties sont prises en charge conjointement par l'Employeur et
                le salarié, selon les taux en vigueur. Le salarié sera informé des modalités de couverture lors de
                son embauche.
            </p>
        </section>

        {{-- Article 27 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 27 : Disciplinaire et Sanctions</h3>
            <p>
                En cas de manquement du salarié à ses obligations contractuelles ou professionnelle, l'Employeur
                pourra prendre à son encontre des sanctions disciplinaires, après une investigation contradictoire,
                proportionnées à la faute commise :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li>Avertissement ;</li>
                <li>Blâme ;</li>
                <li>Mise à pied disciplinaire ;</li>
                <li>Rétrogradation ;</li>
                <li>Licenciement pour faute simple, grave ou lourde.</li>
            </ul>
            <p class="mt-2">
                Le salarié sera systématiquement convoqué à un entretien préalable avant toute sanction, conformément
                aux dispositions légales (art. L. 1332-2 du Code du travail). Il pourra se faire assister par une
                personne de son choix appartenant à l'entreprise.
            </p>
        </section>

        {{-- Article 28 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 28 : Rupture du Contrat</h3>
            <p>
                <strong>Pendant la période d'essai :</strong> chacune des Parties pourra rompre le contrat dans les
                conditions et délais prévus à l'article 4 du présent contrat.
            </p>
            <p class="mt-2">
                <strong>Après la période d'essai :</strong> toute rupture du contrat de travail sera soumise aux
                dispositions légales et conventionnelles en vigueur :
            </p>
            <ul class="list-disc ml-6 mt-1 space-y-1">
                <li><strong>Démission :</strong> le salarié devra respecter un délai de préavis dont la durée est fixée par la convention collective (généralement 1 mois, 2 mois ou 3 mois selon l'ancienneté et la catégorie) ;</li>
                <li><strong>Licenciement :</strong> l'Employeur devra respecter les délais de préavis et les procédures légales (convocation, entretien préalable, notification) ;</li>
                <li><strong>Rupture conventionnelle :</strong> accord mutuel des Parties, soumis à homologation de l'Inspection du Travail ;</li>
                <li><strong>Rupture anticipée :</strong> en cas de faute grave ou lourde, sans préavis ni indemnité.</li>
            </ul>
            <p class="mt-2">
                En fin de contrat, le salarié recevra les documents obligatoires : certificat de travail, reçu pour
                solde de tout compte, attestation Pôle emploi (ou France Travail), et le décompte des congés payés.
            </p>
        </section>

        {{-- Article 29 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 29 : Conventions et Accords Collectifs</h3>
            <p>
                Les relations entre les parties sont régies par le présent contrat, la <strong>Convention Collective
                Nationale du Bâtiment et des Travaux Publics (IDCC 1596)</strong>, ses avenants, ainsi que par les
                accords d'entreprise applicables.
            </p>
            <p class="mt-2">
                En cas de contradiction entre le présent contrat et la convention collective, les dispositions les plus
                favorables au salarié seront applicables, conformément au principe de faveur.
            </p>
            <p class="mt-2">
                Le salarié déclare avoir été informé de l'existence et du contenu de la convention collective applicable,
                qui est consultable dans les locaux de l'entreprise et sur le site internet du ministère du Travail.
            </p>
        </section>

        {{-- Article 30 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 30 : Litiges et Juridiction Compétente</h3>
            <p>
                Tout litige relatif à l'interprétation ou à l'exécution du présent contrat, ou à la rupture de celui-ci,
                sera soumis en priorité à la conciliation et à la médiation, dans les conditions prévues par la convention
                collective et le règlement intérieur.
            </p>
            <p class="mt-2">
                À défaut de résolution amiable, le litige sera porté devant le <strong>Conseil de Prud'hommes</strong>
                territorialement compétent, conformément aux dispositions légales applicables (Code du travail,
                art. L. 1411 et suivants).
            </p>
        </section>

        {{-- Article 31 --}}
        <section>
            <h3 class="font-bold uppercase text-blue-batistack underline mb-2">Article 31 : Dispositions Diverses</h3>
            <p>
                Le présent contrat a été établi en deux (2) exemplaires originaux, dont un pour chaque Partie.
            </p>
            <p class="mt-2">
                Le salarié déclare avoir reçu un exemplaire du contrat de travail et en avoir pris connaissance avant
                sa signature. Il déclare également avoir reçu un exemplaire de la convention collective applicable.
            </p>
            <p class="mt-2">
                Toute modification du contrat de travail devra faire l'objet d'un avenant écrit, signé par les deux
                Parties, sauf accord verbal constaté par un échange de courriers recommandés avec accusé de réception.
            </p>
            <p class="mt-2">
                Les nullités partielles de certaines clauses du présent contrat n'affectent pas la validité de l'ensemble
                du contrat, qui continuera de produire ses effets dans les limites autorisées par la loi.
            </p>
        </section>
    </div>

    {{-- Signatures --}}
    <div class="mt-16">
        <p class="font-bold mb-8 text-xs uppercase text-center">
            Fait à {{ $company->city }}, le {{ now()->format('d/m/Y') }}, en deux exemplaires originaux
        </p>

        <div class="flex justify-between">
            <div class="text-center w-2/5">
                <p class="font-bold mb-12 text-xs uppercase underline">Pour l'Employeur</p>
                <p class="text-xs text-slate-500 mb-2">Signature et cachet de l'entreprise</p>
                <div class="border-t border-slate-300 mt-20 pt-2">
                    <p class="text-xs">{{ $company->legal_name }}</p>
                </div>
            </div>
            <div class="text-center w-2/5">
                <p class="font-bold mb-12 text-xs uppercase underline">Le Salarié</p>
                <p class="text-xs text-slate-500 mb-2">Mention « Lu et approuvé »</p>
                @if(isset($signature) && $signature->signature_data)
                    <div class="text-xs text-slate-500 mb-2">
                        Signé par {{ $employee->full_name }}<br>
                        le {{ $signature->signed_at ? $signature->signed_at->format('d/m/Y à H:i:s') : now()->format('d/m/Y à H:i:s') }}<br>
                        Réf : {{ $signature->token }}
                    </div>
                    <img src="{{ $signature->signature_data }}" style="max-height: 80px; margin: 0 auto;" alt="Signature">
                @else
                    <p class="underline mb-8">Signature du Salarié</p>
                    <div style="color: transparent; font-size: 8px; margin-top: 40px;">
                        @{{Signature;role=Signataire;type=signature}}
                    </div>
                @endif
                <div class="border-t border-slate-300 mt-4 pt-2">
                    <p class="text-xs">{{ $employee->full_name }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
