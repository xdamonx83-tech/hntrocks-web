import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// This build only serves /next. It does not replace or overwrite the
// existing React and Laravel assets on the public site.
export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/next/feed.css', 'resources/js/next/feed.js'],
      buildDirectory: 'build/next',
      refresh: false,
    }),
  ],
});
