import { resolve } from "node:path";
import basicSsl from "@vitejs/plugin-basic-ssl";
import browserslist from "browserslist";
import { browserslistToTargets } from "lightningcss";
import { defineConfig } from "vite";
import getPluginAdminBase from "./getPluginAdminBase.mjs";

const adminBase = getPluginAdminBase(import.meta.dirname);

// https://vite.dev/config/
export default defineConfig({
	cacheDir: "./node_modules/.vite/admin-app",
	plugins: [basicSsl()],
	base:
		process.env.APP_ENV === "development"
			? `${adminBase}/`
			: `${adminBase}/dist/`,
	root: "",
	css: {
		transformer: "lightningcss",
		lightningcss: {
			targets: browserslistToTargets(browserslist(">= 0.25%")),
		},
	},
	build: {
		cssMinify: "lightningcss",
		// output dir for production build
		outDir: resolve(import.meta.dirname, "dist"),
		emptyOutDir: true,
		manifest: true,
		cssCodeSplit: false,
		target: "es2018",
		rolldownOptions: {
			input: resolve(import.meta.dirname, "src/main.js"),
		},
	},
	server: {
		cors: true,
		strictPort: true,
		port: 9980,
		https: true,
		hmr: {
			protocol: "wss",
			port: 9980,
		},
	},
});
