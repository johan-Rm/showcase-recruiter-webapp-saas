import { describe, expect, it } from 'vitest'

import { CHECKERS } from './checkers'

/**
 * La preuve qu'exige le README de `webapp/` : « toute nouvelle règle doit être prouvée par une
 * violation volontaire ». Une règle structurelle a deux façons de cesser de mordre — ne plus voir
 * ce qu'elle refuse, et se mettre à voir ce qu'elle autorise. Les deux sont exigées ici.
 *
 * Appâts en littéraux plutôt qu'en fichiers `.vue` : le vérificateur prend une chaîne, et un vrai
 * fichier se ferait linter et formater — deux exemptions de configuration pour un gain nul.
 */

const REGION = CHECKERS['components-no-page-region']!
const MARKER = CHECKERS['components-root-marker']!
const LAYOUT_MAIN = CHECKERS['layouts-single-main']!
const PAGE_MAIN = CHECKERS['pages-no-main']!
const RAW_SPACING = CHECKERS['layouts-no-raw-spacing']!
const KIT_CLASS = CHECKERS['app-no-ambiguous-kit-class']!

const componentAvec = (racine: string): string => `<template>
  <${racine} class="search-stack min-h-0">
    <p>Profils</p>
  </${racine}>
</template>
`

const FRAGMENT = `<template>
  <section v-if="pending" class="search-stack">Chargement</section>
  <div v-else class="search-stack">Profils</div>
</template>
`

const SECTION_INTERNE = `<template>
  <article class="search-profile-card">
    <section>
      <h2>Son projet</h2>
    </section>
  </article>
</template>
`

describe("components-no-page-region — ce qu'elle refuse", () => {
  it.each(['section', 'aside', 'main'])('refuse <%s> en racine de composant', (racine) => {
    const [violation] = REGION(
      'spaces/entreprise/app/components/search/Stack.vue',
      componentAvec(racine),
    )

    expect(violation?.hit).toBe(`<${racine}> en racine de composant`)
  })

  it('rapporte la ligne de la racine, pas celle du template', () => {
    const [violation] = REGION(
      'spaces/entreprise/app/components/search/Stack.vue',
      componentAvec('section'),
    )

    expect(violation?.line).toBe(2)
  })
})

describe("components-no-page-region — ce qu'elle autorise", () => {
  it.each(['header', 'footer', 'nav'])('épargne <%s>, qui qualifie un contenu', (racine) => {
    // `search/ProgressHeader` est l'en-tête de sa propre colonne, `account/Nav` une navigation :
    // aucun des deux ne prétend structurer la page. Les refuser rendrait la règle inapplicable.
    expect(REGION('base/app/components/layout/AppHeader.vue', componentAvec(racine))).toEqual([])
  })

  it('dispense les fragments, comme le marqueur de racine', () => {
    expect(REGION('spaces/entreprise/app/components/search/Stack.vue', FRAGMENT)).toEqual([])
  })

  it('accepte une <section> interne, qui découpe le contenu du composant', () => {
    expect(
      REGION('spaces/entreprise/app/components/search/ProfileCard.vue', SECTION_INTERNE),
    ).toEqual([])
  })
})

/**
 * Les deux vérificateurs lisent la même racine. L'extraction de cette lecture ne devait rien
 * changer au marqueur — mais une régression y serait INVISIBLE : un `singleRootOf` qui rendrait
 * toujours `null` ferait sortir la règle verte partout. D'où ces deux cas, qui exigent qu'elle
 * morde encore.
 */
describe('components-root-marker — la lecture de racine partagée', () => {
  it('refuse une racine sans le marqueur attendu', () => {
    // L'appât porte `search-stack`, le chemin attend `search-profile-card` : le décalage est le
    // défaut que la règle existe pour voir.
    const [violation] = MARKER(
      'spaces/entreprise/app/components/search/ProfileCard.vue',
      componentAvec('div'),
    )

    expect(violation?.hit).toContain('search-profile-card')
  })

  it('accepte la racine qui porte le marqueur en première classe', () => {
    expect(
      MARKER('spaces/entreprise/app/components/search/Stack.vue', componentAvec('section')),
    ).toEqual([])
  })
})

