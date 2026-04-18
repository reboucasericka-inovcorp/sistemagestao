type LaravelErrorResponse = {
  message?: string
  errors?: Record<string, string[]>
}

const errorMap: Record<string, string> = {
  'The iban has already been taken.': 'Este IBAN já está registado.',
  'The number has already been taken.': 'Este número já existe.',
  'The email has already been taken.': 'Este email já está registado.',
  'The iban field is required.': 'O IBAN é obrigatório.',
  'The number field is required.': 'O número é obrigatório.',
  'The payment proof file field is required.': 'É obrigatório anexar comprovativo de pagamento.',
}

export const mapLaravelErrors = (data: LaravelErrorResponse): string[] => {
  const messages: string[] = []

  if (data.errors) {
    Object.values(data.errors).forEach((fieldErrors) => {
      fieldErrors.forEach((msg) => {
        messages.push(errorMap[msg] ?? msg)
      })
    })
  }

  if (data.message && messages.length === 0) {
    messages.push(errorMap[data.message] ?? data.message)
  }

  return messages
}
