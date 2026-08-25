const mix = require('laravel-mix');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
if (mix.inProduction()) {
    mix.version();
}

// mix.config.webpackConfig.output = {
//     chunkFilename: 'v/' + version + '/scripts/[name].js',
//     publicPath: '/',
// };

mix.js('resources/js/app.js', 'public/v/'+process.env.APP_VERSION+'/js').vue()
.postCss('resources/css/app.css', 'public/v/'+process.env.APP_VERSION+'/css', [
    //
]);