/**
 * Le repère principal — `layouts-single-main` et `pages-no-main`.
 *
 * Les deux lisent le même AST : une régression du parcours les ferait sortir vertes ENSEMBLE, l'une
 * en ne voyant plus l'absence, l'autre en ne voyant plus la présence. D'où des cas dans les deux
 * sens pour chacune.
 */
const LAYOUT = 'base/app/layouts/default.vue'
const PAGE = 'spaces/entreprise/app/pages/recherches/[uuid]/index.vue'

const SANS_MAIN = `<template>
  <div>
    <LayoutAppHeader />
    <slot />
  </div>
</template>
`

const UN_MAIN_NU = `<template>
  <div>
    <main class="container py-6">
      <slot />
    </main>
  </div>
</template>
`

const UN_UMAIN = `<template>
  <div>
    <UMain :ui="{ base: 'min-h-0' }" class="container py-6">
      <slot />
    </UMain>
  </div>
</template>
`

const DEUX_MAIN = `<template>
  <div>
    <main class="container"><slot /></main>
    <UMain class="secondaire"><slot name="aside" /></UMain>
  </div>
</template>
`

// Le piège que la lecture ligne-à-ligne ne saurait pas éviter : le moteur de `forbid` n'écarte que
// les lignes qui COMMENCENT par un délimiteur, et les fichiers de ce dépôt portent des commentaires
// de plusieurs lignes. Ici, `<main>` est cité en continuation — c'est du texte, pas du code.
const MAIN_EN_COMMENTAIRE = `<template>
  <div>
    <!-- Le repère principal appartient au layout : une page qui écrirait
         un <main> ici en produirait un second. -->
    <section><slot /></section>
  </div>
</template>
`

describe("layouts-single-main — ce qu'elle refuse", () => {
  it('refuse un layout sans repère principal', () => {
    const [violation] = LAYOUT_MAIN(LAYOUT, SANS_MAIN)

    expect(violation?.hit).toContain('aucun <main>')
  })

  it('refuse le SECOND repère, et pointe sa ligne — pas celle du premier', () => {
    // Deux repères valent zéro : aucun n'est plus désigné. C'est le doublon qu'il faut retirer,
    // donc c'est sa ligne que la violation doit montrer.
    const violations = LAYOUT_MAIN(LAYOUT, DEUX_MAIN)

    expect(violations).toHaveLength(1)
    expect(violations[0]?.line).toBe(4)
  })
})

describe("layouts-single-main — ce qu'elle autorise", () => {
  it.each([
    ['la balise nue', UN_MAIN_NU],
    ['la primitive UMain', UN_UMAIN],
  ])('accepte un layout qui pose exactement un repère, par %s', (_, source) => {
    // `UMain` rend `<main>` : ne compter que la balise nue rendrait la règle aveugle au cas normal,
    // puisque c'est la primitive que le socle emploie.
    expect(LAYOUT_MAIN(LAYOUT, source)).toEqual([])
  })
})

describe("pages-no-main — ce qu'elle refuse", () => {
  it.each([
    ['la balise nue', UN_MAIN_NU],
    ['la primitive UMain', UN_UMAIN],
  ])('refuse une page qui écrit le repère, par %s', (_, source) => {
    const [violation] = PAGE_MAIN(PAGE, source)

    expect(violation?.hit).toContain('le layout le porte déjà')
  })
})

describe("pages-no-main — ce qu'elle autorise", () => {
  it('épargne une page sans repère, qui garde ses <section>', () => {
    expect(PAGE_MAIN(PAGE, SANS_MAIN)).toEqual([])
  })

  it('épargne un <main> cité dans un commentaire', () => {
    // L'AST classe les commentaires à part : ce cas est gratuit ici, et il ne l'aurait pas été avec
    // un motif interdit. Le test verrouille ce choix — un retour au `forbid` le ferait rougir.
    expect(PAGE_MAIN(PAGE, MAIN_EN_COMMENTAIRE)).toEqual([])
  })
})

