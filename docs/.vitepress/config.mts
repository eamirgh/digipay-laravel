import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Digipay Laravel',
  description: 'Production-grade scalable Digipay UPG package for Laravel 11, 12, 13+',
  base: '/digipay-laravel/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/logo.svg' }],
    ['meta', { name: 'theme-color', content: '#0d6efd' }],
  ],

  themeConfig: {
    siteTitle: 'Digipay Laravel',

    nav: [
      { text: 'Guide', link: '/guide/introduction' },
      { text: 'API Reference', link: '/api/ticket-creation' },
      { text: 'Advanced', link: '/advanced/multi-tenancy' },
      { text: 'Changelog', link: 'https://github.com/eamirgh/digipay-laravel/releases' },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        collapsed: false,
        items: [
          { text: 'Introduction', link: '/guide/introduction' },
          { text: 'Installation & Setup', link: '/guide/installation' },
          { text: 'Payment Lifecycle', link: '/guide/lifecycle' },
          { text: 'Eloquent Model Integration', link: '/guide/eloquent-integration' },
          { text: 'Quick Start Tutorial', link: '/guide/quick-start' },
        ],
      },
      {
        text: 'Core API Operations',
        collapsed: false,
        items: [
          { text: 'Ticket Creation & Redirect', link: '/api/ticket-creation' },
          { text: 'Callback & Verification', link: '/api/verification' },
          { text: 'Manual Reversal', link: '/api/reversal' },
          { text: 'Delivery Confirmation', link: '/api/delivery' },
          { text: 'Refunds & Inquiries', link: '/api/refunds' },
        ],
      },
      {
        text: 'Advanced Architecture',
        collapsed: false,
        items: [
          { text: 'Multi-Store & Multi-Tenancy', link: '/advanced/multi-tenancy' },
          { text: 'Domain Events', link: '/advanced/events' },
          { text: 'Enums & DTO Reference', link: '/advanced/enums-dtos' },
          { text: 'Testing & Mocking', link: '/advanced/testing' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/eamirgh/digipay-laravel' },
    ],

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 Amir Ghaffari (eamirgh)',
    },
  },
})
