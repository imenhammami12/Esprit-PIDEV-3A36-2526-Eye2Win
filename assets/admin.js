// ── assets/admin.js ──────────────────────────────────────────
// Point d'entrée unique pour tout l'admin.
// Webpack Encore va bundler + minifier tout ça en un seul fichier.

// 1. Bootstrap JS (remplace le CDN bootstrap.bundle.min.js)
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// 2. Bootstrap CSS via Sass (composants sélectifs uniquement)
import './styles/admin.scss';

// 3. Bootstrap Icons (self-hosted, plus de CDN render-blocking)
import 'bootstrap-icons/font/bootstrap-icons.css';