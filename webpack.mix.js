// webpack.mix.js

let mix = require('laravel-mix');
let webpack = require('webpack');
require('laravel-mix-polyfill');

mix.setPublicPath("src/Resources/public");
mix.js('assets/js/field-produts-attributes.js', '');
mix.js('src/Resources/private/js/app.js', 'sylius.js');

mix.polyfill();

mix.webpackConfig({
    output: {
        publicPath: 'bundles/easyshop/',
    },
    plugins: [
        // fix ReferenceError: Buffer/process is not defined
        new webpack.ProvidePlugin({
            process : 'process/browser',
            Buffer  : ['buffer', 'Buffer']
        })
    ]
})