/**
 * Le rythme d'une coque — `layouts-no-raw-spacing`.
 *
 * Une règle d'espacement a deux façons de cesser de servir : laisser passer un palier brut, et
 * refuser ce qui est légitime. Les deux sont exigées ici, et la seconde compte autant — une règle
 * qui rougit sur `p-0` ou sur une valeur arbitraire commentée se fait désactiver.
 */
const coqueAvec = (classes: string): string => `<template>
  <div class="${classes}">
    <slot />
  </div>
</template>
`

const COQUE_CONFORME = `<template>
  <div class="flex h-dvh flex-col gap-element overflow-hidden p-element">
    <header class="flex-none px-block py-pane"><slot name="header" /></header>
    <main class="min-h-0 flex-1"><slot /></main>
  </div>
</template>
`

// Le piège que la lecture ligne-à-ligne ne saurait pas éviter : un palier cité dans un commentaire
// de template, en continuation — la ligne ne commence pas par le délimiteur.
const PALIER_EN_COMMENTAIRE = `<template>
  <div class="p-element">
    <!-- Le déplacement vaut sa propre largeur PLUS la gouttière
         (l'ancien \`gap-2\` valait 0.5rem), sinon les panneaux se chevauchent. -->
    <slot />
  </div>
</template>
`

describe("layouts-no-raw-spacing — ce qu'elle refuse", () => {
  it.each(['p-1', 'px-4', 'py-6', 'mt-4', 'gap-2', 'gap-x-3', 'space-y-3', 'mb-1.5'])(
    'refuse le palier brut %s',
    (palier) => {
      const [violation] = RAW_SPACING('base/app/layouts/auth.vue', coqueAvec(`flex ${palier}`))

      expect(violation?.hit).toContain(palier)
    },
  )

  it.each(['sm:py-8', 'lg:gap-4', 'dark:p-2'])(
    'refuse la forme préfixée %s — un point de rupture ne doit pas ouvrir de porte',
    (palier) => {
      expect(RAW_SPACING('base/app/layouts/auth.vue', coqueAvec(`flex ${palier}`))).toHaveLength(1)
    },
  )

  it('rapporte un hit par palier, pas un par élément', () => {
    // `auth.vue` en portait quatre sur la même balise : les compter ensemble cacherait trois
    // corrections à faire.
    expect(
      RAW_SPACING('base/app/layouts/auth.vue', coqueAvec('px-4 py-6 sm:px-10 sm:py-8')),
    ).toHaveLength(4)
  })
})

describe("layouts-no-raw-spacing — ce qu'elle autorise", () => {
  it('épargne une coque qui emploie les paliers du socle', () => {
    expect(RAW_SPACING('base/app/layouts/default.vue', COQUE_CONFORME)).toEqual([])
  })

  it.each(['p-0', 'm-0', 'gap-0', 'mt-0'])(
    "épargne %s — remettre à zéro n'est pas choisir une valeur",
    (palier) => {
      expect(RAW_SPACING('base/app/layouts/auth.vue', coqueAvec(`flex ${palier}`))).toEqual([])
    },
  )

  it.each(['w-[clamp(220px,18vw,272px)]', 'h-[100dvh]', 'p-[3px]'])(
    'épargne la valeur arbitraire %s, couverte ailleurs',
    (classe) => {
      expect(RAW_SPACING('spaces/admin/app/layouts/dashboard.vue', coqueAvec(classe))).toEqual([])
    },
  )

  it('épargne un palier cité dans un commentaire de template', () => {
    // L'AST classe les commentaires à part : gratuit ici, impossible avec un motif interdit.
    expect(RAW_SPACING('base/app/layouts/auth.vue', PALIER_EN_COMMENTAIRE)).toEqual([])
  })

  it("épargne ce qui n'est pas de l'espacement — grille, largeur, typographie", () => {
    expect(
      RAW_SPACING(
        'base/app/layouts/auth.vue',
        coqueAvec('grid lg:grid-cols-2 max-w-sm text-sm z-10'),
      ),
    ).toEqual([])
  })
})

