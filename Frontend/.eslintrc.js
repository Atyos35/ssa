module.exports = {
  root: true,
  extends: [
    '@nuxt/eslint-config'
  ],
  rules: {
    // Règles personnalisées si nécessaire
    'vue/multi-word-component-names': 'off',
    'vue/no-unused-vars': 'warn',
    '@typescript-eslint/no-unused-vars': 'warn'
  }
} 