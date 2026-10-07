/**
 * Politique de mot de passe SOLIDE, en écho aux règles serveur (`PasswordSetupConsumeInput`). Le
 * front GUIDE — une checklist vivante — mais l'API reste juge. Les deux doivent dire la même chose,
 * d'où un seul endroit ici pour la version front.
 *
 * Règles explicites (et non un score d'entropie) : la checklist peut montrer LAQUELLE manque, et
 * l'utilisateur voit chacune virer au vert. `\p{Lu}`/`\p{Ll}` couvrent les casses accentuées.
 */
export const PASSWORD_MIN_LENGTH = 10

export interface PasswordRule {
  /** Sert de clé i18n (`auth.passwordRule.<key>`) et de `:key` de liste. */
  key: 'length' | 'upper' | 'lower' | 'digit'
  met: boolean
}

export const usePasswordPolicy = (password: MaybeRefOrGetter<string>) => {
  const rules = computed<PasswordRule[]>(() => {
    const value = toValue(password)

    return [
      { key: 'length', met: value.length >= PASSWORD_MIN_LENGTH },
      { key: 'upper', met: /\p{Lu}/u.test(value) },
      { key: 'lower', met: /\p{Ll}/u.test(value) },
      { key: 'digit', met: /\d/.test(value) },
    ]
  })

  const satisfied = computed(() => rules.value.every((rule) => rule.met))

  return { rules, satisfied }
}