/**
 * `app-no-space-prefix` ne juge que le CHEMIN — le contenu n'a qu'à exister. Les appâts sont donc
 * des chemins, et c'est le seul vérificateur du fichier dont le second argument est indifférent.
 */
const SPACE_PREFIX = CHECKERS['app-no-space-prefix']!

const PEU_IMPORTE = `<template>
  <div class="peu-importe" />
</template>
`

describe("app-no-space-prefix — ce qu'elle refuse", () => {
  it('refuse un composant de coque qui reprend le nom de son espace', () => {
    const [violation] = SPACE_PREFIX('spaces/admin/app/components/AdminTopbar.vue', PEU_IMPORTE)

    expect(violation?.hit).toBe('AdminTopbar — « Admin » est déjà porté par la layer')
  })

  it('rapporte la première ligne — le fautif est le nom du fichier, pas une ligne de code', () => {
    const [violation] = SPACE_PREFIX('spaces/admin/app/components/AdminTopbar.vue', PEU_IMPORTE)

    expect(violation?.line).toBe(1)
  })

  it('refuse un composable préfixé, en jugeant ce qui suit `use`', () => {
    const [violation] = SPACE_PREFIX('spaces/admin/app/composables/useAdminNav.ts', PEU_IMPORTE)

    expect(violation?.hit).toBe('useAdminNav — « Admin » est déjà porté par la layer')
  })

  it('refuse un store en camelCase, dont le préfixe est en minuscules', () => {
    const [violation] = SPACE_PREFIX('spaces/admin/app/stores/adminUsers.ts', PEU_IMPORTE)

    expect(violation?.hit).toBe('adminUsers — « admin » est déjà porté par la layer')
  })

  it("refuse le nom qui EST celui de l'espace, sans rien après", () => {
    expect(SPACE_PREFIX('spaces/public/app/components/Public.vue', PEU_IMPORTE)).toHaveLength(1)
  })
})

describe("app-no-space-prefix — ce qu'elle autorise", () => {
  it('épargne un nom qui ne répète rien', () => {
    expect(SPACE_PREFIX('spaces/admin/app/components/Topbar.vue', PEU_IMPORTE)).toEqual([])
  })

  it("épargne un mot du métier qui commence comme l'espace — la frontière de mot", () => {
    // Sans elle, la règle condamnerait le vocabulaire de l'espace candidat : après « Candidat »
    // vient un « e » minuscule, donc « Candidate » est un mot, pas un préfixe suivi d'autre chose.
    expect(SPACE_PREFIX('spaces/candidat/app/components/CandidateTable.vue', PEU_IMPORTE)).toEqual(
      [],
    )
  })

  it('épargne une page, dont le nom de fichier est une adresse', () => {
    // `/inscription/entreprise` : renommer le fichier déplacerait la page.
    expect(
      SPACE_PREFIX('spaces/entreprise/app/pages/inscription/entreprise.vue', PEU_IMPORTE),
    ).toEqual([])
  })

  it("épargne un layout, dont le nom est l'identifiant que definePageMeta va chercher", () => {
    expect(SPACE_PREFIX('spaces/admin/app/layouts/admin.vue', PEU_IMPORTE)).toEqual([])
  })

  it("épargne le socle, qui n'appartient à aucun espace", () => {
    expect(SPACE_PREFIX('base/app/components/layout/AppHeader.vue', PEU_IMPORTE)).toEqual([])
  })
})

/**
 * Un composant d'appât pour la règle des noms ambigus (task WEB-014).
 *
 * Racines multiples volontaires : la règle du marqueur de racine dispense les fragments, ce qui
 * évite de faire tomber deux règles sur le même appât.
 */
const kitAvec = (classes: string): string => `<template>
  <span class="${classes}">gris</span>
  <em class="text-body">texte</em>
</template>
`

