const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSingleRuntimeChunk()
    .splitEntryChunks()

    // ── Entrées ──────────────────────────────────────────────
    // Entry principal (front)
    .addEntry('app', './assets/app.js')
    // Entry admin — CSS + JS groupés pour toutes les pages admin
    .addEntry('admin', './assets/admin.js')

    // ── Sass (pour Bootstrap custom) ─────────────────────────
    .enableSassLoader()

    // ── PostCSS (pour PurgeCSS + Autoprefixer) ────────────────
    .enablePostCssLoader()

    // ── Babel ─────────────────────────────────────────────────
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })
;

module.exports = Encore.getWebpackConfig();