import { describe, expect, it } from 'vitest'

import { regionOf } from './regions'

/**
 * La preuve que le README de `webapp/` exigeait depuis l'origine : « toute nouvelle règle doit être
 * prouvée par une violation volontaire — une gate qui ne mord jamais est pire qu'inutile, elle
 * rassure sans protéger ».
 *
 * Les appâts sont écrits ICI, en littéraux, plutôt que dans des fichiers `.vue` : `regionOf` prend
 * une chaîne, un vrai fichier n'apporterait rien de plus — et il se ferait linter et formater, ce
 * qui suppose de l'exempter dans deux configurations pour un gain nul.
 */

/**
 * Le cas qui a échappé au vérificateur pendant toute sa vie : un slot nommé ferme un `</template>`
 * AVANT la fin du template racine. La régionalisation s'y arrêtait, et tout ce qui suit — ici le
 * `v-model` interdit — n'était jamais lu.
 */
const PAGE_AVEC_SLOT_NOMME = `<script setup lang="ts">
const open = ref(false)
</script>

<template>
  <ActionBar>
    <template #end>
      <UButton>Affiner</UButton>
    </template>
  </ActionBar>

  <USlideover v-model:open="open" />
</template>
`

const PAGE_SANS_SLOT = `<template>
  <div>
    <UButton>Affiner</UButton>
  </div>
</template>
`

describe('regionOf — template', () => {
  it('lit au-delà du premier </template> fermé par un slot nommé', () => {
    const { text } = regionOf(PAGE_AVEC_SLOT_NOMME, 'template')

    expect(text).toContain('v-model:open')
  })

  it('rapporte la ligne réelle du fichier, pas celle de la région', () => {
    const { text, offset } = regionOf(PAGE_AVEC_SLOT_NOMME, 'template')
    const index = text.split('\n').findIndex((line) => line.includes('v-model:open'))

    // Même arithmétique que le vérificateur : offset + index + 1.
    const reported = offset + index + 1
    const actual =
      PAGE_AVEC_SLOT_NOMME.split('\n').findIndex((line) => line.includes('v-model:open')) + 1

    expect(reported).toBe(actual)
  })

  it('lit un template sans slot nommé, comme avant', () => {
    const { text } = regionOf(PAGE_SANS_SLOT, 'template')

    expect(text).toContain('Affiner')
  })

  it("n'ignore pas un bloc qui ouvre le fichier", () => {
    // Le piège que le commentaire d'origine signalait : `index === 0` est une position valide.
    const { text } = regionOf(PAGE_SANS_SLOT, 'template')

    expect(text.trim()).not.toBe('')
  })

  it('rend une région vide quand le bloc est absent, sans lever', () => {
    expect(regionOf('const a = 1\n', 'template')).toEqual({ text: '', offset: 0 })
  })
})

describe('regionOf — script et tout le fichier', () => {
  it('isole le bloc script', () => {
    const { text } = regionOf(PAGE_AVEC_SLOT_NOMME, 'script')

    expect(text).toContain('const open')
    expect(text).not.toContain('USlideover')
  })

  it('rend le fichier entier sans décalage', () => {
    const content = 'const a = 1\n'

    expect(regionOf(content, 'all')).toEqual({ text: content, offset: 0 })
  })
})
