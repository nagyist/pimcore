const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

let webpackConfigs = [];

const standardConfigs = [
    {
        bundleFolderName: "AdminBundle",
        name: "admin",
        bundleName: "pimcoreadmin",
        configName: "pimcoreAdmin"
    }, {
        bundleFolderName: "AdminBundle",
        name: "imageEditor",
        bundleName: "pimcoreadmin",
        configName: "pimcoreAdminImageEditor"
    }, {
        bundleFolderName: "TinymceBundle",
        name: "tinymce",
        bundleName: "pimcoretinymce",
        configName: "pimcoreTinymce"
    }
]

standardConfigs.map((par) => {

    Encore.reset();

    Encore

        // directory where compiled assets will be stored
        .setOutputPath(`bundles/${par.bundleFolderName}/public/build`)
        .setOutputPath(`bundles/${par.bundleFolderName}/public/build/${par.name}`)
        // public path used by the web server to access the output path
        .setPublicPath(`/bundles/${par.bundleName}/build/${par.name}`)

        .setManifestKeyPrefix(`${par.bundleFolderName}/build`)


        .addEntry(par.name, `./assets/${par.name}.js`)

        // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
        .splitEntryChunks()

        // will require an extra script tag for runtime.js
        // but, you probably want this, unless you're building a single-page app
        .enableSingleRuntimeChunk()

        .cleanupOutputBeforeBuild()
        .enableBuildNotifications()
        .enableSourceMaps(!Encore.isProduction())
    ;

    let encoreConfig = Encore.getWebpackConfig();
    encoreConfig.name = par.configName;

    webpackConfigs.push(encoreConfig);

});

const par = {
    bundleFolderName: "EcommerceFrameworkBundle",
    name1: "backOffice",
    name2: "voucher",
    bundleName: "pimcoreecommerceframework",
    configName: "pimcoreEcommerceFrameworkVoucher"
};

Encore.reset();

Encore

    // directory where compiled assets will be stored
    .setOutputPath(`bundles/${par.bundleFolderName}/public/build`)
    // public path used by the web server to access the output path
    .setPublicPath(`/bundles/${par.bundleName}/build`)

    .setManifestKeyPrefix(`${par.bundleFolderName}/build`)


    .addEntry(par.name1, `./assets/${par.name1}.js`)
    .addEntry(par.name2, `./assets/${par.name2}.js`)

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
;

let encoreConfig = Encore.getWebpackConfig();
encoreConfig.name = par.configName;

webpackConfigs.push(encoreConfig);

module.exports = webpackConfigs;
