import { parse } from 'vue/compiler-sfc'

/**
 * Vérificateurs structurels — le second type de règle, à côté des `forbid` ligne-à-ligne.
 *
 * Certaines conventions ne se voient pas sur une ligne : le tag racine d'un composant s'étale
 * sur plusieurs lignes dès qu'il a trois classes Tailwind, et un grep qui lirait la première
 * ligne du template raterait le marqueur de `Button.vue` comme de `Badge.vue`. On lit donc
 * l'AST du SFC, que Vue expose déjà (`vue/compiler-sfc`) — aucune dépendance ajoutée.
 */

/** Élément, au sens du compilateur Vue (`NodeTypes.ELEMENT`). */
const ELEMENT = 1

/**
 * Les nœuds de template, dérivés du retour de `parse` plutôt qu'importés.
 *
 * `vue/compiler-sfc` n'expose pas `TemplateChildNode` ni `ElementNode` — ils vivent dans
 * `@vue/compiler-core`, dépendance transitive que rien ne nous garantit. Les lire sur la signature
 * de `parse` donne les mêmes types sans rien ajouter à `package.json`.
 */
type TemplateRoot = NonNullable<
  NonNullable<ReturnType<typeof parse>['descriptor']['template']>['ast']
>
type TemplateChild = TemplateRoot['children'][number]
type TemplateElement = Extract<TemplateChild, { type: typeof ELEMENT }>

export interface StructuralHit {
  line: number
  hit: string
}

export type Checker = (relPath: string, content: string) => StructuralHit[]

const kebab = (value: string): string => value.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase()

/** `base/app/components/auth/LoginForm.vue` → `auth-login-form`. */
const markerOf = (relPath: string): string =>
  kebab(
    relPath
      .slice(relPath.lastIndexOf('components/') + 'components/'.length)
      .replace(/\.vue$/, '')
      .replaceAll('/', '-'),
  )

/**
 * La racine unique du template, ou `null` s'il n'y en a pas à juger.
 *
 * Les fragments sont dispensés — racines multiples, ou racine `<template v-if>` : il n'y a alors
 * aucun élément réel (cf. `auth/Nav.vue`). Les deux vérificateurs de racine partagent cette
 * lecture : deux définitions divergentes de « la racine » se contrediraient sur les mêmes fichiers.
 */
const singleRootOf = (relPath: string, content: string) => {
  const { descriptor } = parse(content, { filename: relPath })
  const roots = (descriptor.template?.ast?.children ?? []).filter((node) => node.type === ELEMENT)
  const root = roots.at(0)

  return roots.length === 1 && root && root.tag !== 'template' ? root : null
}

/**
 * Un composant à racine unique porte son marqueur en première classe.
 */
const componentsRootMarker: Checker = (relPath, content) => {
  const root = singleRootOf(relPath, content)

  if (!root) {
    return []
  }

  const expected = markerOf(relPath)
  const line = root.loc.start.line

  const classAttr = root.props.find((prop) => prop.type === 6 && prop.name === 'class')
  const first =
    classAttr?.type === 6 ? classAttr.value?.content.trim().split(/\s+/).at(0) : undefined

  if (first === expected) {
    return []
  }

  return [
    {
      line,
      hit: first
        ? `<${root.tag} class="${first} …"> au lieu de "${expected}"`
        : `<${root.tag}> sans class statique — attendu "${expected}"`,
    },
  ]
}

/**
 * Balises qui découpent un DOCUMENT — les employer, c'est se déclarer partie du plan de la page.
 *
 * `<header>`, `<footer>` et `<nav>` en sont absents : ils qualifient un CONTENU et gardent leur sens
 * à l'intérieur de n'importe quel bloc. `search/ProgressHeader` est l'en-tête de sa propre colonne,
 * `account/Nav` est une navigation — ni l'un ni l'autre ne prétend structurer la page.
 */
const PAGE_REGIONS = new Set(['section', 'aside', 'main'])

/**
 * La balise de région appartient à celui qui PLACE, pas à celui qui est placé.
 *
 * Un composant qui s'ouvre sur `<section>` s'attribue une région du modèle de page — et emporte avec
 * elle les classes qui décrivent la grille de son parent. La mise en page se retrouve alors à cheval
 * sur deux fichiers, et personne ne l'a en entier : c'est ainsi que les quatre états de la
 * Visionneuse ont cessé de se centrer sans qu'aucune gate ne le voie.
 *
 * Seule la RACINE est jugée : une `<section>` interne découpe le contenu que le composant possède,
 * elle est légitime (`search/ProfileCard` en porte deux).
 */
