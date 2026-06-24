import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import {
  githubRequest,
  handleApiError,
  toolResult,
  errorResult,
} from "../services/github.js";
import { ResponseFormat, responseFormatSchema, paginationFields } from "../schemas/common.js";
import type { GitHubRepo } from "../types.js";

interface SearchResponse<T> {
  total_count: number;
  incomplete_results: boolean;
  items: T[];
}

interface CodeSearchItem {
  name: string;
  path: string;
  html_url: string;
  repository?: { full_name: string };
}

export function registerSearchTools(server: McpServer): void {
  // --- github_search_repositories ---
  const SearchReposSchema = z.object({
    query: z
      .string()
      .min(1)
      .describe(
        "GitHub repo search query, supporting qualifiers, e.g. 'table of contents language:php stars:>50'"
      ),
    sort: z
      .enum(["stars", "forks", "updated", "best-match"])
      .default("best-match")
      .describe("Sort field (default best-match)"),
    order: z.enum(["desc", "asc"]).default("desc").describe("Sort order (default desc)"),
    ...paginationFields,
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_search_repositories",
    {
      title: "Search Repositories",
      description: `Search public repositories with GitHub's query syntax.

Args:
  - query (string, required): supports qualifiers (language:, stars:, user:, topic:, in:name)
  - sort ('stars' | 'forks' | 'updated' | 'best-match'): default 'best-match'
  - order ('desc' | 'asc'): default 'desc'
  - per_page (1-100, default 30), page (default 1)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { total_count, count, page, has_more, repos: [{ full_name, description, language, stars, url }] }

Examples:
  - "Find popular WordPress TOC plugins" -> query='table of contents wordpress language:php', sort='stars'
  - Check name availability -> query='tocflow in:name'`,
      inputSchema: SearchReposSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ query, sort, order, per_page, page, response_format }) => {
      try {
        const params: Record<string, unknown> = { q: query, order, per_page, page };
        if (sort !== "best-match") params.sort = sort;
        const { data } = await githubRequest<SearchResponse<GitHubRepo>>(
          "/search/repositories",
          "GET",
          undefined,
          params
        );
        const out = {
          total_count: data.total_count,
          count: data.items.length,
          page,
          has_more: data.total_count > page * per_page,
          repos: data.items.map((r) => ({
            full_name: r.full_name,
            description: r.description ?? null,
            language: r.language ?? null,
            stars: r.stargazers_count ?? 0,
            url: r.html_url,
          })),
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : data.items.length
            ? `# Repo search: '${query}' (${data.total_count} total)\n\n${data.items
                .map((r) => `- **${r.full_name}** (★${r.stargazers_count ?? 0}${r.language ? `, ${r.language}` : ""})${r.description ? ` — ${r.description}` : ""}\n  ${r.html_url}`)
                .join("\n")}`
            : `No repositories matched '${query}'.`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_search_code ---
  const SearchCodeSchema = z.object({
    query: z
      .string()
      .min(1)
      .describe("Code search query, e.g. 'addEventListener repo:owner/name' or 'TODO language:ts'"),
    ...paginationFields,
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_search_code",
    {
      title: "Search Code",
      description: `Search file contents across GitHub (or within a repo via the 'repo:' qualifier).

Note: GitHub code search only indexes the default branch and has its own rate limits.

Args:
  - query (string, required): supports qualifiers (repo:, path:, language:, filename:)
  - per_page (1-100, default 30), page (default 1)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { total_count, count, page, has_more, matches: [{ repo, path, url }] }

Examples:
  - "Find where 'registerBlockType' is used in me/tocflow" -> query='registerBlockType repo:me/tocflow'`,
      inputSchema: SearchCodeSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ query, per_page, page, response_format }) => {
      try {
        const { data } = await githubRequest<SearchResponse<CodeSearchItem>>(
          "/search/code",
          "GET",
          undefined,
          { q: query, per_page, page }
        );
        const out = {
          total_count: data.total_count,
          count: data.items.length,
          page,
          has_more: data.total_count > page * per_page,
          matches: data.items.map((i) => ({
            repo: i.repository?.full_name ?? null,
            path: i.path,
            url: i.html_url,
          })),
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : data.items.length
            ? `# Code search: '${query}' (${data.total_count} total)\n\n${data.items
                .map((i) => `- ${i.repository?.full_name ?? "?"}: \`${i.path}\`\n  ${i.html_url}`)
                .join("\n")}`
            : `No code matched '${query}'.`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );
}
