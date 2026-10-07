<?php

declare(strict_types=1);

namespace App\Domain\Search\Demo;

/**
 * Le catalogue des recherches de démonstration — task API-029.
 *
 * Il existe pour qu'on puisse REGARDER la grille de l'accueil entreprise : cartes chargées, cartes à
 * peine ouvertes, cartes closes, cartes reçues d'un collègue. Une seule recherche de
 * fixtures ne montre aucune de ces situations.
 *
 * Les métiers sont ceux réellement portés par le vivier importé, et ils débordent largement
 * l'informatique : administration, commerce, finance, industrie, logistique, ressources humaines,
 * enseignement, restauration, bâtiment, juridique. Une grille qui n'afficherait que des postes
 * techniques donnerait une fausse idée de ce que la plateforme sait trouver.
 *
 * Trois règles gouvernent les combinaisons, et elles viennent de ce que contient le vivier :
 *
 * - **Deux critères au minimum.** Les recherches d'essai qu'un développeur laisse derrière lui n'en
 *   portent qu'un ; c'est ce qui garantit qu'aucune ne sera confondue avec une entrée d'ici.
 * - **Aucune recherche ne ramène zéro profil.** Une carte vide n'apprend rien sur la mise en page,
 *   et fait douter de la recherche plutôt que du jeu de démonstration.
 * - **Les critères qui RESSERRENT restent rares** — un seuil d'expérience ou un niveau d'études ne
 *   se pose que sur un métier volumineux. Les mots-clés, eux, élargissent, et peuvent donc être
 *   nombreux partout.
 */
final class DemoSearchCatalog
{
    /**
     * L'avancement, dérivé du RANG de la recherche plutôt que tiré au sort : deux exécutions
     * produisent la même grille, et une capture d'écran reste comparable à la suivante.
     *
     * Les sept jeux se répètent sur toute la longueur du catalogue et couvrent le spectre : jamais
     * ouverte, à peine entamée, en cours, terminée.
     *
     * @var list<array{int, int, int}> seenPercent, retained, fresh
     */
    private const array PROGRESSIONS = [
        [0, 0, 0],
        [6, 2, 12],
        [18, 8, 0],
        [34, 3, 4],
        [55, 14, 0],
        [78, 5, 27],
        [100, 9, 2],
    ];