describe("app-no-ambiguous-kit-class — ce qu'elle refuse", () => {
  it.each(['bg-muted', 'border-muted'])('refuse %s, que Nuxt UI gagne en silence', (classe) => {
    const [violation] = KIT_CLASS('base/app/components/search/Card.vue', kitAvec(`flex ${classe}`))

    expect(violation?.hit).toContain(classe)
  })

  it.each(['hover:bg-muted', 'sm:border-muted', 'dark:bg-muted', 'group-hover:bg-muted'])(
    'refuse la forme préfixée %s — un état ne doit pas ouvrir de porte',
    (classe) => {
      expect(KIT_CLASS('base/app/components/search/Card.vue', kitAvec(classe))).toHaveLength(1)
    },
  )

  it('rapporte un hit par classe, pas un par élément', () => {
    expect(
      KIT_CLASS('base/app/components/search/Card.vue', kitAvec('bg-muted border-muted')),
    ).toHaveLength(2)
  })

  it('mord dans les quatre espaces, pas seulement dans le socle', () => {
    expect(KIT_CLASS('spaces/admin/app/pages/vivier.vue', kitAvec('bg-muted'))).toHaveLength(1)
  })
})

describe("app-no-ambiguous-kit-class — ce qu'elle autorise", () => {
  it('épargne `text-muted`, la seule forme que Nuxt UI remappe sur la bonne valeur', () => {
    // Mesuré : `--ui-text-muted` vaut `var(--muted)`. L'interdire condamnerait une écriture juste,
    // employée partout dans le produit.
    expect(KIT_CLASS('base/app/components/search/Card.vue', kitAvec('text-muted'))).toEqual([])
  })

  it("épargne l'écriture de remplacement que le message recommande", () => {
    expect(
      KIT_CLASS('base/app/components/search/Card.vue', kitAvec('bg-current text-muted')),
    ).toEqual([])
  })

  it.each(['bg-surface-soft', 'border-line', 'bg-mutedish', 'border-muted-foreground'])(
    'épargne %s — la règle vise deux noms exacts, pas ce qui leur ressemble',
    (classe) => {
      expect(KIT_CLASS('base/app/components/search/Card.vue', kitAvec(classe))).toEqual([])
    },
  )

  it('épargne les deux noms cités dans un commentaire de template', () => {
    // C'est la raison d'être du checker : le commentaire qui EXPLIQUE ce piège cite forcément les
    // classes qu'il dénonce, et un motif interdit par ligne condamnerait l'explication.
    const commentaire = `<template>
  <!-- PAS bg-muted ni border-muted : ces noms appartiennent à Nuxt UI. -->
  <span class="bg-current text-muted">gris</span>
  <em class="text-body">texte</em>
</template>
`

    expect(KIT_CLASS('base/app/components/search/Card.vue', commentaire)).toEqual([])
  })

  it("épargne une liaison `:class`, qu'elle ne sait pas lire", () => {
    // Limite ASSUMÉE : `staticClassesOf` ne lit que l'attribut statique — une expression peut
    // porter n'importe quoi. La règle attrape l'écriture courante, pas toutes les écritures.
    const liaison = `<template>
  <span :class="actif ? 'bg-muted' : ''">gris</span>
  <em class="text-body">texte</em>
</template>
`

    expect(KIT_CLASS('base/app/components/search/Card.vue', liaison)).toEqual([])
  })
})

const JOURNAL = CHECKERS['app-no-comment-journal']!

const FICHIER = 'spaces/entreprise/app/components/search/MatchList.vue'

