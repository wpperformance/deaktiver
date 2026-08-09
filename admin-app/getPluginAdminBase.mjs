import { realpathSync } from "node:fs";
import { sep } from "node:path";

const CONTENT_PARENTS = new Set(["app", "wp-content"]);

/**
 * Resolve a filesystem path, following symlinks when possible.
 *
 * @param {string} fromDir
 * @returns {string}
 */
function resolveRealPath(fromDir) {
	try {
		return realpathSync(fromDir);
	} catch {
		return fromDir;
	}
}

/**
 * Public URL base matching PressWind PWVite::get_relative_path_from() for plugins.
 * Bedrock: /app/plugins/{slug}/admin-app
 * Classic: /wp-content/plugins/{slug}/admin-app
 *
 * @param {string} fromDir Absolute path inside the plugin (defaults to cwd).
 * @returns {string} URL path without trailing slash.
 */
export default function getPluginAdminBase(fromDir = process.cwd()) {
	const parts = resolveRealPath(fromDir).split(sep).filter(Boolean);

	// Prefer …/{app|wp-content}/plugins/{slug}/…
	for (let i = 0; i < parts.length - 2; i++) {
		if (CONTENT_PARENTS.has(parts[i]) && parts[i + 1] === "plugins") {
			return `/${parts[i]}/plugins/${parts[i + 2]}/admin-app`;
		}
	}

	// Fallback: last "plugins" segment with a known content parent.
	const pluginsIndex = parts.lastIndexOf("plugins");
	if (pluginsIndex >= 1 && pluginsIndex + 1 < parts.length) {
		const contentParent = parts[pluginsIndex - 1];
		if (CONTENT_PARENTS.has(contentParent)) {
			return `/${contentParent}/plugins/${parts[pluginsIndex + 1]}/admin-app`;
		}
	}

	throw new Error(`Could not resolve plugin path from: ${fromDir}`);
}
