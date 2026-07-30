# React live routing

The main domain serves the React frontend from `public/app` only for routes that already exist in `hntrocks-react-next`.

Laravel remains responsible for API endpoints, social-auth callbacks, maps, guides, LFG, teams, moments, legal pages, admin pages, and every route not explicitly assigned to the React SPA in `public/.htaccess`.

The production build for the main domain must use Vite base `/app/` and be deployed to `public/app`.
