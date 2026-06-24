#!/usr/bin/env node
/**
 * github-mcp-server
 *
 * An MCP server exposing the GitHub REST API as tools: repositories, file
 * contents, issues, pull requests, branches, and search. Authenticates with a
 * personal access token from the GITHUB_TOKEN environment variable.
 *
 * Transport: stdio (designed to run locally as a subprocess of an MCP client).
 */

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";

import { SERVER_NAME, SERVER_VERSION } from "./constants.js";
import { registerRepoTools } from "./tools/repos.js";
import { registerFileTools } from "./tools/files.js";
import { registerIssueTools } from "./tools/issues.js";
import { registerPullTools } from "./tools/pulls.js";
import { registerSearchTools } from "./tools/search.js";

function buildServer(): McpServer {
  const server = new McpServer({ name: SERVER_NAME, version: SERVER_VERSION });
  registerRepoTools(server);
  registerFileTools(server);
  registerIssueTools(server);
  registerPullTools(server);
  registerSearchTools(server);
  return server;
}

async function main(): Promise<void> {
  // Support `--help` without requiring a token.
  if (process.argv.includes("--help") || process.argv.includes("-h")) {
    // Log to stderr only (stdout is reserved for the MCP protocol).
    console.error(
      `${SERVER_NAME} v${SERVER_VERSION}\n\n` +
        `MCP server for the GitHub REST API (stdio transport).\n\n` +
        `Environment:\n` +
        `  GITHUB_TOKEN     (required) GitHub personal access token\n` +
        `  GITHUB_API_URL   (optional) override API base for GitHub Enterprise\n\n` +
        `Tools: github_get_authenticated_user, github_list_repos, github_get_repo,\n` +
        `  github_create_repo, github_get_file, github_create_or_update_file,\n` +
        `  github_list_issues, github_create_issue, github_list_pull_requests,\n` +
        `  github_create_pull_request, github_list_branches,\n` +
        `  github_search_repositories, github_search_code`
    );
    process.exit(0);
  }

  if (!process.env.GITHUB_TOKEN && !process.env.GITHUB_PERSONAL_ACCESS_TOKEN) {
    console.error(
      "ERROR: GITHUB_TOKEN environment variable is required. " +
        "Create a personal access token at https://github.com/settings/tokens and set GITHUB_TOKEN."
    );
    process.exit(1);
  }

  const server = buildServer();
  const transport = new StdioServerTransport();
  await server.connect(transport);
  // stdio servers must log to stderr, never stdout.
  console.error(`${SERVER_NAME} v${SERVER_VERSION} running on stdio`);
}

main().catch((error) => {
  console.error("Fatal server error:", error);
  process.exit(1);
});
