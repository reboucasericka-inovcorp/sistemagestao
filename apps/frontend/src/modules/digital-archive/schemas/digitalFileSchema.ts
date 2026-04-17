import { z } from 'zod'

export const digitalFileSchema = z.object({
  name: z.string().trim().optional(),
  file: z.instanceof(File, { message: 'Selecione um ficheiro.' }),
  category: z.string().trim().optional(),
  description: z.string().trim().optional(),
  entity_id: z.number().optional().nullable(),
})

export type DigitalFileFormData = z.infer<typeof digitalFileSchema>
