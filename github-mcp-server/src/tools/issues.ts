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
import type { GitHubIssue } from "../types.js";

function labelNames(labels?: Array<{ name: string } | string>): string[] {
  if (!labels) return [];
  return labels.map((l) => (typeof l === "string" ? l : l.name));
}

export function registerIssueTools(server: McpServer): void {
  // --- github_list_issues ---
  const ListIssuesSchema = z.object({
    ...repoFields,
    state: z.enum(["open", "closed", "all"]).default("open").describe("Issue state (default open)"),
    labels: z
      .string()
      .optional()
      .describe("Comma-separated label names to filter by, e.g. 'bug,help wanted'"),
    ...paginationFields,
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_list_issues",
    {
      title: "List Issues",
      description: `List issues in a repository (pull requests are filtered out).

Args:
  - owner (string), repo (string)
  - state ('open' | 'closed' | 'all'): default 'open'
  - labels (string, optional): comma-separated label filter
  - per_page (1-100, default 30), page (default 1)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { total_in_page, page, has_more, issues: [{ number, title, state, author, labels, comments, created_at, url }] }

Examples:
  - "Show open bugs in me/repo" -> state='open', labels='bug'`,
      inputSchema: ListIssuesSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, state, labels, per_page, page, response_format }) => {
      try {
        const params: Record<string, unknown> = { state, per_page, page };
        if (labels) params.labels = labels;
        const { data, link } = await githubRequest<GitHubIssue[]>(
          `/repos/${owner}/${repo}/issues`,
          "GET",
          undefined,
          params
        );
        // The issues endpoint also returns PRs; exclude those.
        const issuesOnly = data.filter((i) => !i.pull_request);
        const out = {
          total_in_page: issuesOnly.length,
          page,
          has_more: hasNextPage(link),
          issues: issuesOnly.map((i) => ({
            number: i.number,
            title: i.title,
            state: i.state,
            author: i.user?.login ?? null,
            labels: labelNames(i.labels),
            comments: i.comments ?? 0,
            created_at: i.created_at ?? null,
            url: i.html_url,
          })),
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : issuesOnly.length
            ? `# Issues in ${owner}/${repo} (${state}) — page ${page}\n\n${issuesOnly
                .map(
                  (i) =>
                    `- #${i.number} **${i.title}** [${i.state}] by ${i.user?.login ?? "?"}${
                      labelNames(i.labels).length ? ` (${labelNames(i.labels).join(", ")})` : ""
                    }\n  ${i.html_url}`
                )
                .join("\n")}`
            : `No ${state} issues found in ${owner}/${repo}.`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_create_issue ---
  const CreateIssueSchema = z.object({
    ...repoFields,
    title: z.string().min(1).describe("Issue title"),
    body: z.string().optional().describe("Issue body in Markdown (optional)"),
    labels: z.array(z.string()).optional().describe("Label names to apply (optional)"),
    assignees: z.array(z.string()).optional().describe("User logins to assign (optional)"),
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_create_issue",
    {
      title: "Create Issue",
      description: `Create a new issue in a repository. This WRITES to GitHub.

Args:
  - owner (string), repo (string)
  - title (string, required)
  - body (string, optional): Markdown body
  - labels (string[], optional)
  - assignees (string[], optional)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { number, title, state, url }

Examples:
  - "Open an issue 'Links break with sticky header' in me/tocflow" -> title=..., body=...`,
      inputSchema: CreateIssueSchema.shape,
      annotations: {
        readOnlyHint: false,
        destructiveHint: false,
        idempotentHint: false,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, title, body, labels, assignees, response_format }) => {
      try {
        const payload: Record<string, unknown> = { title };
        if (body) payload.body = body;
        if (labels?.length) payload.labels = labels;
        if (assignees?.length) payload.assignees = assignees;
        const { data } = await githubRequest<GitHubIssue>(
          `/repos/${owner}/${repo}/issues`,
          "POST",
          payload
        );
        const out = { number: data.number, title: data.title, state: data.state, url: data.html_url };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `Created issue #${out.number}: **${out.title}**\n${out.url}`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );
}