const componentsNoPageRegion: Checker = (relPath, content) => {
  const root = singleRootOf(relPath, content)

  if (!root || !PAGE_REGIONS.has(root.tag)) {
    return []
  }

  return [{ line: root.loc.start.line, hit: `<${root.tag}> en racine de composant` }]
}

/**
 * Les deux façons d'écrire le repère principal : la balise, et la primitive Nuxt UI qui la rend.
 *
 * `UMain` rend `<main>` par défaut (`as`). Ne compter que la balise nue laisserait la règle aveugle
 * au cas normal, puisque c'est la primitive que le socle emploie. La comparaison est insensible à la
 * casse : `<UMain>` et `<u-main>` désignent le même composant.
 *
 * Limite assumée : un `<UMain as="div">` est compté comme un repère alors qu'il n'en rend plus.
 * L'inspecter demanderait de suivre une prop qui peut être liée à une expression, et personne n'a
 * de raison d'écrire ça — c'est la primitive `<main>` qu'on va chercher en l'appelant.
 */
const MAIN_TAGS = new Set(['main', 'umain'])

/** Les repères principaux du template, à quelque profondeur qu'ils soient. */
const mainsIn = (nodes: TemplateChild[]): TemplateElement[] =>
  nodes.flatMap((node) =>
    node.type !== ELEMENT
      ? []
      : [...(MAIN_TAGS.has(node.tag.toLowerCase()) ? [node] : []), ...mainsIn(node.children)],
  )

/**
 * Les repères d'un fichier — l'AST plutôt qu'un `forbid` ligne à ligne, pour deux raisons.
 *
 * Les commentaires d'abord : le moteur de `forbid` n'écarte que les lignes qui COMMENCENT par un
 * délimiteur, si bien qu'un `<main>` cité au fil d'un commentaire de plusieurs lignes compterait
 * comme du code. Les fichiers de ce dépôt en sont pleins.
 *
 * Le comptage ensuite : « exactement un » ne s'exprime pas en motif interdit. C'est pourtant la
 * règle — deux repères principaux valent zéro, les technologies d'assistance n'en désignant alors
 * plus aucun.
 */
const mainsOf = (relPath: string, content: string): TemplateElement[] => {
  const { descriptor } = parse(content, { filename: relPath })

  return mainsIn(descriptor.template?.ast?.children ?? [])
}

/**
 * Un layout pose le repère principal, et un seul.
 *
 * C'est le layout et non la page parce qu'il y a cinq gabarits pour des dizaines de pages : posé
 * ici, le repère existe sur les écrans qu'on n'a pas encore écrits. Posé dans la page, il manque à
 * la première qu'on oublie — trois espaces sur quatre n'en avaient aucun.
 */
const layoutsSingleMain: Checker = (relPath, content) => {
  const mains = mainsOf(relPath, content)

  if (mains.length === 1) {
    return []
  }

  if (mains.length === 0) {
    return [{ line: 1, hit: 'aucun <main> — le layout doit poser le repère principal' }]
  }

  // Le premier est légitime : ce sont les SUIVANTS qui cassent l'unicité, et c'est leur ligne qu'il
  // faut montrer pour savoir lequel retirer.
  return mains
    .slice(1)
    .map((node) => ({ line: node.loc.start.line, hit: `<${node.tag}> en double dans le layout` }))
}

/** Une page n'écrit pas le repère principal : son layout l'a déjà posé, et il n'y en a qu'un. */
const pagesNoMain: Checker = (relPath, content) =>
  mainsOf(relPath, content).map((node) => ({
    line: node.loc.start.line,
    hit: `<${node.tag}> dans une page — le layout le porte déjà`,
  }))

/**
 * Un palier d'espacement BRUT de Tailwind : `p-1`, `px-4`, `gap-2`, `sm:py-8`, `space-y-3`…
 *
 * Le préfixe de variante est facultatif et non capturé — `sm:py-8` est visé comme `py-8`, sans quoi
 * un point de rupture offrirait une porte de sortie. `p-0` et consorts sont épargnés : remettre à
 * zéro n'est pas choisir une valeur hors échelle, et c'est parfois nécessaire pour défaire un défaut
 * de bibliothèque. Les valeurs arbitraires (`w-[clamp(…)]`) ne sont pas visées ici — l'interdit
 * n°3 du design system les couvre déjà, et les mêler brouillerait deux sujets.
 */
