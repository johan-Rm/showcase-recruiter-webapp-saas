# showcase-recruiter-webapp-saas — Profil des dimensions du vivier

> **But** : décrire la composition du vivier. Pour chaque donnée portée par les profils, combien de
> valeurs distinctes elle prend, sur quelle part des profils elle est renseignée, et à quel point ces
> valeurs se répètent. Ce document dit ce que **contient** la donnée ; les critères de recherche qui
> s'appuient dessus vivent dans [criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md).

## Méthode de mesure

Les valeurs sont comptées **après normalisation** : casse, espaces et ponctuation uniformisés. Les
intitulés de poste sont en plus ramenés à leur **segment de tête**, avant le premier séparateur — un
intitulé tel que `Product Owner / AMOA | Renewables` compte comme `product owner`.

- **Valeurs distinctes** — nombre de valeurs uniques après normalisation.
- **Occurrences** — nombre total d'instances. Un profil portant douze compétences en apporte douze.
- **Profils** — nombre de profils portant au moins une valeur.
- **Couverture** — part des profils concernés.
- **Dispersion** — rapport des valeurs distinctes au nombre d'occurrences. Une dispersion basse
  signifie que les mêmes valeurs reviennent d'un profil à l'autre ; une dispersion haute signifie que
  chaque profil apporte des valeurs qui lui sont propres.

Les mesures portent sur **1 698 profils**, à l'exception de la société : celle-ci est présente à la
source Jarvi et masquée à l'exposition, sa mesure porte donc sur un échantillon de **100 profils**.

## Composition du vivier

| Dimension | Valeurs distinctes | Occurrences | Profils | Couverture | Dispersion |
| --- | ---: | ---: | ---: | ---: | ---: |
| Langues | 29 | 3 144 | 1 494 | 88 % | 1 % |
| Compétences | 13 734 | 45 226 | 1 565 | 92 % | 30 % |
| Titres de formation | 1 885 | 4 061 | 1 497 | 88 % | 46 % |
| Écoles | 2 365 | 4 496 | 1 567 | 92 % | 53 % |
| Intitulés de poste | 7 137 | 9 953 | 1 640 | 97 % | 72 % |
| Diplômes | 92 | 125 | 37 | 2 % | 74 % |
| Domaines d'études | 84 | 96 | 34 | 2 % | 88 % |
| Sociétés *(échantillon 100 profils)* | 445 | 555 | 98 | 98 % | 80 % |

## Lecture

**Deux dimensions sont renseignées sur une part marginale des profils.** Les diplômes et les domaines
d'études couvrent 2 % du vivier : la qualification du parcours académique passe par les **titres de
formation**, renseignés sur 88 % des profils.

**Les langues forment un vocabulaire fermé.** Vingt-neuf valeurs pour 3 144 occurrences : les mêmes
codes reviennent d'un profil à l'autre, et la liste complète tient sur un écran. Le français, l'anglais
et l'arabe couvrent l'essentiel du vivier, devant l'espagnol et le néerlandais.

**Les compétences se répètent suffisamment pour former des familles.** Trente pour cent de dispersion :
une compétence sur trois est propre à un profil, les deux autres reviennent ailleurs. Les termes les
plus portés — communication, gestion de projet, bureautique, anglais, management — se retrouvent sur
plusieurs centaines de profils chacun.

**Les intitulés de poste et les sociétés sont les dimensions les plus dispersées.** Respectivement 72 %
et 80 % : la quasi-totalité des valeurs n'apparaît qu'une fois. Un intitulé de poste porte le plus
souvent des précisions propres à l'employeur, et chaque profil cite des entreprises que les autres ne
citent pas.

**Le vocabulaire est bilingue.** Un même concept s'exprime en français et en anglais dans des
proportions comparables : `gestion de projet` et `project management`, `travail d'équipe` et
`teamwork`, `anglais` et `english` figurent tous parmi les valeurs les plus fréquentes.

## Regroupement du vocabulaire

Les valeurs les plus portées se regroupent en **familles** : plusieurs libellés désignent un même
concept, et une recherche portant sur l'un d'eux concerne les autres.

| | Familles | Libellés | Libellés par famille | Part des mentions |
| --- | ---: | ---: | ---: | ---: |
| Compétences | 70 | 156 | 2,2 | 28 % |
| Postes | 27 | 109 | 4,0 | 16 % |

Quarante-six des soixante-dix familles de compétences réunissent au moins deux écritures, et
vingt-trois des vingt-sept familles de postes. Les autres ne portent qu'un seul libellé.

Les familles de compétences les plus portées sont la gestion de projet (782 mentions), la
communication (585), le travail d'équipe (520), l'anglais (515) et la bureautique Microsoft (446).
Côté postes viennent le stage (482), le développeur logiciel (130), le chef de projet (103), le
dirigeant ou fondateur (84) et le commercial (81). Le stage arrive en tête des intitulés : il décrit
un statut et non un métier.

Le regroupement réunit les écritures française et anglaise d'un même concept — `gestion de projet`
avec `project management`, `travail d'équipe` avec `teamwork`. Il réunit aussi les formulations
concurrentes d'un même poste : une seule famille rassemble `software engineer`, `développeur web`,
`full stack developer`, `web developer` et `ingénieur logiciel`, soit onze écritures.

Trente-et-une familles correspondent à une entrée du référentiel européen ESCO : quatorze pour les
postes, dix-sept pour les compétences.

## Qualifications portées implicitement

Trois qualifications ne sont pas des champs du profil mais se lisent dans les données existantes.

| Qualification | Valeurs | Profils | Couverture | Se lit dans |
| --- | ---: | ---: | ---: | --- |
| Séniorité | 4 | 1 203 | 71 % | les intitulés de poste |
| Niveau d'études | 6 | 1 335 | 79 % | les titres de formation |
| Secteur d'activité *(échantillon 100 profils)* | 18 | 59 | 59 % | les sociétés |

**La séniorité** se lit dans l'intitulé lui-même : encadrement, stage, senior, junior. L'encadrement
et le stage dominent largement.

**Le niveau d'études** se lit dans le titre de la formation : master et MBA en tête, puis licence,
diplôme d'ingénieur, baccalauréat, bac+2 et doctorat.

**Le secteur d'activité** se lit dans le nom de l'employeur. Il transforme la dimension la plus
dispersée du vivier en la plus resserrée : dix-huit secteurs là où les sociétés comptent 445 valeurs.
Conseil et audit, informatique, transport et logistique, industrie et banque arrivent en tête.

## Écriture des valeurs

Les écarts d'écriture entre deux valeurs d'un même concept relèvent de deux natures.

Les **variantes d'accent** portent sur 110 valeurs de l'ensemble compétences et intitulés :
`législation` et `legislation`, `stratégie` et `strategie`, `développeur java` et `developpeur java`
désignent la même chose. Elles se rapprochent par comparaison insensible aux accents.

Les **écarts d'une seule lettre** ne se rapprochent pas de la même façon. Sur des termes techniques
courts, une lettre distingue deux choses différentes : `scss` et `css` sont deux technologies, `ssas`
et `sas` deux produits distincts. Le rapprochement automatique sur cette base confond des valeurs
qu'il faut garder séparées.

Les fautes d'écriture représentent une part réduite de la dispersion : 2,4 % des compétences et 1,1 %
des intitulés. La dispersion tient à la diversité réelle des formulations.
