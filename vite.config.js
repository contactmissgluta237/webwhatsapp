import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";
import fs from "fs";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "public/assets/scss/style.scss",
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: [
                "**/node_modules/**",
                "**/vendor/**",
                "**/resources/lang/**",
                "**/.git/**",
            ],
            usePolling: false,
        },
        hmr: {
            exclude: ["resources/lang/**/*.json", "resources/lang/**/*.php"],
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    "mixed-decls",
                    "color-functions",
                    "global-builtin",
                    "import",
                ],
            },
        },
    },
    resolve: {
        alias: {
            "@scss": path.resolve(__dirname, "public/assets/scss/app"),
        },
    },
    optimizeDeps: {
        exclude: ["resources/lang"],
    },
    build: {
        rollupOptions: {
            external: [/resources\/lang\/.*/],
        },
    },
});