const RAW_SPACING =
  /(?:^|:)(?:p|m)[xytrbles]?-[1-9]\d*(?:\.\d+)?$|(?:^|:)gap(?:-[xy])?-[1-9]\d*(?:\.\d+)?$|(?:^|:)space-[xy]-[1-9]\d*(?:\.\d+)?$/

/** Les classes statiques d'un élément, sans les liaisons — `:class` porte des expressions. */
const staticClassesOf = (node: TemplateElement): string[] => {
  const attr = node.props.find((prop) => prop.type === 6 && prop.name === 'class')

  return attr?.type === 6 ? (attr.value?.content.trim().split(/\s+/) ?? []) : []
}

/** Tous les éléments du template, à quelque profondeur qu'ils soient. */
const elementsIn = (nodes: TemplateChild[]): TemplateElement[] =>
  nodes.flatMap((node) => (node.type !== ELEMENT ? [] : [node, ...elementsIn(node.children)]))

/**
 * Une coque n'improvise pas son rythme : elle emploie l'échelle du socle.
 *
 * Le rythme d'un layout gouverne TOUS les écrans qu'il porte. Un `p-1` dans `dashboard.vue` a fait
 * diverger les trente-deux écrans de l'espace admin pendant des semaines (`ADM-015`) : dans un
 * composant, un palier brut est une tache locale ; dans une coque, c'est un fork.
 *
 * L'AST plutôt qu'un motif interdit, pour la même raison que `layouts-single-main` : le moteur de
 * `forbid` n'écarte que les lignes qui COMMENCENT par un délimiteur, et les templates de ce dépôt
 * citent des classes au fil de commentaires de plusieurs lignes. On lit donc l'attribut `class`,
 * jamais la ligne.
 */
const layoutsNoRawSpacing: Checker = (relPath, content) => {
  const { descriptor } = parse(content, { filename: relPath })

  return elementsIn(descriptor.template?.ast?.children ?? []).flatMap((node) =>
    staticClassesOf(node)
      .filter((cls) => RAW_SPACING.test(cls))
      .map((cls) => ({
        line: node.loc.start.line,
        hit: `<${node.tag} class="… ${cls} …"> — palier brut hors de l'échelle`,
      })),
  )
}

/**
 * Les noms que le kit et Nuxt UI revendiquent TOUS LES DEUX, et que Nuxt UI gagne.
 *
 * Notre nuancier expose `--color-muted` — un gris de TEXTE. Nuxt UI expose de son côté ses propres
 * utilitaires `bg-muted` et `border-muted`, branchés dans `nuxt-ui.css` sur `--surface-soft` et
 * `--line`. Mesuré : c'est Nuxt UI qui l'emporte, et l'écriture rend donc une autre couleur que
 * celle qu'on visait, **sans le dire**.
 *
 * `text-muted` n'y est PAS, et c'est délibéré : Nuxt UI le gagne aussi, mais son `--ui-text-muted`
 * est remappé sur `--muted` — même valeur, aucun écart. L'interdire condamnerait une écriture
 * correcte employée partout dans le produit.
 *
 * Le préfixe de variante est admis : `hover:bg-muted` est le même piège, à un état près.
 */
const AMBIGUOUS_KIT_CLASSES = /(?:^|:)(?:bg|border)-muted$/

/**
 * Un nom qui existe des deux côtés ne se relit pas : il faut le mesurer.
 *
 * Le défaut est SILENCIEUX, et c'est ce qui justifie une règle plutôt qu'un commentaire : le nom
 * demandé existe, il répond, il répond simplement autre chose. Ni la relecture, ni le type-check,
 * ni le lint ne peuvent le voir — la barre d'avancement de la carte de recherche s'est peinte dans
 * la couleur de son propre fond pendant toute la vie d'`ENT-050`, et il a fallu mesurer la page
 * rendue pour s'en apercevoir.
 *
 * L'AST plutôt qu'un motif interdit, pour la raison qui a fait de `layouts-no-raw-spacing` un
 * checker : le commentaire qui explique ce piège cite forcément la classe fautive, et un scan de
 * ligne condamnerait l'explication avec la faute.
 */