describe("app-no-comment-journal — ce qu'elle refuse", () => {
  it.each([
    ['// Elle ne retouche rien (`ENT-063`).', 'ENT-063', 'renvoi de tâche'],
    ['// Miroir de l’enum API (6 valeurs, task 112).', 'task 112', 'renvoi de tâche'],
    ['// Santé du vivier (tâche 119) : ce que la base contient.', 'tâche 119', 'renvoi de tâche'],
    ['// Deux régimes assumés (cf. carte 120).', 'carte 120', 'renvoi de tâche'],
    ['// Le rang vit désormais dans la barre haute.', 'désormais', 'chronologie'],
    ['// Il accompagnait le rang, auparavant.', 'auparavant', 'chronologie'],
    ['// Le ✕ a vécu ici pendant deux écrans.', 'a vécu', 'chronologie'],
    ['// Une quatrième case a été retirée.', 'a été retirée', 'chronologie'],
    ['// TODO: brancher le compteur.', 'TODO', 'dette non suivie'],
  ])('refuse %s', (source, motif, famille) => {
    const [violation] = JOURNAL(FICHIER, `<script setup lang="ts">\n${source}\n</script>\n`)

    expect(violation?.hit).toBe(`« ${motif} » — ${famille}`)
  })

  it('voit le MILIEU d’un commentaire HTML de plusieurs lignes', () => {
    // La raison d'être du checker. Le moteur de `forbid` n'écarte — et ne lit — que les lignes qui
    // COMMENCENT par un délimiteur : sept renvois de tâche ont dormi en troisième ligne de
    // commentaires de six, dans trois espaces qu'un `grep` ancré en début de ligne disait propres.
    const bloc = `<template>
  <!-- UNE SEULE RANGÉE : en 2×2, les cases mangent cent quarante pixels.
       TROIS COLONNES ÉGALES (\`ENT-058\`), et c'est ce qui rend les nombres
       comparables d'un coup d'œil. -->
  <dl class="search-progress-header" />
</template>
`

    const [violation] = JOURNAL(FICHIER, bloc)

    expect(violation).toEqual({ line: 3, hit: '« ENT-058 » — renvoi de tâche' })
  })

  it('voit le milieu d’un bloc JSDoc, dont les lignes commencent par une étoile', () => {
    const jsdoc = `<script setup lang="ts">
/**
 * Les deux documents achetables.
 *
 * Le bouton a été déplacé contre la valeur qu'il débloque.
 */
const ACCES = []
</script>
`

    const [violation] = JOURNAL(FICHIER, jsdoc)

    expect(violation).toEqual({ line: 5, hit: '« a été déplacé » — chronologie' })
  })

  it('rapporte un hit par motif, pas un par ligne', () => {
    const deux = `<script setup lang="ts">
// \`ENT-047\` l'a posé, et il vit désormais ailleurs.
</script>
`

    expect(JOURNAL(FICHIER, deux)).toHaveLength(2)
  })
})

describe("app-no-comment-journal — ce qu'elle autorise", () => {
  it('épargne un fichier sans commentaire', () => {
    expect(
      JOURNAL(FICHIER, '<template>\n  <div class="search-match-list" />\n</template>\n'),
    ).toEqual([])
  })

  it('épargne le CODE qui porte les mêmes motifs', () => {
    // Le miroir de la limite des autres checkers : ceux-là ne lisent jamais un commentaire, celui-ci
    // ne lit que ça. Une chaîne de code citant un numéro de tâche n'est pas du journal.
    const code = `<script setup lang="ts">
const REF = 'ENT-047'
const LIBELLE = 'la tâche 119 du pipeline'
</script>
`

    expect(JOURNAL(FICHIER, code)).toEqual([])
  })

  it('épargne une URL, dont le `//` n’ouvre pas de commentaire', () => {
    const url = `<script setup lang="ts">
const DOC = 'https://exemple.test/ENT-047'
</script>
`

    expect(JOURNAL(FICHIER, url)).toEqual([])
  })

  it.each([
    '// Ce qui a été demandé, le verdict, et ce que le profil porte en face.',
    '// Le bouton qui portait le focus vient de disparaître.',
    '// Les peser pareil obligerait à relire les deux.',
  ])('épargne %s — un temps du passé n’est pas un récit', (source) => {
    expect(JOURNAL(FICHIER, `<script setup lang="ts">\n${source}\n</script>\n`)).toEqual([])
  })

  it('épargne un nombre sans le mot qui en ferait un renvoi', () => {
    const mesure = `<script setup lang="ts">
// La colonne fait 280 pixels, et la bulle en déborde de 7.
</script>
`

    expect(JOURNAL(FICHIER, mesure)).toEqual([])
  })
})
