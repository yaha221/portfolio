import { ref } from 'vue'

import { ApiError, SessionExpiredError } from '@/api/problem'

/**
 * Busy flag and errors around a form submit: 422 violations go to fields, anything else to one message.
 */
export function useSubmit() {
  const busy = ref(false)
  const errors = ref<Record<string, string>>({})
  const failure = ref<string | null>(null)

  async function submit<T>(action: () => Promise<T>): Promise<T | undefined> {
    busy.value = true
    errors.value = {}
    failure.value = null

    try {
      return await action()
    } catch (caught) {
      if (caught instanceof SessionExpiredError) {
        return undefined // adminCall already redirects to the login page
      }
      if (caught instanceof ApiError && caught.status === 422) {
        errors.value = caught.fieldErrors
        failure.value = 'Исправьте отмеченные поля.'
      } else {
        failure.value =
          caught instanceof ApiError
            ? caught.message
            : 'Не получилось. Проверьте соединение и попробуйте ещё раз.'
      }
      return undefined
    } finally {
      busy.value = false
    }
  }

  return { busy, errors, failure, submit }
}