const appNoAmbiguousKitClass: Checker = (relPath, content) => {
  const { descriptor } = parse(content, { filename: relPath })

  return elementsIn(descriptor.template?.ast?.children ?? []).flatMap((node) =>
    staticClassesOf(node)
      .filter((cls) => AMBIGUOUS_KIT_CLASSES.test(cls))
      .map((cls) => ({
        line: node.loc.start.line,
        hit: `<${node.tag} class="… ${cls} …"> — ce nom appartient à Nuxt UI`,
      })),
  )
}

/** L'espace d'un fichier de layer — `spaces/admin/app/…` → `admin`. */
const SPACE_SEGMENT = /^spaces\/([^/]+)\//

/**
 * Les deux couches dont le nom de fichier n'est pas un nom d'auto-import.
 *
 * Une page est nommée par sa ROUTE : `pages/inscription/entreprise.vue` est l'adresse
 * `/inscription/entreprise`, et le fichier ne peut pas s'appeler autrement sans déplacer la page. Un
 * layout est nommé par l'identifiant que `definePageMeta({ layout: … })` va chercher. Dans les deux
 * cas, le nom est imposé du dehors — `eslint.config.mjs` le reconnaît déjà en y désactivant la règle
 * de nommage multi-mots.
 */
const ROUTED_LAYERS = ['/app/pages/', '/app/layouts/']

/**
 * Un fichier ne répète pas le nom de son espace.
 *
 * Un checker et non un `forbid` : la chaîne à refuser dépend du CHEMIN du fichier — `Admin` sous
 * `spaces/admin/`, `Public` sous `spaces/public/`. Aucun motif statique ne dit ça, pour la même
 * raison qui a fait de `components-root-marker` un checker.
 *
 * Le jugement porte sur le nom de fichier, jamais sur le contenu : la ligne rapportée est donc
 * toujours la première. Trois formes sont couvertes d'un seul geste — `AdminTopbar.vue` en
 * PascalCase, `useAdminNav.ts` dont on juge ce qui suit `use`, et `adminUsers.ts` en camelCase pour
 * les stores et les utils.
 *
 * **Le préfixe doit être un mot entier**, et c'est ce qui rend la règle vivable : `CandidateCard.vue`
 * passe dans l'espace `candidat` parce que « Candidate » est un mot du métier, pas le nom de
 * l'espace suivi d'autre chose. Sans cette frontière, la règle aurait condamné le vocabulaire de
 * l'espace candidat le jour où il aura des composants.
 */
const appNoSpacePrefix: Checker = (relPath) => {
  const space = SPACE_SEGMENT.exec(relPath)?.[1]

  if (!space || ROUTED_LAYERS.some((layer) => relPath.includes(layer))) {
    return []
  }

  const name = relPath.slice(relPath.lastIndexOf('/') + 1).replace(/\.\w+$/, '')
  const bare = name.startsWith('use') ? name.slice(3) : name

  const repeated = [space, space.charAt(0).toUpperCase() + space.slice(1)].find((prefix) => {
    if (!bare.startsWith(prefix)) {
      return false
    }

    const rest = bare.slice(prefix.length)

    return rest === '' || /^[A-Z]/.test(rest)
  })

  return repeated ? [{ line: 1, hit: `${name} — « ${repeated} » est déjà porté par la layer` }] : []
}

/**
 * Le texte réellement COMMENTÉ, ligne par ligne — l'inverse exact de tous les vérificateurs
 * ci-dessus.
 *
 * Les autres lisent l'AST précisément pour ne jamais juger un commentaire ; celui-ci ne juge que
 * les commentaires. Et il ne peut pas se contenter d'un motif ancré en début de ligne, comme le
 * fait le moteur de `forbid` : c'est cette forme-là qui a laissé sept renvois de tâche dormir au
 * MILIEU de commentaires de six lignes, dans trois espaces qu'un `grep` avait déclarés propres.
 *
 * On suit donc l'ouverture et la fermeture des blocs, `<!--` comme le bloc JS, et on ne retient que
 * ce qui est dedans.
 */
const HTML_OPEN = '<!--'
const HTML_CLOSE = '-->'
const BLOCK_OPEN = '/*'
const BLOCK_CLOSE = '*/'

/**
 * Le premier `//` qui ouvre vraiment un commentaire de ligne.
 *
 * Le `:` qui précède écarte `https://` et consorts — sans lui, la moindre URL citée dans du code
 * ferait passer la fin de sa ligne pour un commentaire.
 */
const lineCommentAt = (source: string): number => {
  for (let at = source.indexOf('//'); -1 !== at; at = source.indexOf('//', at + 2)) {
    if (':' !== source.at(at - 1)) {
      return at
    }
  }

  return -1
}

