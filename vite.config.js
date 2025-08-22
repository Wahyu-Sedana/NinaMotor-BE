import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    return {
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/js/app.js"],
                refresh: true,
            }),
        ],
        build: {
            outDir: "public/build",
            emptyOutDir: true,
            manifest: "manifest.json",
            rollupOptions: {
                output: {
                    manualChunks: undefined,
                },
            },
        },
        server: {
            host: "0.0.0.0",
            port: 5173,
            hmr: {
                host: "localhost",
                port: 5173,
            },
        },

        base: command === "serve" ? "" : "/build/",
        define: {
            __APP_ENV__: JSON.stringify(env.APP_ENV),
            __APP_URL__: JSON.stringify(env.APP_URL),
        },
    };
});
