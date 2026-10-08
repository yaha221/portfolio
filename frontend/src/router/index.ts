import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import { trackPageView } from '@/api/pageViews'
import { site } from '@/config/site'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useAuthStore } from '@/stores/auth'
import HomeView from '@/views/HomeView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    requiresAuth?: boolean
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'home', component: HomeView },
      {
        path: 'projects',
        name: 'projects',
        component: () => import('@/views/ProjectsView.vue'),
        meta: { title: 'Проекты' },
      },
      {
        path: 'projects/:slug',
        name: 'project',
        component: () => import('@/views/ProjectView.vue'),
        props: true,
      },
      {
        path: 'about',
        name: 'about',
        component: () => import('@/views/AboutView.vue'),
        meta: { title: 'Обо мне' },
      },
      {
        path: 'contact',
        name: 'contact',
        component: () => import('@/views/ContactView.vue'),
        meta: { title: 'Контакты' },
      },
      {
        path: ':pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/NotFoundView.vue'),
        meta: { title: 'Страница не найдена' },
      },
    ],
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: () => import('@/views/admin/LoginView.vue'),
    meta: { title: 'Вход' },
  },
  {
    // Admin code is a separate chunk: public visitors never download it
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'admin-projects' } },
      {
        path: 'projects',
        name: 'admin-projects',
        component: () => import('@/views/admin/ProjectsAdminView.vue'),
        meta: { title: 'Проекты' },
      },
      {
        path: 'projects/new',
        name: 'admin-project-new',
        component: () => import('@/views/admin/ProjectEditView.vue'),
        meta: { title: 'Новый проект' },
      },
      {
        path: 'projects/:id(\\d+)',
        name: 'admin-project-edit',
        component: () => import('@/views/admin/ProjectEditView.vue'),
        props: true,
        meta: { title: 'Проект' },
      },
      {
        path: 'technologies',
        name: 'admin-technologies',
        component: () => import('@/views/admin/TechnologiesAdminView.vue'),
        meta: { title: 'Технологии' },
      },
      {
        path: 'skills',
        name: 'admin-skills',
        component: () => import('@/views/admin/SkillsAdminView.vue'),
        meta: { title: 'Навыки' },
      },
      {
        path: 'experience',
        name: 'admin-experience',
        component: () => import('@/views/admin/ExperienceAdminView.vue'),
        meta: { title: 'Опыт' },
      },
      {
        path: 'messages',
        name: 'admin-messages',
        component: () => import('@/views/admin/MessagesAdminView.vue'),
        meta: { title: 'Сообщения' },
      },
      {
        path: 'analytics',
        name: 'admin-analytics',
        component: () => import('@/views/admin/AnalyticsAdminView.vue'),
        meta: { title: 'Аналитика' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash }
    // A filter change keeps the scroll position; a new page starts from the top
    return to.path !== from.path || to.query.page !== from.query.page ? { top: 0 } : false
  },
})

router.beforeEach(async (to) => {
  if (!to.matched.some((record) => record.meta.requiresAuth)) {
    return true
  }

  // Restores the session from the refresh cookie after a page reload
  const token = await useAuthStore().validToken()

  return token !== null ? true : { name: 'admin-login', query: { redirect: to.fullPath } }
})

router.afterEach((to, from, failure) => {
  if (failure) return

  // Views that know a better title (a project's name) set it themselves after loading
  document.title = to.meta.title ? `${to.meta.title} — ${site.name}` : site.name

  // Admin pages aren't site traffic; a new query on the same page (filter, page number) isn't a new view
  const samePage = from.matched.length > 0 && from.path === to.path
  if (!to.path.startsWith('/admin') && !samePage) {
    trackPageView(to.path)
  }
})

export default router
