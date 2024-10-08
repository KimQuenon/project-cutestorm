const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('header', './assets/js/header.js')
    .addEntry('fadeAnim', './assets/js/fadeAnim.js')
    .addEntry('flipCard', './assets/js/flipCard.js')
    .addEntry('imgActions', './assets/js/imgActions.js')
    .addEntry('keepInTouch', './assets/js/keepInTouch.js')
    .addEntry('like', './assets/js/like.js')
    .addEntry('likeComment', './assets/js/likeComment.js')
    .addEntry('multiStep', './assets/js/multiStep.js')
    .addEntry('removeVariant', './assets/js/removeVariant.js')
    .addEntry('replyAdd', './assets/js/replyAdd.js')
    .addEntry('replyDisplay', './assets/js/replyDisplay.js')
    .addEntry('search', './assets/js/search.js')
    .addEntry('slideEffect', './assets/js/slideEffect.js')
    .addEntry('toggleDisplay', './assets/js/toggleDisplay.js')
    .addEntry('updateCart', './assets/js/updateCart.js')
    .addEntry('updateOrder', './assets/js/updateOrder.js')
    .addEntry('updateStock', './assets/js/updateStock.js')


    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    .enableStimulusBridge('./assets/controllers.json')

    // enables Sass/SCSS support
    .enableSassLoader()

    .enablePostCssLoader(options => {
        options.postcssOptions = {
            plugins: [
                require('tailwindcss'),
                require('autoprefixer'),
            ],
        };
    })


    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();
