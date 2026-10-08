import '@fontsource-variable/onest'
import '@fontsource-variable/jetbrains-mono'
import './styles/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import { setSessionExpiredHandler } from './api/admin'
import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(createPinia())
app.use(router)

setSessionExpiredHandler(() => {
  void router.push({ name: 'admin-login', query: { redirect: router.currentRoute.value.fullPath } })
})

app.mount('#app')
