import { z } from 'zod'

// Schéma de validation pour l'inscription
export const registrationSchema = z.object({
  firstName: z
    .string()
    .min(1, 'Le prénom est obligatoire')
    .min(2, 'Le prénom doit contenir au moins 2 caractères')
    .max(50, 'Le prénom ne peut pas dépasser 50 caractères')
    .regex(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes'),

  lastName: z
    .string()
    .min(1, 'Le nom est obligatoire')
    .min(2, 'Le nom doit contenir au moins 2 caractères')
    .max(50, 'Le nom ne peut pas dépasser 50 caractères')
    .regex(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes'),

  email: z
    .string()
    .min(1, 'L\'email est obligatoire')
    .email('Format d\'email invalide')
    .max(255, 'L\'email ne peut pas dépasser 255 caractères'),

  password: z
    .string()
    .min(1, 'Le mot de passe est obligatoire')
    .min(12, 'Le mot de passe doit contenir au moins 12 caractères')
    .max(128, 'Le mot de passe ne peut pas dépasser 128 caractères')
    .regex(/[A-Z].*[A-Z]/, 'Le mot de passe doit contenir au moins 2 majuscules')
    .regex(/\d.*\d/, 'Le mot de passe doit contenir au moins 2 chiffres')
    .regex(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?].*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, 'Le mot de passe doit contenir au moins 2 caractères spéciaux')
})

// Type TypeScript généré automatiquement
export type RegistrationForm = z.infer<typeof registrationSchema>

// Fonction de validation
export const validateRegistration = (data: unknown): { success: true; data: RegistrationForm } | { success: false; errors: Record<string, string> } => {
  const result = registrationSchema.safeParse(data)
  
  if (result.success) {
    return { success: true, data: result.data }
  }
  
  // Transformer les erreurs Zod en format utilisable
  const errors: Record<string, string> = {}
  result.error.issues.forEach(issue => {
    const field = issue.path[0] as string
    if (field) {
      errors[field] = issue.message
    }
  })
  
  return { success: false, errors }
}
