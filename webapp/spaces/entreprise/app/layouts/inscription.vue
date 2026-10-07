<script setup lang="ts">
// Coque de l'onboarding entreprise, atteinte depuis le magic link.
//
// ZÉRO SCROLL : la règle qui commande. Posée ICI, par la coque, jamais laissée à la bonne volonté
// du contenu. Si un écran déborde, c'est l'écran qu'on allège.
//
// ÎLOT SOMBRE. `class="dark"` en dur : cet écran ne suit PAS le thème de l'utilisateur. Le fond est
// une photo sombre ; le thème sombre apporte un accent bleu pâle et une encre claire. Même parti que le panneau Showcase du
// login. La classe suffit : `themes.css` remappe ses tokens sur `.dark`, pas sur `html.dark`.
//
// Ni langue, ni thème, ni « Se déconnecter » : rien ne doit détourner du seul acte en cours.
// L'abandon reste possible et prévu (cadrage : statut INCOMPLET, l'admin renvoie un magic link) —
// mais il se produit en fermant l'onglet, pas en cliquant un bouton qu'on aurait mis là exprès.
//
// N'existe que dans la build entreprise : seul espace à ouvrir un tunnel d'inscription
// (cf. SPACES_WITH_REGISTRATION).
</script>

<template>
  <!-- `max-h` retiré avec le même motif que dans `cockpit.vue` : aucun ancêtre n'est flex ou grid,
       donc rien ne peut étirer cette boîte au-delà de sa hauteur, et `overflow-hidden` empêche le
       contenu de la pousser. La classe ne pouvait pas mordre — elle faisait croire à une garantie. -->
  <div class="dark inscription-shell relative h-dvh overflow-hidden">
    <!-- Le panneau à gauche, la marque en bas à droite : deux couches sur un fond unique, et non
         deux colonnes. C'est ce qui permet au panneau d'être collé au bord tout en laissant la
         photo vivre entière derrière lui. -->
    <!-- Un seul slot : une page posée par `definePageMeta({ layout })` ne peut pas remplir de slot
         NOMMÉ — il faudrait qu'elle rende `<NuxtLayout>` elle-même. La coque donne donc la boîte,
         et ce qu'elle contient s'y organise. -->
    <!-- Le flou ne fonce PAS : il adoucit la photo sans la cacher — on la devine derrière le panneau.
         C'est l'opacité (72 %) qui tient seule la lisibilité du texte, pas le flou : là où
         `backdrop-filter` manque (vieux Safari sans le préfixe), rien ne casse, et c'est pourquoi on
         ne maintient pas le `-webkit-` à la main. -->
    <!-- LE REPÈRE PRINCIPAL, sur le panneau : c'est lui le contenu de l'écran, la marque en bas à
         droite n'étant qu'une image. La `<section>` qu'il remplace ne qualifiait rien de plus, et
         le tunnel n'avait aucun point de saut. `min-h-0` neutralise le `min-h` de thème d'`UMain`
         — cf. `base/app/layouts/README.md`. -->
    <UMain
      :ui="{ base: 'min-h-0' }"
      class="inscription-shell__panel backdrop-blur-lg backdrop-saturate-[1.15]"
    >
      <slot />
    </UMain>

    <section class="inscription-shell__brand">
      <!-- Le logo est posé sur la PHOTO, pas sur une surface du thème : il reste blanc, comme la
           signature juste dessous. `text-on-accent` semblerait plus juste, mais ce jeton vaut
           `--bg` — il basculerait au sombre avec le thème, alors que la photo, elle, ne bascule pas. -->
      <LayoutLogo class="h-6 w-auto text-white" />

      <!-- La coupe est portée par la TRADUCTION : `{break}` est un slot que chaque langue place où
           sa syntaxe l'exige — le français ne décide pas pour l'arabe. `<i18n-t>` le rend en `<br>`
           réel, sans `v-html`. Une largeur ne pouvait pas faire ce travail : `ch` mesure le « 0 »,
           pas les lettres, donc le nombre juste en français était faux ailleurs. -->
      <i18n-t
        keypath="app.tagline"
        tag="p"
        scope="global"
        class="mt-4 text-[15px]/relaxed text-white/60"
      >
        <template #break><br /></template>
      </i18n-t>
    </section>
  </div>
