import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import {
  githubRequest,
  handleApiError,
  hasNextPage,
  toolResult,
  errorResult,
} from "../services/github.js";
import {
  ResponseFormat,
  responseFormatSchema,
  paginationFields,
  repoFields,
} from "../schemas/common.js";
import type { GitHubPullRequest, GitHubBranch } from "../types.js";

export function registerPullTools(server: McpServer): void {
  // --- github_list_pull_requests ---
  const ListPullsSchema = z.object({
    ...repoFields,
    state: z.enum(["open", "closed", "all"]).default("open").describe("PR state (default open)"),
    ...paginationFields,
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_list_pull_requests",
    {
      title: "List Pull Requests",
      description: `List pull requests in a repository.

Args:
  - owner (string), repo (string)
  - state ('open' | 'closed' | 'all'): default 'open'
  - per_page (1-100, default 30), page (default 1)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { total_in_page, page, has_more, pull_requests: [{ number, title, state, author, head, base, draft, created_at, url }] }

Examples:
  - "List open PRs in me/repo" -> state='open'`,
      inputSchema: ListPullsSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, state, per_page, page, response_format }) => {
      try {
        const { data, link } = await githubRequest<GitHubPullRequest[]>(
          `/repos/${owner}/${repo}/pulls`,
          "GET",
          undefined,
          { state, per_page, page }
        );
        const out = {
          total_in_page: data.length,
          page,
          has_more: hasNextPage(link),
          pull_requests: data.map((p) => ({
            number: p.number,
            title: p.title,
            state: p.state,
            author: p.user?.login ?? null,
            head: p.head?.ref ?? null,
            base: p.base?.ref ?? null,
            draft: p.draft ?? false,
            created_at: p.created_at ?? null,
            url: p.html_url,
          })),
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : data.length
            ? `# Pull requests in ${owner}/${repo} (${state}) — page ${page}\n\n${data
                .map(
                  (p) =>
                    `- #${p.number} **${p.title}** [${p.state}${p.draft ? ", draft" : ""}] ${p.head?.ref ?? "?"} → ${p.base?.ref ?? "?"}\n  ${p.html_url}`
                )
                .join("\n")}`
            : `No ${state} pull requests in ${owner}/${repo}.`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_create_pull_request ---
  const CreatePullSchema = z.object({
    ...repoFields,
    title: z.string().min(1).describe("Pull request title"),
    head: z
      .string()
      .min(1)
      .describe("Branch with your changes. For cross-fork PRs use 'forkowner:branch'."),
    base: z.string().min(1).describe("Branch you want to merge into, e.g. 'main'"),
    body: z.string().optional().describe("PR description in Markdown (optional)"),
    draft: z.boolean().default(false).describe("Open as a draft PR? (default false)"),
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_create_pull_request",
    {
      title: "Create Pull Request",
      description: `Open a pull request. This WRITES to GitHub.

Args:
  - owner (string), repo (string): the BASE repository
  - title (string, required)
  - head (string, required): source branch (or 'forkowner:branch' for forks)
  - base (string, required): target branch
  - body (string, optional)
  - draft (boolean): default false
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { number, title, state, url }

Examples:
  - "PR my feat/scroll-offset into main" -> head='feat/scroll-offset', base='main'
  - Returns 422 if no commits differ between head and base, or the PR already exists.`,
      inputSchema: CreatePullSchema.shape,
      annotations: {
        readOnlyHint: false,
        destructiveHint: false,
        idempotentHint: false,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, title, head, base, body, draft, response_format }) => {
      try {
        const payload: Record<string, unknown> = { title, head, base, draft };
        if (body) payload.body = body;
        const { data } = await githubRequest<GitHubPullRequest>(
          `/repos/${owner}/${repo}/pulls`,
          "POST",
          payload
        );
        const out = { number: data.number, title: data.title, state: data.state, url: data.html_url };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `Created PR #${out.number}: **${out.title}**\n${out.url}`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_list_branches ---
  const ListBranchesSchema = z.object({
    ...repoFields,
    ...paginationFields,
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_list_branches",
    {
      title: "List Branches",
      description: `List branches in a repository.

Args:
  - owner (string), repo (string)
  - per_page (1-100, default 30), page (default 1)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { total_in_page, page, has_more, branches: [{ name, protected, commit_sha }] }

Examples:
  - "What branches exist in me/repo?"`,
      inputSchema: ListBranchesSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, per_page, page, response_format }) => {
      try {
        const { data, link } = await githubRequest<GitHubBranch[]>(
          `/repos/${owner}/${repo}/branches`,
          "GET",
          undefined,
          { per_page, page }
        );
        const out = {
          total_in_page: data.length,
          page,
          has_more: hasNextPage(link),
          branches: data.map((b) => ({
            name: b.name,
            protected: b.protected ?? false,
            commit_sha: b.commit?.sha ?? null,
          })),
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : data.length
            ? `# Branches in ${owner}/${repo} (page ${page})\n\n${data
                .map((b) => `- ${b.name}${b.protected ? " (protected)" : ""}`)
                .join("\n")}`
            : `No branches found in ${owner}/${repo}.`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );
}
