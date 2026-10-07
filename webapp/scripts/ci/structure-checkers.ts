import { parse } from 'vue/compiler-sfc'

import type { Checker, StructuralHit } from './checkers'

const ELEMENT = 1
const KEBAB = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const INLINE_TAGS = new Set([
  'a',
  'abbr',
  'b',
  'br',
  'code',
  'em',
  'i',
  'img',
  'label',
  'small',
  'span',
  'strong',
  'svg',
])

type TemplateRoot = NonNullable<
  NonNullable<ReturnType<typeof parse>['descriptor']['template']>['ast']
>
type TemplateChild = TemplateRoot['children'][number]
type TemplateElement = Extract<TemplateChild, { type: typeof ELEMENT }>

const singleRootOf = (relPath: string, content: string): TemplateElement | null => {
  const { descriptor } = parse(content, { filename: relPath })
  const roots = (descriptor.template?.ast?.children ?? []).filter(
    (node): node is TemplateElement => node.type === ELEMENT,
  )

  // La racine est liée avant d'être testée : `roots.length === 1` n'apprend rien au compilateur sur
  // `roots[0]`, que le typage strict des accès indexés rend `… | undefined`. Le replier en
  // `roots[0]` ramènerait l'erreur.
  const [root] = roots

  return root && roots.length === 1 && root.tag !== 'template' ? root : null
}

const staticAttrOf = (node: TemplateElement, name: string): string | undefined => {
  const attr = node.props.find((prop) => prop.type === 6 && prop.name === name)

  return attr?.type === 6 ? attr.value?.content : undefined
}

const hasDynamicAttr = (node: TemplateElement, name: string): boolean =>
  node.props.some(
    (prop) =>
      prop.type === 7 && prop.name === 'bind' && prop.arg?.type === 4 && prop.arg.content === name,
  )

const elementsIn = (nodes: TemplateChild[]): TemplateElement[] =>
  nodes.flatMap((node) => (node.type !== ELEMENT ? [] : [node, ...elementsIn(node.children)]))

const routedPathOf = (relPath: string, layer: 'pages' | 'layouts'): string => {
  const marker = `/app/${layer}/`
  const start = relPath.indexOf(marker)

  return start === -1 ? '' : relPath.slice(start + marker.length).replace(/\.vue$/, '')
}

const normalizeSegment = (segment: string): string =>
  segment
    .replace(/^\[\.\.\.(.+)]$/, '$1')
    .replace(/^\[(.+)]$/, '$1')
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .toLowerCase()

const pageMarkerOf = (relPath: string): string =>
  routedPathOf(relPath, 'pages').split('/').map(normalizeSegment).join('-')

const layoutMarkerOf = (relPath: string): string =>
  routedPathOf(relPath, 'layouts').split('/').map(normalizeSegment).join('-')

const rootMarker = (
  relPath: string,
  content: string,
  attr: 'data-page' | 'data-layout',
  expected: string,
): StructuralHit[] => {
  const root = singleRootOf(relPath, content)

  if (!root) {
    return [{ line: 1, hit: `racine unique absente — impossible de porter ${attr}` }]
  }

  if (hasDynamicAttr(root, attr)) {
    return [{ line: root.loc.start.line, hit: `${attr} dynamique — attendu "${expected}"` }]
  }

  const actual = staticAttrOf(root, attr)

  if (actual === expected && KEBAB.test(actual)) {
    return []
  }

  return [
    {
      line: root.loc.start.line,
      hit: `${attr}=${actual ? `"${actual}"` : 'absent'} — attendu "${expected}"`,
    },
  ]
}

const pagesRootMarker: Checker = (relPath, content) =>
  rootMarker(relPath, content, 'data-page', pageMarkerOf(relPath))

const layoutsRootMarker: Checker = (relPath, content) =>
  rootMarker(relPath, content, 'data-layout', layoutMarkerOf(relPath))

const pagesNativeRoot: Checker = (relPath, content) => {
  const root = singleRootOf(relPath, content)

  if (!root) {
    return [{ line: 1, hit: 'racine HTML unique attendue' }]
  }

  const isNative = root.tag === root.tag.toLowerCase() && !root.tag.includes('-')

  return isNative
    ? []
    : [
        {
          line: root.loc.start.line,
          hit: `<${root.tag}> en racine — la composition de page est cachée derrière un composant`,
        },
      ]
}

const structureSectionSemantics: Checker = (relPath, content) => {
  const { descriptor } = parse(content, { filename: relPath })
  const nodes = elementsIn(descriptor.template?.ast?.children ?? [])
  const seen = new Map<string, number>()
  const hits: StructuralHit[] = []

  for (const node of nodes) {
    if (hasDynamicAttr(node, 'data-section')) {
      hits.push({
        line: node.loc.start.line,
        hit: 'data-section dynamique — une zone structurelle doit avoir un nom statique',
      })
      continue
    }

    const section = staticAttrOf(node, 'data-section')

    if (!section) {
      continue
    }

    if (!KEBAB.test(section)) {
      hits.push({
        line: node.loc.start.line,
        hit: `data-section="${section}" — attendu kebab-case`,
      })
    }

    if (INLINE_TAGS.has(node.tag.toLowerCase())) {
      hits.push({
        line: node.loc.start.line,
        hit: `data-section="${section}" sur <${node.tag}> inline/décoratif`,
      })
    }

    const previous = seen.get(section)
    if (previous !== undefined) {
      hits.push({
        line: node.loc.start.line,
        hit: `data-section="${section}" dupliqué — déjà déclaré ligne ${previous}`,
      })
    } else {
      seen.set(section, node.loc.start.line)
    }
  }

  return hits
}

export const STRUCTURE_CHECKERS: Record<string, Checker> = {
  'pages-native-root': pagesNativeRoot,
  'pages-root-marker': pagesRootMarker,
  'layouts-root-marker': layoutsRootMarker,
  'structure-section-semantics': structureSectionSemantics,
}
