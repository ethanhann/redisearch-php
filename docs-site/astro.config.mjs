import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

export default defineConfig({
  site: 'https://ethanhann.com',
  base: '/redisearch-php/',

  integrations: [
    starlight({
      title: 'RediSearch-PHP',
      logo: { src: './src/assets/logo.png' },
      social: [
        {
          icon: 'github',
          label: 'GitHub',
          href: 'https://github.com/ethanhann/redisearch-php',
        },
      ],
      customCss: ['./src/styles/custom.css'],
      editLink: {
        baseUrl:
            'https://github.com/ethanhann/redisearch-php/edit/master/docs-site/src/content/docs/',
      },
      sidebar: [
        { label: 'Getting Started', link: '/' },
        {
          label: 'Documentation',
          items: [
            { label: 'Indexing', link: '/indexing/' },
            { label: 'Searching', link: '/searching/' },
            { label: 'Aggregating', link: '/aggregating/' },
            { label: 'Suggesting', link: '/suggesting/' },
            { label: 'CLI', link: '/cli/' },
          ],
        },
        { label: 'Laravel Support', link: '/laravel-support/' },
        { label: 'Changelog', link: '/changelog/' },
      ],
    }),
  ],
});