// Plugin côté serveur pour Quasar
// Ce plugin s'assure que Quasar ne cause pas d'erreurs d'hydratation

export default defineNuxtPlugin(() => {
  // Côté serveur, on ne fait rien avec Quasar
  // Les composants seront rendus côté client uniquement
})
