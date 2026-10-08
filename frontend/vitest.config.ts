import { fileURLToPath } from 'node:url'
import { mergeConfig, defineConfig, configDefaults } from 'vitest/config'
import viteConfig from './vite.config.ts'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      exclude: [...configDefaults.exclude, 'e2e/**'],
      root: fileURLToPath(new URL('./', import.meta.url)),
      coverage: {
        provider: 'v8',
        include: ['src/**/*.{ts,vue}'],
        // Generated API types, the bootstrap file and the tests themselves
        exclude: ['src/api/schema.d.ts', 'src/main.ts', 'src/__tests__/**'],
        reporter: ['text', 'html'],
      },
    },
  }),
)
