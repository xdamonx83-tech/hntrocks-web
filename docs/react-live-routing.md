# React live routing

The main domain serves the React frontend from `public/app` only for routes that already exist in `hntrocks-react-next`.

Only GET and HEAD requests to the listed SPA pages are rewritten to `public/app/index.html`, including `/profile` and `/profile/edit`. Other methods continue to Laravel.

Laravel remains responsible for API endpoints, social-auth callbacks, maps, guides, LFG, legacy profile subpages and write actions, legal pages, admin pages, and every route not explicitly assigned to the React SPA in `public/.htaccess`.

The production build for the main domain must use Vite base `/app/` and be deployed to `public/app`.
