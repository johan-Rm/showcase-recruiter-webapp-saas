<script setup lang="ts">
// Layout des écrans hors session : pas de header applicatif — la navigation n'a rien à proposer
// tant qu'on n'est pas connecté. La page devient une carte flottante unique, formulaire d'un côté
// et panneau de marque de l'autre. Sous `lg`, le panneau disparaît : le formulaire seul.
//
// Aucun contrôle de langue ni de thème : l'écran ne porte que l'acte à accomplir. Les deux
// restent atteignables sans eux — la locale se résout par URL, cookie puis navigateur, et le
// thème suit le système. Les rétablir ici, c'est rendre à l'écran le bruit qu'on lui a retiré.
//
// Le côté du panneau se lit dans l'état d'inscription, que la page porte en query : la coque n'a
// rien à écouter. Elle passe par le composable et non par la query brute, sinon un `?inscription`
// tapé à la main ferait glisser l'image sur un espace qui n'ouvre aucun tunnel.
const { registering: mediaFirst } = useRegistration()
</script>

<template>
  <!-- Hauteur EXACTE de la fenêtre, et non « au moins » : c'est ce seul mot qui manquait, et qui a
       laissé l'écran déborder dès qu'on lui a ajouté deux boutons. Ce qui cède quand la place manque,
       ce sont les blancs — cf. la règle de resserrement dans le style ci-dessous. -->
  <div class="auth-shell h-svh bg-bg">
    <div
      class="flex h-svh border border-line bg-surface p-element shadow-xl shadow-black/5 dark:shadow-black/40"
    >
      <div
        class="auth-split grid w-full gap-element lg:grid-cols-2"
        :class="{ 'auth-split--swapped': mediaFirst }"
      >
        <!-- LE REPÈRE PRINCIPAL, sur le panneau du formulaire : c'est lui le contenu de l'écran,
             l'autre panneau n'étant qu'une image de marque. La `<section>` qu'il remplace ne
             qualifiait rien de plus, et l'écran n'avait aucun point de saut. `min-h-0` neutralise
             le `min-h` de thème d'`UMain` — cf. `layouts/README.md`. -->
        <UMain :ui="{ base: 'min-h-0' }" class="auth-split__pane flex flex-col px-gutter py-pane">
          <!-- Colonne étroite : login et étape e-mail n'ont que 2 champs. `--container-form` (32rem)
               du système vise des formulaires plus fournis ; ici 24rem se lit mieux. À basculer sur un
               token dédié si le design system en ajoute un plus étroit. -->
          <div class="mx-auto my-auto w-full max-w-sm py-section">
            <slot />
          </div>
        </UMain>

        <AuthShowcase class="auth-split__media hidden lg:flex" />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Ce sont les BLANCS qui cèdent, jamais le contenu.
   La coque tient exactement la hauteur de la fenêtre. Quand celle-ci se réduit — portable bas,
   clavier ouvert sur mobile, écran tourné —, les grands écarts SE RABATTENT sur un palier plus
   serré de l'échelle. Aucune valeur n'est écrite ici : on descend l'échelle sémantique, on n'en
   invente pas une parallèle, et les utilitaires (`p-pane`, `py-section`…) ne changent pas.

   Les paliers du système sont fluides sur la LARGEUR (`vw`) — il n'en existe aucun qui suive la
   hauteur, d'où ce rabattement local.

   ⚠️ Le palier CIBLE n'est jamais redéfini dans le même bloc. Les variables CSS se résolvent à
   l'usage, pas à la déclaration : rabattre `section` sur `pane` tout en rabattant `pane` ailleurs
   ferait résoudre `section` vers la valeur finale de `pane`, soit deux crans au lieu d'un.

   Les deux seuils sont des valeurs littérales parce qu'une condition de media query n'accepte pas
   `var()`. Ce sont des points de bascule mesurés, pas des espacements.

   Aucun `overflow: hidden` : si le rabattement ne suffisait pas — trois lignes d'erreur, une langue
   plus verbeuse, un clavier qui mange la moitié de l'écran —, la page redevient défilable. Un écran
   d'entrée qu'on ne peut plus valider est pire qu'un écran qui défile. */
@media (max-height: 46rem) {
  .auth-shell {
    --spacing-section: var(--spacing-block);
    --spacing-pane: var(--spacing-block);
  }
}

@media (max-height: 36rem) {
  .auth-shell {
    --spacing-section: var(--spacing-element);
    --spacing-pane: var(--spacing-element);
    --spacing-block: var(--spacing-element);
  }
}

/* Les deux panneaux échangent leur colonne en glissant. Réservé à `lg` : en dessous, la grille
   n'a qu'une colonne et le panneau est masqué — translater le formulaire l'enverrait hors écran.
   Le déplacement vaut sa propre largeur PLUS l'écart de la grille, sinon les panneaux se
   chevauchent d'autant. Cet écart est un PALIER FLUIDE (`gap-element`), donc il se lit dans sa
   variable : le recopier en dur — c'était `0.5rem`, la valeur de l'ancien `gap-2` — le figeait, et
   les panneaux se décalaient de travers dès que l'écran changeait de taille.
   `prefers-reduced-motion` est déjà couvert par la règle globale de transitions.css : la durée
   tombe à ~0 et l'échange devient instantané, ce qui est le comportement voulu. */
@media (min-width: 1024px) {
  .auth-split__pane,
  .auth-split__media {
    transition: transform var(--transition-slow);
  }

  .auth-split--swapped .auth-split__pane {
    transform: translateX(calc(100% + var(--spacing-element)));
  }

  .auth-split--swapped .auth-split__media {
    transform: translateX(calc(-100% - var(--spacing-element)));
  }

  /* En RTL (arabe) la grille est déjà miroir : le glissement doit s'inverser avec elle, sinon les
     deux panneaux partent du même côté et se superposent. */
  [dir='rtl'] .auth-split--swapped .auth-split__pane {
    transform: translateX(calc(-100% - var(--spacing-element)));
  }

  [dir='rtl'] .auth-split--swapped .auth-split__media {
    transform: translateX(calc(100% + var(--spacing-element)));
  }
}
</style>