const commentLinesOf = (content: string): { line: number; text: string }[] => {
  const found: { line: number; text: string }[] = []
  let closing: string | null = null

  content.split('\n').forEach((raw, index) => {
    let rest = raw
    let text = ''

    while (rest.length > 0) {
      if (closing) {
        const end = rest.indexOf(closing)

        if (-1 === end) {
          text += rest
          rest = ''
          continue
        }

        text += rest.slice(0, end)
        rest = rest.slice(end + closing.length)
        closing = null
        continue
      }

      const html = rest.indexOf(HTML_OPEN)
      const block = rest.indexOf(BLOCK_OPEN)
      const line = lineCommentAt(rest)
      const opens = [
        { at: html, open: HTML_OPEN, close: HTML_CLOSE },
        { at: block, open: BLOCK_OPEN, close: BLOCK_CLOSE },
        { at: line, open: '//', close: null },
      ]
        .filter((candidate) => -1 !== candidate.at)
        .sort((a, b) => a.at - b.at)

      const opener = opens.at(0)

      if (!opener) {
        rest = ''
        continue
      }

      if (!opener.close) {
        text += rest.slice(opener.at)
        rest = ''
        continue
      }

      closing = opener.close
      rest = rest.slice(opener.at + opener.open.length)
    }

    if (text.trim()) {
      found.push({ line: index + 1, text })
    }
  })

  return found
}

/**
 * Ce qu'un commentaire ne raconte pas : d'où vient le code, et ce qu'on lui doit encore.
 *
 * Les trois familles sont refusées parce qu'elles sont MÉCANIQUEMENT reconnaissables et qu'aucune
 * n'a de contre-exemple légitime — un numéro de tâche, un mot de chronologie, un marqueur de dette.
 *
 * Les TEMPS du passé en sont volontairement absents, et c'est la seule décision discutable de cette
 * règle : « ce qui a été demandé » est un état, « elle poussait » est un récit, et rien ne les
 * sépare sans lire la phrase. Les refuser condamnerait douze commentaires justes relevés dans les
 * quatre espaces — une règle qui crie au loup finit avec une liste d'exceptions plus longue qu'elle.
 */
const JOURNAL_PATTERNS: readonly { pattern: RegExp; hint: string }[] = [
  { pattern: /\b(?:ENT|WEB|ADM|API|CAN|PUB|OPS|UX)-\d{3}\b/, hint: 'renvoi de tâche' },
  { pattern: /\b(?:t[âa]ches?|tasks?|cartes?)\s+n?°?\s*#?\d{2,4}\b/i, hint: 'renvoi de tâche' },
  {
    pattern: /\b(?:désormais|dorénavant|auparavant|autrefois|jusqu['’](?:ici|alors)|naguère)\b/i,
    hint: 'chronologie',
  },
  { pattern: /\ba (?:vécu|fini par)\b/i, hint: 'chronologie' },
  // PAS de `\b` final : ces participes se terminent tous par `é`, qui n'est pas un caractère de mot
  // pour une regex JS non-Unicode — la frontière ne se forme donc jamais et le motif ne mordait sur
  // aucun d'eux. C'est un test qui l'a montré, pas la relecture.
  {
    pattern: /\ba été (?:retiré|supprimé|remplacé|déplacé|abandonné|renommé)e?s?/i,
    hint: 'chronologie',
  },
  { pattern: /\b(?:TODO|FIXME|HACK|XXX)\b/, hint: 'dette non suivie' },
]

/** Un commentaire décrit un état présent — pas le chemin par lequel le code y est arrivé. */
const appNoCommentJournal: Checker = (_relPath, content) =>
  commentLinesOf(content).flatMap(({ line, text }) =>
    JOURNAL_PATTERNS.flatMap(({ pattern, hint }) => {
      const hit = text.match(pattern)

      return hit ? [{ line, hit: `« ${hit[0]} » — ${hint}` }] : []
    }),
  )

export const CHECKERS: Record<string, Checker> = {
  'components-root-marker': componentsRootMarker,
  'components-no-page-region': componentsNoPageRegion,
  'layouts-single-main': layoutsSingleMain,
  'pages-no-main': pagesNoMain,
  'layouts-no-raw-spacing': layoutsNoRawSpacing,
  'app-no-space-prefix': appNoSpacePrefix,
  'app-no-ambiguous-kit-class': appNoAmbiguousKitClass,
  'app-no-comment-journal': appNoCommentJournal,
}
