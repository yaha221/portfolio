import { type Ref, ref } from 'vue'

/**
 * Loading state around one async loader: call run() on mount and whenever its inputs change.
 * A slower earlier run never overwrites the result of a later one.
 */
export function useAsync<T>(load: () => Promise<T>) {
  const data = ref(null) as Ref<T | null>
  const error = ref<unknown>(null)
  const loading = ref(false)
  let latest = 0

  async function run(): Promise<void> {
    const current = ++latest
    loading.value = true
    error.value = null
    try {
      const result = await load()
      if (current === latest) data.value = result
    } catch (caught) {
      if (current === latest) error.value = caught
    } finally {
      if (current === latest) loading.value = false
    }
  }

  return { data, error, loading, run }
}
