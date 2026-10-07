<script setup lang="ts">
// Champ sans cadre : un mot souligné qu'on écrit soi-même. Il ne se nomme PAS lui-même — c'est le
// `<label for>` de l'appelant qui le fait. Un `aria-label` ici écraserait ce libellé visible par une
// seconde source de vérité, et les deux dériveraient.
interface Props {
  /** Lie le `<label for>` de l'appelant : c'est LUI qui nomme le champ, pas un `aria-label` ici. */
  id: string
  placeholder?: string
  /** L'aide vit chez l'appelant, mais l'attribut doit atterrir sur LE CONTRÔLE : c'est lui que le
   *  lecteur d'écran annonce. */
  describedBy?: string
  /** Cap natif de longueur, miroir du `maxLength` OpenAPI : le navigateur bloque au-delà, sans JS
   *  ni message. On n'atteint cette borne qu'au collage, jamais à la frappe. */
  maxlength?: number
}

const { id, placeholder = '', describedBy, maxlength } = defineProps<Props>()

const value = defineModel<string>({ required: true })
</script>

<template>
  <input
    :id="id"
    v-model="value"
    type="text"
    class="ui-inline-input"
    :placeholder="placeholder"
    :maxlength="maxlength"
    :aria-describedby="describedBy"
    autocomplete="off"
  />
</template>

<style scoped>
/* Réponse en texte neutre, pas en accent : l'accent est réservé à ce qui appelle un geste (le champ
   ACTIF, « Continuer ») et à ce qui mesure (jauge, pourcentage). Peindre chaque valeur saisie en accent
   faisait briller tout le formulaire ; ici le trait ne s'avive qu'au focus — l'accent DÉSIGNE alors le
   champ où l'on est. Filet neutre au repos, à peine renforcé au survol.

   UNE EXCEPTION EXISTE, et elle est locale : la phrase de recherche
   (`spaces/entreprise/…/search/Sentence.vue`) repeint SES valeurs en accent, par-dessus ce défaut.
   Elle le peut parce qu'elle n'est pas un formulaire — sa grammaire fait que les trois valeurs SONT
   le contenu, et le fixe l'entoure. La règle ci-dessus tient partout ailleurs : elle vise un écran
   de champs étiquetés, où rien ne distingue une valeur d'un libellé. */
.ui-inline-input {
  width: 100%;
  padding: 0 0.12em;
  border: 0;
  border-bottom: 1px solid var(--line);
  border-radius: 0;
  background: none;
  color: var(--text);
  font: inherit;
  transition:
    border-color var(--transition-fast),
    background-color var(--transition-fast);
}

.ui-inline-input::placeholder {
  color: var(--muted);
  opacity: 0.7;
}

.ui-inline-input:hover {
  border-bottom-color: color-mix(in srgb, var(--text) 40%, transparent);
}

/* Le focus ne peut pas être un contour : le champ n'a pas de cadre. Il s'annonce par un voile
   d'accent très dilué — 5 % et non les 8 % de `--sl-hover` : ce state layer est calibré pour des
   chips posées sur une surface, pas pour une bande pleine largeur, où la même valeur devient un
   aplat qui se voit plus que le texte qu'il porte. */
.ui-inline-input:focus-visible {
  outline: none;
  border-bottom-color: var(--accent);
  background: color-mix(in srgb, var(--accent) 5%, transparent);
}
</style>