    /**
     * Les mots-clés viennent des COMPÉTENCES QUE PORTENT RÉELLEMENT les profils de chaque métier,
     * choisies pour leur spécificité et non pour leur fréquence.
     *
     * La distinction décide de tout. Les compétences les plus fréquentes d'un métier sont les plus
     * creuses — « communication », « management », « microsoft office » reviennent sur presque tous
     * les profils du vivier et ne disent rien de ce qu'on cherche. Les compétences retenues ici sont
     * celles qui sont nettement plus présentes chez ce métier qu'ailleurs : `catia` et `solidworks`
     * pour un ingénieur mécanique, `déclaration fiscale` pour un comptable, `terraform` et `jenkins`
     * pour un ingénieur DevOps.
     *
     * Aucune langue n'y figure. « Anglais » est la compétence la plus fréquente du vivier après les
     * outils bureautiques : posée en mot-clé, elle ramènerait un profil sur quatre de n'importe quel
     * métier, et ferait passer une recherche ciblée pour une recherche large.
     *
     * Les mots-clés s'ajoutent en OU : en poser cinq ÉLARGIT la recherche au lieu de la resserrer.
     * C'est ce qui permet d'en mettre autant sans vider les cartes.
     *
     * @return list<DemoSearchDefinition>
     */
    public function definitions(): array
    {
        return [
            // ─── Direction, gestion de projet et encadrement ───────────────────────────────────
            new DemoSearchDefinition([
                self::job('Chef de projet'),
                self::experience(5),
                self::education('MASTER'),
                self::keyword('prince2'), self::keyword('gestion de projet'),
                self::keyword('project management'), self::keyword('project delivery'),
                self::keyword('kpi'),
            ], shared: true),
            new DemoSearchDefinition([
                self::job('Chef de projet'),
                self::experience(5),
                self::education('MASTER'),
                self::keyword('project estimation'), self::keyword('project management'),
                self::keyword('projets de migration'),
            ], closed: true),
            new DemoSearchDefinition([
                self::job('Dirigeant / Fondateur'),
                self::experience(10),
                self::keyword('start-ups'), self::keyword('executive management'),
                self::keyword('market development'),
            ]),
            new DemoSearchDefinition([
                self::job('Manager'),
                self::experience(8),
                self::education('LICENCE'),
                self::keyword('p&l management'), self::keyword('management'),
                self::keyword('team management'), self::keyword('b2b'),
                self::keyword('employee training'),
            ], byColleague: true, shared: true),
            new DemoSearchDefinition([
                self::job('Responsable'),
                self::experience(8),
                self::education('LICENCE'),
                self::keyword('gestion d’équipe'), self::keyword('management'),
                self::keyword('gestion du personnel'), self::keyword('motivation des équipes'),
                self::keyword('merchandising'),
            ], shared: true),
            new DemoSearchDefinition([
                self::job("Chef d'équipe"),
                self::country('FR'),
                self::keyword('management'), self::keyword('gestion d’équipe'),
                self::keyword('service client'), self::keyword('leadership d’équipe'),
                self::keyword('operations management'), self::keyword('expérience client'),
            ], byColleague: true, shared: true),
            new DemoSearchDefinition([
                self::job('Coordinateur'),
                self::experience(3),
                self::keyword('project management'), self::keyword('event management'),
                self::keyword('public relations'), self::keyword('customer satisfaction'),
            ]),
            new DemoSearchDefinition([
                self::job("Responsable d'exploitation"),
                self::keyword('gestion des opérations'), self::keyword('gestion du changement'),
                self::keyword('e-commerce'),
            ]),

            // ─── Commerce et développement ─────────────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Commercial'),
                self::education('MASTER'),
                self::country('FR'),
                self::keyword('fidélisation'), self::keyword('vente'),
                self::keyword('développement commercial'), self::keyword('ventes directes'),
                self::keyword('gestion de compte'), self::keyword('import-export'),
            ]),
            new DemoSearchDefinition([
                self::job('Commercial terrain / Ventes'),
                self::experience(5),
                self::education('BAC_PLUS_2'),
                self::keyword('saas'), self::keyword('vente'),
                self::keyword('développement commercial'), self::keyword('sales process'),
                self::keyword('solution selling'), self::keyword('international sales'),
            ]),
            new DemoSearchDefinition([
                self::job('Business Developer'),
                self::experience(3),
                self::education('LICENCE'),
                self::keyword('grands comptes'), self::keyword('développement commercial'),
                self::keyword('vente'), self::keyword('génération de leads'),
                self::keyword('télémarketing'), self::keyword('international sales'),
            ]),
            new DemoSearchDefinition([
                self::job('Sales Manager'),
                self::experience(5),
                self::keyword('direct sales'), self::keyword('international sales'),
                self::keyword('processus de vente'),
            ]),
            new DemoSearchDefinition([
                self::job('Account Manager'),
                self::experience(3),
                self::keyword('grands comptes'), self::keyword('saas'),
                self::keyword('solution selling'), self::keyword('enterprise software'),
            ]),
            new DemoSearchDefinition([
                self::job("Chargé d'affaires"),
                self::keyword('génie électrique'), self::keyword('télécommunications'),
                self::keyword('prospection'), self::keyword('stratégie commerciale'),
            ]),
            new DemoSearchDefinition([
                self::job('Responsable de magasin'),
                self::keyword('commerce de détail'), self::keyword('gestion des stocks'),
                self::keyword('gestion des ventes'),
            ], shared: true),

