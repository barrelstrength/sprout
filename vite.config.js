import ViteRestart from 'vite-plugin-restart';

// https://vitejs.dev/config/
export default ({command}) => ({
    base: command === 'serve' ? '' : '/dist/',
    // Path starts from the project path, in our case 'assets' where package.json is
    root: 'assets/src',
    // Relative to "root" setting folder
    publicDir: '../public',
    // https://github.com/vitejs/vite/discussions/7920
    esbuild: {
        drop: ['console', 'debugger'],
    },
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
                emailTypes: '/mailer/EmailTypesSettings.js',
                sentEmailDetailsModal: '/sent-email/SentEmailDetailsModal.js',
                transactionalEmail: '/transactional/NotificationEvents.js',
                // copyPaste: '/campaigns/CopyPaste.js',
                // notifications: '/notifications/Notifications.js',
                redirects: '/redirects/Redirects.js',
                dataStudio: '/data-studio/DataStudio.js',
                dataSetIndex: '/data-studio/DataSetIndex.js',
                sitemapMetadata: '/sitemaps/SitemapMetadata.js',
                meta: '/meta/Meta.js',
                addressField: '/fields/AddressField.js',
                emailField: '/fields/EmailField.js',
                phoneField: '/fields/PhoneField.js',
                regularExpressionField: '/fields/RegularExpressionField.js',
                urlField: '/fields/UrlField.js',

                forms: '/forms/Forms.js',
                formTypes: '/forms/FormTypesSettings.js',
                integrationTypes: '/forms/IntegrationTypesSettings.js',
                submissionStatusSettings: '/forms/SubmissionStatusSettings.js',

                // Front End
                DynamicCsrfInput: '/core/DynamicCsrfInput.js',

                accessibility: '/forms-frontend/Accessibility.js',
                addressFieldFrontEnd: '/forms-frontend/AddressField.js',
                disableSubmitButton: '/forms-frontend/DisableSubmitButton.js',
                rules: '/forms-frontend/Rules.js',
                submitHandler: '/forms-frontend/SubmitHandler.js',

                reCaptchaCheckbox: '/forms-frontend/recaptcha_v2_checkbox.js',
                reCaptchaInvisible: '/forms-frontend/recaptcha_v2_invisible.js',
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
