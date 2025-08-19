import { z } from 'zod'

export const missionClosureSchema = z.object({
  // Accepte string ou number, transforme en string
  missionId: z.union([
    z.string().min(1, 'L\'ID de la mission est requis'),
    z.number().positive('L\'ID de la mission doit être un nombre positif')
  ]).transform(val => String(val)), // Transformer en string pour la validation
  
  status: z.enum(['Success', 'Failure']),
  
  summary: z.string()
    .min(1, 'Le résumé de la mission est requis')
    .max(1000, 'Le résumé ne peut pas dépasser 1000 caractères')
})

// Type pour les données du formulaire (après validation Zod)
export type MissionClosureFormData = z.infer<typeof missionClosureSchema>

// Type pour les données brutes du formulaire (avant validation)
export interface MissionClosureFormRawData {
  missionId: string | number | null
  status: 'Success' | 'Failure' | ''
  summary: string
}
