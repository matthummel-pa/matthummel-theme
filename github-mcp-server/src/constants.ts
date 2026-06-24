/** Shared constants for the GitHub MCP server. */

export const SERVER_NAME = "github-mcp-server";
export const SERVER_VERSION = "1.0.0";

/** Default GitHub REST API base. Override with GITHUB_API_URL for Enterprise. */
export const DEFAULT_API_URL = "https://api.github.com";

/** Max characters returned in a single tool response before truncation. */
export const CHARACTER_LIMIT = 25000;

/** Default and max page sizes for list/search tools. */
export const DEFAULT_PER_PAGE = 30;
export const MAX_PER_PAGE = 100;

/** HTTP request timeout in milliseconds. */
export const REQUEST_TIMEOUT_MS = 30000;