</template>

<style scoped>
/* L'îlot RE-POSE son encre. `body` a résolu `color: var(--text)` à SON niveau — encre sombre en
   colormode clair — et cette couleur CALCULÉE descend par héritage : le `.dark` de la coque remappe
   bien la variable `--text`, mais pas un `color` déjà figé plus haut. Sans cette ligne, tout texte
   sans couleur propre (le lead, l'aide des champs) reste sombre sur le fond sombre en thème clair.
   Posée sur la coque, elle vaut pour tout ce qu'elle contient — panneau comme marque. */
.inscription-shell {
  color: var(--text);
}

/* La photo porte tout l'écran. Le noir dessous la tient tant qu'elle n'est pas décodée : sans lui,
   le premier écran de l'inscription flasherait en blanc. */
.inscription-shell::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    url('/images/fond-inscription-2.webp') center / cover no-repeat,
    var(--color-slate-950);
}

/* Voile de scène. Il ne se raisonne PAS seul : le panneau pose le sien par-dessus, et les deux se
   composent. Dense des deux côtés, on monterait à ~94 % et la photo disparaîtrait derrière la
   plaque. Le dégradé va donc à l'inverse de l'intuition : il assoit la MARQUE — du blanc posé à
   même la photo, qui n'a rien d'autre pour tenir — et s'efface côté panneau, qui se débrouille. */
.inscription-shell::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    260deg,
    color-mix(in srgb, var(--color-slate-950) 58%, transparent) 0%,
    color-mix(in srgb, var(--color-slate-950) 34%, transparent) 55%,
    color-mix(in srgb, var(--color-slate-950) 22%, transparent) 100%
  );
}

/* Collé au bord de départ, pleine hauteur. `inset-inline-start` et non `left` : en arabe tout le
   miroir se refait seul, sans une règle à maintenir en double. */
.inscription-shell__panel {
  /* Publié en tokens et pas seulement appliqué : la jauge de l'onboarding doit DÉBORDER ce padding
     pour toucher les deux bords. Une propriété personnalisée s'hérite sans se soucier du `scoped`,
     donc le contenu peut la lire — là où des nombres recopiés chez lui se décaleraient en silence le
     jour où le panneau change de gabarit. */
  --panel-pad-block: 40px;
  --panel-pad-inline: 46px;

  position: absolute;
  z-index: 2;
  inset-block: 0;
  inset-inline-start: 0;
  display: flex;
  flex-direction: column;
  /* Le `min()` garde la garde basse : le token a un plancher de 30rem, qui déborderait sous ~560px
     de viewport. Le token décide de la largeur, cette borne l'empêche de sortir de l'écran. */
  width: min(var(--container-panel), calc(100vw - 80px));
  padding: var(--panel-pad-block) var(--panel-pad-inline);
  background: color-mix(in srgb, var(--color-slate-950) 72%, transparent);
  /* Sans décalage : collé au bord, le panneau n'a qu'un côté libre — celui qui touche la photo. Une
     ombre portée horizontale pointerait du mauvais côté en RTL (`box-shadow` n'a pas d'équivalent
     logique) ; sans décalage, elle reste juste dans les deux sens. */
  box-shadow: 0 0 60px color-mix(in srgb, var(--color-slate-950) 55%, transparent);
}

.inscription-shell__brand {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: flex-end;
  height: 100%;
  padding: 40px 42px;
  /* La marque s'arrête avant le panneau, sinon elle passerait dessous. La réserve est DÉRIVÉE de sa
     largeur — recopiée à la main, elle se décalait dès que le panneau changeait de gabarit, ce qui
     est arrivé en le rendant fluide. Les 5rem sont l'écart voulu entre les deux, rien d'autre. */
  padding-inline-start: calc(var(--container-panel) + 5rem);
  text-align: end;
}

/* Sous ~940 px, la marque n'a plus de quoi tenir une ligne : elle s'efface et le panneau prend
   toute la largeur. */
@media (max-width: 940px) {
  .inscription-shell__brand {
    display: none;
  }
}
</style>
