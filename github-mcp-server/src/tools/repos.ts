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
import type { GitHubRepo, GitHubUser } from "../types.js";

function repoSummary(r: GitHubRepo) {
  return {
    full_name: r.full_name,
    private: r.private,
    description: r.description ?? null,
    language: r.language ?? null,
    stars: r.stargazers_count ?? 0,
    forks: r.forks_count ?? 0,
    open_issues: r.open_issues_count ?? 0,
    default_branch: r.default_branch ?? null,
    url: r.html_url,
    updated_at: r.updated_at ?? null,
  };
}

function repoLine(r: GitHubRepo): string {
  const vis = r.private ? "private" : "public";
  const lang = r.language ? `, ${r.language}` : "";
  const desc = r.description ? ` — ${r.description}` : "";
  return `- **${r.full_name}** (${vis}${lang}, ★${r.stargazers_count ?? 0})${desc}\n  ${r.html_url}`;
}

export function registerRepoTools(server: McpServer): void {
  // --- github_get_authenticated_user ---
  const WhoAmISchema = z.object({ response_format: responseFormatSchema });
  server.registerTool(
    "github_get_authenticated_user",
    {
      title: "Get Authenticated User",
      description: `Get the profile of the user the GITHUB_TOKEN belongs to ("who am I").

Use this first to confirm authentication works and to discover the account's login (username), which you need as the 'owner' for other tools.

Args:
  - response_format ('markdown' | 'json'): Output format (default: markdown)

Returns: login, name, profile URL, public repo count, follower/following counts.

Examples:
  - Use when: "What's my GitHub username?" or before creating a repo under your account.`,
      inputSchema: WhoAmISchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ response_format }) => {
      try {
        const { data } = await githubRequest<GitHubUser>("/user");
        const out = {
          login: data.login,
          name: data.name ?? null,
          company: data.company ?? null,
          public_repos: data.public_repos ?? 0,
          followers: data.followers ?? 0,
          following: data.following ?? 0,
          url: data.html_url,
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `# ${out.login}${out.name ? ` (${out.name})` : ""}\n- Public repos: ${out.public_repos}\n- Followers: ${out.followers} · Following: ${out.following}\n- ${out.url}`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_list_repos ---
  const ListReposSchema = z.object({
    visibility: z
      .enum(["all", "public", "private"])
      .default("all")
      .describe("Filter by repository visibility (default: all)"),
    affiliation: z
      .enum(["owner", "collaborator", "organization_member"])
      .optional()
      .describe("Limit to a relationship type (optional; default returns all affiliations)"),
    sort: z
      .enum(["created", "updated", "pushed", "full_name"])
      .default("full_name")
      .describe("Sort field (default: full_name)"),
    ...paginationFields,
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_list_repos",
    {
      title: "List My Repositories",
      description: `List repositories accessible to the authenticated user.

Args:
  - visibility ('all' | 'public' | 'private'): default 'all'
  - affiliation ('owner' | 'collaborator' | 'organization_member'): optional
  - sort ('created' | 'updated' | 'pushed' | 'full_name'): default 'full_name'
  - per_page (number 1-100, default 30), page (number, default 1)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { total_in_page, page, has_more, repos: [{ full_name, private, description, language, stars, forks, open_issues, default_branch, url, updated_at }] }

Examples:
  - "List my private repos" -> visibility='private'
  - "Show my most recently pushed repos" -> sort='pushed'`,
      inputSchema: ListReposSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ visibility, affiliation, sort, per_page, page, response_format }) => {
      try {
        const params: Record<string, unknown> = { visibility, sort, per_page, page };
        if (affiliation) params.affiliation = affiliation;
        const { data, link } = await githubRequest<GitHubRepo[]>(
          "/user/repos",
          "GET",
          undefined,
          params
        );
        const out = {
          total_in_page: data.length,
          page,
          has_more: hasNextPage(link),
          repos: data.map(repoSummary),
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : data.length
            ? `# Repositories (page ${page}, ${data.length} shown${out.has_more ? ", more available" : ""})\n\n${data.map(repoLine).join("\n")}`
            : "No repositories found.";
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_get_repo ---
  const GetRepoSchema = z.object({ ...repoFields, response_format: responseFormatSchema });
  server.registerTool(
    "github_get_repo",
    {
      title: "Get Repository",
      description: `Get details for a single repository.

Args:
  - owner (string): repo owner login
  - repo (string): repo name
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: full_name, visibility, description, language, stars, forks, open issues, default branch, URL.

Examples:
  - "Show details for octocat/hello-world" -> owner='octocat', repo='hello-world'`,
      inputSchema: GetRepoSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, response_format }) => {
      try {
        const { data } = await githubRequest<GitHubRepo>(`/repos/${owner}/${repo}`);
        const out = repoSummary(data);
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `# ${out.full_name} (${out.private ? "private" : "public"})\n${out.description ?? "_No description_"}\n\n- Language: ${out.language ?? "n/a"}\n- ★ ${out.stars} · Forks ${out.forks} · Open issues ${out.open_issues}\n- Default branch: ${out.default_branch ?? "n/a"}\n- ${out.url}`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_create_repo ---
  const CreateRepoSchema = z.object({
    name: z
      .string()
      .min(1)
      .max(100)
      .describe("Name for the new repository (e.g. 'tocflow')"),
    description: z.string().max(350).optional().describe("Short repository description"),
    private: z
      .boolean()
      .default(false)
      .describe("Create as private? (default false = public)"),
    auto_init: z
      .boolean()
      .default(true)
      .describe("Initialize with an empty commit (creates default branch). Default true."),
    gitignore_template: z
      .string()
      .optional()
      .describe("Optional .gitignore template name, e.g. 'Node'"),
    license_template: z
      .string()
      .optional()
      .describe("Optional license keyword, e.g. 'gpl-2.0' or 'mit'"),
    org: z
      .string()
      .optional()
      .describe("Create under this organization instead of the authenticated user"),
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_create_repo",
    {
      title: "Create Repository",
      description: `Create a new repository under the authenticated user (or an organization).

This WRITES to GitHub. The repo is owned by the token's account unless 'org' is set.

Args:
  - name (string, required): repository name
  - description (string, optional)
  - private (boolean): default false (public)
  - auto_init (boolean): default true (creates an initial commit + default branch so you can immediately add files)
  - gitignore_template (string, optional): e.g. 'Node'
  - license_template (string, optional): e.g. 'gpl-2.0'
  - org (string, optional): create under an org instead of your account
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { full_name, private, default_branch, url }

Examples:
  - "Create a public repo called tocflow" -> name='tocflow', private=false
  - Returns 422 if a repo with that name already exists.`,
      inputSchema: CreateRepoSchema.shape,
      annotations: {
        readOnlyHint: false,
        destructiveHint: false,
        idempotentHint: false,
        openWorldHint: true,
      },
    },
    async ({ name, description, private: isPrivate, auto_init, gitignore_template, license_template, org, response_format }) => {
      try {
        const body: Record<string, unknown> = {
          name,
          private: isPrivate,
          auto_init,
        };
        if (description) body.description = description;
        if (gitignore_template) body.gitignore_template = gitignore_template;
        if (license_template) body.license_template = license_template;

        const endpoint = org ? `/orgs/${org}/repos` : "/user/repos";
        const { data } = await githubRequest<GitHubRepo>(endpoint, "POST", body);
        const out = {
          full_name: data.full_name,
          private: data.private,
          default_branch: data.default_branch ?? "main",
          url: data.html_url,
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `Created **${out.full_name}** (${out.private ? "private" : "public"}).\nDefault branch: ${out.default_branch}\n${out.url}`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );
}
