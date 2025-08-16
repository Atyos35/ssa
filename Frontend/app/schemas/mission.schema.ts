import { z } from 'zod'

export const missionSchema = z.object({
  name: z.string()
    .min(3, 'Le nom doit contenir au moins 3 caractères')
    .max(100, 'Le nom ne peut pas dépasser 100 caractères'),
  
  description: z.string()
    .min(1, 'La description est requise')
    .max(500, 'La description ne peut pas dépasser 500 caractères'),
  
  objectives: z.string()
    .min(1, 'Les objectifs sont requis')
    .max(500, 'Les objectifs ne peuvent pas dépasser 500 caractères'),
  
  danger: z.enum(['Low', 'Medium', 'High', 'Critical'], {
    required_error: 'Le niveau de danger est requis'
  }),
  
  startDate: z.string()
    .min(1, 'La date de début est requise'),
  
  endDate: z.string()
    .optional()
    .refine((val) => !val || val > new Date().toISOString().split('T')[0], {
      message: 'La date de fin doit être postérieure à aujourd\'hui'
    }),
  
  countryId: z.union([
    z.number().min(1, 'Le pays est requis'),
    z.string().min(1, 'Le pays est requis').transform(val => Number(val))
  ]).transform(val => Number(val)),
  
  agentIds: z.array(z.union([
    z.number(),
    z.string().transform(val => Number(val))
  ]))
    .optional()
    .default([])
})

export type MissionFormData = z.infer<typeof missionSchema>
