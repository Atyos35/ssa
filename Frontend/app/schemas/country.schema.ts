import { z } from 'zod'

export const countrySchema = z.object({
  name: z
    .string()
    .min(1, 'Le nom du pays est obligatoire')
    .min(2, 'Le nom du pays doit contenir au moins 2 caractères')
    .max(100, 'Le nom du pays ne peut pas dépasser 100 caractères')
    .regex(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le nom du pays ne peut contenir que des lettres, espaces, tirets et apostrophes')
})

export type CountryForm = z.infer<typeof countrySchema>

export const validateCountry = (data: unknown): { success: true; data: CountryForm } | { success: false; errors: Record<string, string> } => {
  const result = countrySchema.safeParse(data)
  
  if (result.success) {
    return { success: true, data: result.data }
  }
  
  const errors: Record<string, string> = {}
  result.error.issues.forEach(issue => {
    const field = issue.path[0] as string
    if (field) {
      errors[field] = issue.message
    }
  })
  
  return { success: false, errors }
}