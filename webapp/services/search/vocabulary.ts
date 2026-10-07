/**
 * Ce que le dictionnaire de métiers reconnaît — vu depuis le champ de saisie.
 *
 * Métier pur : la fonction reçoit ce que le champ sait déjà et rend un verdict. Elle ne connaît ni
 * l'état réactif, ni le réseau ; c'est l'appelant qui a interrogé.
 */

/** Ce que le champ observe d'une interrogation du vocabulaire, à un instant donné. */
export interface JobTermLookup {
  /** Le contenu du champ, tel qu'il est. */
  term: string
  /** Les propositions revenues pour ce terme. */
  suggestions: readonly { value: string }[]
  /** L'interrogation est-elle encore en vol ? */
  loading: boolean
  /** A-t-elle échoué ? */
  failed: boolean
  /** En deçà de cette longueur, aucune interrogation n'est même tentée. */
  minChars: number
}

/**
 * Ce terme ne désigne-t-il **aucun** métier connu ?
 *
 * POURQUOI C'EST UTILE. Le filtre par métier exige une correspondance exacte : un intitulé hors
 * dictionnaire ramène zéro profil quoi qu'il y ait dans le vivier. L'écran écrivait alors « Aucun
 * profil ne correspond », qui décrit une autre situation — le vivier est vide sur ces critères — et
 * laissait croire au recruteur qu'il avait interrogé le vivier, alors qu'on n'avait pas compris sa
 * question.
 *
 * ⚠️ LE SIGNAL EST LA LISTE VIDE, PAS « le terme n'est pas dans la liste ». La règle qui vient à
 * l'esprit est fausse et mérite d'être écrite pour que personne ne la réinvente : « développeur » est
 * un libellé VARIANTE du métier « Développeur logiciel ». Le filtre l'accepte — il ramène bien ses
 * profils — mais les propositions n'affichent que les noms **canoniques**, donc « développeur » n'y
 * figure pas. Cette règle-là le déclarerait inconnu à tort.
 *
 * L'implication solide va dans l'autre sens : si la liste revient **entièrement vide**, alors aucun
 * métier ni aucune de ses variantes ne porte ce mot, et l'égalité stricte du filtre échouera à coup
 * sûr. Quand la liste est non vide mais que le terme libre n'en fait pas partie, on ne dit rien : on
 * préfère se taire que se tromper.
 *
 * TROIS SITUATIONS QUI NE SONT PAS « INCONNU », et qu'il ne faut pas confondre avec elle :
 * l'interrogation a échoué — une panne ne prouve rien sur le mot —, elle est encore en vol — une
 * liste vide y est une liste pas encore arrivée —, ou le terme est trop court pour qu'on ait même
 * demandé.
 */
export const isUnknownJobTerm = ({
  term,
  suggestions,
  loading,
  failed,
  minChars,
}: JobTermLookup): boolean =>
  term.trim().length >= minChars && !loading && !failed && 0 === suggestions.length
