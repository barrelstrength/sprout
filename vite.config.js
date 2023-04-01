import ViteRestart from 'vite-plugin-restart';

// https://vitejs.dev/config/
export default ({command}) => ({
    base: command === 'serve' ? '' : '/dist/',
    // Path starts from the project path, in our case 'assets' where package.json is
    root: 'assets/src',
    // Relative to "root" setting folder
    publicDir: '../public',
    build: {
        emptyOutDir: true,
        manifest: true,
        // outDir is relative to 'root' as defined above
        outDir: '../dist',
        // rollupOptions is relative to 'root' as defined above
        rollupOptions: {
            input: {
                cp: '/core/SproutCp.js',
                alpine: '/core/SproutAlpine.js',
                adminTable: '/core/SproutAdminTable.js',
                groups: '/core/SourceGroups.js',
                // copyPaste: '/campaigns/CopyPaste.js',
                // forms: '/forms/Forms.js',
                // notifications: '/notifications/Notifications.js',
                redirects: '/redirects/Redirects.js',
                // subscribers: '/lists/Subscribers.js',
                dataStudio: '/data-studio/DataStudio.js',
                dataSetIndex: '/data-studio/DataSetIndex.js',
                sitemaps: '/sitemaps/Sitemaps.js',
                meta: '/meta/Meta.js',
                addressField: '/fields/AddressField.js',
                emailField: '/fields/EmailField.js',
                phoneField: '/fields/PhoneField.js',
                regularExpressionField: '/fields/RegularExpressionField.js',
                urlField: '/fields/UrlField.js',

                // Front End
                DynamicCsrfInput: '/core/DynamicCsrfInput.js',

                accessibility: '/forms-frontend/Accessibility.js',
                addressFieldFrontEnd: '/forms-frontend/AddressField.js',
                disableSubmitButton: '/forms-frontend/DisableSubmitButton.js',
                rules: '/forms-frontend/Rules.js',
                submitHandler: '/forms-frontend/SubmitHandler.js',
            },
            output: {
                sourcemap: true,
            },
        },
    },
    plugins: [
        ViteRestart({
            reload: [
                '../../src/**/templates/**/*',
            ],
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 39999, // DDEV Internal Port
        strictPort: true,
        hmr: {
            // To run vite in DDEV we need to recognize the port mapping
            // https://vitejs.dev/config/server-options.html#server-hmr
            clientPort: 3002, // DDEV External Port (HTTPS)
        },
    },
});