            // ─── Administration et relation client ─────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Assistant administratif'),
                self::experience(3),
                self::country('FR'),
                self::keyword('service client'), self::keyword('aide administrative'),
                self::keyword('saisie de données'), self::keyword('création de contenu'),
            ]),
            new DemoSearchDefinition([
                self::job('Conseiller service client'),
                self::experience(5),
                self::education('BAC_PLUS_2'),
                self::keyword('expérience client'), self::keyword('service client'),
                self::keyword('vente'), self::keyword('services clients'),
                self::keyword('processus de vente'),
            ]),
            new DemoSearchDefinition([
                self::job('Gestionnaire'),
                self::experience(3),
                self::keyword('service client'), self::keyword('gestion des opérations'),
                self::keyword('gestion du temps'),
            ]),
            new DemoSearchDefinition([
                self::job('Employé polyvalent'),
                self::keyword('autonomie'), self::keyword('sens de l’organisation'),
                self::keyword('service client'),
            ]),
            new DemoSearchDefinition([
                self::job('Réceptionniste'),
                self::keyword('relation client'), self::keyword('service client'),
                self::keyword('adaptabilité'),
            ]),
            new DemoSearchDefinition([
                self::job('Hôte / Hôtesse'),
                self::keyword('accueil'), self::keyword('service client'),
                self::keyword('vente'),
            ]),

            // ─── Finance, gestion et droit ─────────────────────────────────────────────────────
            new DemoSearchDefinition(
                [
                    self::job('Comptable'),
                    self::education('BAC_PLUS_2'),
                    self::country('FR'),
                    self::keyword('comptabilité'), self::keyword('finance'),
                    self::keyword('déclaration fiscale'), self::keyword('comptabilité fiscale'),
                    self::keyword('flux de trésorerie'), self::keyword('account reconciliation'),
                ],
                name: 'Comptabilité — renfort pour la clôture annuelle',
            ),
            new DemoSearchDefinition([
                self::job('Contrôleur de gestion'),
                self::keyword('contrôle des coûts'), self::keyword('consolidation'),
                self::keyword('rapports financiers'), self::keyword('flux de trésorerie'),
            ], byColleague: true, shared: true),
            new DemoSearchDefinition([
                self::job('Auditeur'),
                self::experience(5),
                self::education('MASTER'),
                self::keyword('contrôles internes'), self::keyword('audit'),
                self::keyword('comptabilité'), self::keyword('finance'),
                self::keyword('audit externe'), self::keyword('audit interne'),
                self::keyword('consolidation'),
            ]),
            new DemoSearchDefinition([
                self::job('Analyste financier'),
                self::keyword('financial modeling'), self::keyword('analyse financière'),
                self::keyword('corporate finance'), self::keyword('audit financier'),
            ]),
            new DemoSearchDefinition([
                self::job('Juriste'),
                self::keyword('droit des affaires'), self::keyword('droit des sociétés'),
                self::keyword('recherche juridique'),
            ]),

            // ─── Ressources humaines, enseignement et formation ────────────────────────────────
            new DemoSearchDefinition([
                self::job('Chargé de recrutement'),
                self::experience(3),
                self::keyword('sourcing'), self::keyword('recruiting'),
                self::keyword('gestion des talents'), self::keyword('entretiens'),
            ], shared: true),
            new DemoSearchDefinition([
                self::job('Assistant ressources humaines'),
                self::experience(3),
                self::keyword('recruiting'), self::keyword('sourcing'),
                self::keyword('formation'),
            ], byColleague: true),
            new DemoSearchDefinition([
                self::job('Formateur'),
                self::experience(5),
                self::keyword('formation'), self::keyword('coaching'),
                self::keyword('sens de l’écoute'), self::keyword('adaptabilité'),
            ]),
            new DemoSearchDefinition([
                self::job('Enseignant'),
                self::experience(3),
                self::keyword('teaching'), self::keyword('mathématiques'),
                self::keyword('parler en public'),
            ], closed: true),
            new DemoSearchDefinition([
                self::job('Chercheur'),
                self::keyword('deep learning'), self::keyword('matlab'),
                self::keyword('problem solving'),
            ]),

            // ─── Marketing et création ─────────────────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Assistant marketing'),
                self::keyword('google ads'), self::keyword('content marketing'),
                self::keyword('facebook ads'), self::keyword('media planning'),
            ]),
            new DemoSearchDefinition([
                self::job('Community Manager'),
                self::experience(3),
                self::keyword('storytelling'), self::keyword('social media marketing'),
                self::keyword('google ads'), self::keyword('canva'),
                self::keyword('montage vidéo'),
            ]),
            new DemoSearchDefinition([
                self::job('Chef de produit'),
                self::experience(3),
                self::keyword('marketing'), self::keyword('stratégie marketing'),
                self::keyword('gestion de produit'), self::keyword('benchmarking'),
            ]),
            new DemoSearchDefinition([
                self::job('Designer'),
                self::experience(3),
                self::keyword('branding'), self::keyword('adobe photoshop'),
                self::keyword('design'), self::keyword('autocad'),
            ], byColleague: true),

            // ─── Industrie, qualité et maintenance ─────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job("Ingénieur d'études"),
                self::experience(3),
                self::keyword('ingénierie'), self::keyword('structural analysis'),
                self::keyword('electronics'), self::keyword('énergie éolienne'),
                self::keyword('comsol'),
            ]),
            new DemoSearchDefinition([
                self::job('Technicien de maintenance'),
                self::experience(3),
                self::keyword('gmao'), self::keyword('maintenance préventive'),
                self::keyword('maintenance et réparation'), self::keyword('maintenance mécanique'),
                self::keyword('dépannage électrique'), self::keyword('hydraulique'),
            ]),
            new DemoSearchDefinition([
                self::job('Ingénieur qualité'),
                self::keyword('iso 9001'), self::keyword('assurance qualité'),
                self::keyword('contrôle qualité'), self::keyword('lean manufacturing'),
            ], byColleague: true),
            new DemoSearchDefinition([
                self::job('Ingénieur mécanique'),
                self::experience(3),
                self::keyword('catia'), self::keyword('solidworks'),
                self::keyword('ansys'), self::keyword('abaqus'),
                self::keyword('génie mécanique'),
            ]),
            new DemoSearchDefinition([
                self::job('Responsable maintenance'),
                self::keyword('gestion d’équipe'), self::keyword('ingénierie'),
                self::keyword('maintenance'),
            ]),
            new DemoSearchDefinition([
                self::job('Opérateur de production'),
                self::keyword('maintenance'), self::keyword('production'),
                self::keyword('solidworks'),
            ]),
            new DemoSearchDefinition([
                self::job('Chef de chantier'),
                self::keyword('construction'), self::keyword('autocad'),
                self::keyword('gestion d’équipe'),
            ], byColleague: true),

            // ─── Logistique, achats et transport ───────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Logisticien'),
                self::keyword('transport'), self::keyword('logistique'),
                self::keyword('achats'), self::keyword('négociation'),
            ]),
            new DemoSearchDefinition([
                self::job('Acheteur'),
                self::keyword('achats'), self::keyword('négociation'),
                self::keyword('sourcing'),
            ], byColleague: true, shared: true),
            new DemoSearchDefinition([
                self::job('Préparateur de commandes'),
                self::keyword('logistique'), self::keyword('gestion des stocks'),
                self::keyword('gestion de projet'), self::keyword('travail d’équipe'),
            ], byColleague: true),
            new DemoSearchDefinition([
                self::job('Chauffeur'),
                self::keyword('transport'), self::keyword('logistique'),
                self::keyword('service client'),
            ], closed: true),

            // ─── Restauration et accueil ───────────────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Employé de restauration'),
                self::keyword('service client'), self::keyword('restauration'),
                self::keyword('travail d’équipe'),
            ]),
            new DemoSearchDefinition([
                self::job('Responsable qualité'),
                self::keyword('amélioration continue'), self::keyword('gestion de projet'),
                self::keyword('quality management'), self::keyword('contrôle qualité'),
            ]),

            // ─── Conseil et analyse ────────────────────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Consultant'),
                self::experience(5),
                self::education('MASTER'),
                self::keyword('project management'), self::keyword('consulting'),
                self::keyword('strategic consulting'), self::keyword('organizational design'),
                self::keyword('asset management'), self::keyword('financial services'),
            ]),
            new DemoSearchDefinition([
                self::job('Business Analyst'),
                self::experience(3),
                self::keyword('sql'), self::keyword('scrum'),
                self::keyword('business analysis'), self::keyword('pl/sql'),
                self::keyword('agile methodologies'), self::keyword('jira'),
            ]),
            new DemoSearchDefinition([
                self::job('Analyste'),
                self::experience(3),
                self::education('MASTER'),
                self::keyword('sql'), self::keyword('microsoft power bi'),
                self::keyword('big data'), self::keyword('oracle database'),
                self::keyword('programmation'), self::keyword('optimisation'),
            ]),
            new DemoSearchDefinition([
                self::job('Consultant IT'),
                self::keyword('business analysis'), self::keyword('change management'),
                self::keyword('oracle'), self::keyword('consulting'),
            ]),
            new DemoSearchDefinition([
                self::job('Consultant en management'),
                self::keyword('strategic planning'), self::keyword('change management'),
                self::keyword('business strategy'), self::keyword('team leadership'),
            ]),

            // ─── Informatique et données ───────────────────────────────────────────────────────
            new DemoSearchDefinition([
                self::job('Développeur logiciel'),
                self::experience(5),
                self::keyword('java'), self::keyword('sql'),
                self::keyword('javascript'), self::keyword('spring'),
                self::keyword('jpa'), self::keyword('nestjs'),
                self::keyword('développement java'), self::keyword('spring batch'),
            ]),
            new DemoSearchDefinition([
                self::job('Data Analyst'),
                self::keyword('analyse prédictive'), self::keyword('tableau'),
                self::keyword('pyspark'), self::keyword('ingénierie des données'),
            ]),
            new DemoSearchDefinition([
                self::job('Data Scientist'),
                self::experience(3),
                self::keyword('nlp'), self::keyword('keras'),
                self::keyword('computer vision'), self::keyword('science des données'),
            ]),
            new DemoSearchDefinition([
                self::job('Data Engineer'),
                self::keyword('apache airflow'), self::keyword('azure data factory'),
                self::keyword('google bigquery'), self::keyword('pyspark'),
                self::keyword('scala'),
            ]),
            new DemoSearchDefinition([
                self::job('Ingénieur IA / ML'),
                self::experience(3),
                self::keyword('pytorch'), self::keyword('langchain'),
                self::keyword('keras'), self::keyword('nlp'),
                self::keyword('fastapi'),
            ], closed: true),
            new DemoSearchDefinition([
                self::job('Architecte (IT/logiciel)'),
                self::experience(5),
                self::keyword('solution architecture'), self::keyword('uml'),
                self::keyword('microsoft azure'), self::keyword('architecture'),
            ]),
            new DemoSearchDefinition([
                self::job('Administrateur système'),
                self::keyword('vmware vsphere'), self::keyword('windows server'),
                self::keyword('ansible'), self::keyword('virtualisation'),
            ], closed: true),
            new DemoSearchDefinition([
                self::job('Support informatique'),
                self::keyword('active directory'), self::keyword('servicenow'),
                self::keyword('gestion des incidents'), self::keyword('cisco technologies'),
            ]),
            new DemoSearchDefinition([
                self::job('Ingénieur DevOps'),
                self::keyword('terraform'), self::keyword('ansible'),
                self::keyword('jenkins'), self::keyword('kubernetes'),
                self::keyword('gitlab'),
            ]),
            new DemoSearchDefinition([
                self::job('Product Owner'),
                self::keyword('scrum'), self::keyword('jira'),
                self::keyword('confluence'), self::keyword('méthodes agiles'),
                self::keyword('gestion de produit'),
            ]),
        ];
    }

    /** L'avancement de la recherche de rang `$index`, borné au jeu réel par le seeder. */
    public function progressAt(int $index): DemoSearchProgress
    {
        [$seenPercent, $retained, $fresh] = self::PROGRESSIONS[$index % \count(self::PROGRESSIONS)];

        return new DemoSearchProgress($seenPercent, $retained, $fresh);
    }

    /** @return array{facet: string, value: string, group: string} */
    private static function job(string $label): array
    {
        return ['facet' => 'job', 'value' => $label, 'group' => 'metier'];
    }

    /** @return array{facet: string, value: string, group: string} */
    private static function experience(int $years): array
    {
        return ['facet' => 'experienceMin', 'value' => (string) $years, 'group' => 'experience'];
    }

    /** @return array{facet: string, value: string, group: string} */
    private static function education(string $level): array
    {
        return ['facet' => 'educationLevel', 'value' => $level, 'group' => 'etudes'];
    }

    /** @return array{facet: string, value: string, group: string} */
    private static function country(string $code): array
    {
        return ['facet' => 'country', 'value' => $code, 'group' => 'localisation'];
    }

    /** @return array{facet: string, value: string, group: string} */
    private static function keyword(string $term): array
    {
        return ['facet' => 'q', 'value' => $term, 'group' => 'motsCles'];
    }
}
