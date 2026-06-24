import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import {
  githubRequest,
  handleApiError,
  toolResult,
  errorResult,
} from "../services/github.js";
import { ResponseFormat, responseFormatSchema, repoFields } from "../schemas/common.js";
import type { GitHubContentFile, GitHubCommitResponse } from "../types.js";

/** Best-effort lookup of an existing file's blob sha (needed to update a file). */
async function lookupSha(
  owner: string,
  repo: string,
  path: string,
  branch?: string
): Promise<string | undefined> {
  try {
    const { data } = await githubRequest<GitHubContentFile>(
      `/repos/${owner}/${repo}/contents/${encodeURIComponent(path).replace(/%2F/g, "/")}`,
      "GET",
      undefined,
      branch ? { ref: branch } : undefined
    );
    return data.sha;
  } catch {
    return undefined; // File does not exist yet → this will be a create.
  }
}

export function registerFileTools(server: McpServer): void {
  // --- github_get_file ---
  const GetFileSchema = z.object({
    ...repoFields,
    path: z.string().min(1).describe("File path within the repo, e.g. 'src/index.ts'"),
    ref: z
      .string()
      .optional()
      .describe("Branch, tag, or commit SHA to read from (optional; default branch if omitted)"),
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_get_file",
    {
      title: "Get File Contents",
      description: `Read a single file's contents from a repository.

Args:
  - owner (string), repo (string): repository identifiers
  - path (string): file path within the repo
  - ref (string, optional): branch/tag/commit (default branch if omitted)
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { path, sha, size, content } where 'content' is the decoded UTF-8 text.

Examples:
  - "Show package.json from octocat/hello-world" -> path='package.json'
  - Returns 404 if the path does not exist on that ref.`,
      inputSchema: GetFileSchema.shape,
      annotations: {
        readOnlyHint: true,
        destructiveHint: false,
        idempotentHint: true,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, path, ref, response_format }) => {
      try {
        const { data } = await githubRequest<GitHubContentFile>(
          `/repos/${owner}/${repo}/contents/${encodeURIComponent(path).replace(/%2F/g, "/")}`,
          "GET",
          undefined,
          ref ? { ref } : undefined
        );
        if (data.type !== "file" || data.content === undefined) {
          return errorResult(
            `Error: '${path}' is not a file (type: ${data.type}). Use a directory listing tool or specify a file path.`
          );
        }
        const decoded = Buffer.from(data.content, "base64").toString("utf-8");
        const out = { path: data.path, sha: data.sha, size: data.size, content: decoded, url: data.html_url };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `# ${out.path} (${out.size} bytes)\nsha: ${out.sha}\n\n\`\`\`\n${decoded}\n\`\`\``;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );

  // --- github_create_or_update_file ---
  const PutFileSchema = z.object({
    ...repoFields,
    path: z.string().min(1).describe("Destination file path in the repo, e.g. 'README.md'"),
    content: z.string().describe("Full UTF-8 text content of the file (will be base64-encoded for you)"),
    message: z.string().min(1).describe("Commit message"),
    branch: z
      .string()
      .optional()
      .describe("Target branch (optional; repo default branch if omitted)"),
    sha: z
      .string()
      .optional()
      .describe(
        "Blob SHA of the file being replaced. Optional: if omitted the tool looks it up automatically so updates don't fail."
      ),
    response_format: responseFormatSchema,
  });
  server.registerTool(
    "github_create_or_update_file",
    {
      title: "Create or Update File",
      description: `Create a new file or update an existing one via a single commit (Contents API).

This WRITES to GitHub. If the file already exists and no 'sha' is given, the tool fetches the current sha automatically so the update succeeds (otherwise GitHub returns 422).

Args:
  - owner (string), repo (string)
  - path (string): destination path
  - content (string): full file text (base64 encoding handled internally)
  - message (string): commit message
  - branch (string, optional): default branch if omitted
  - sha (string, optional): auto-resolved if omitted
  - response_format ('markdown' | 'json'): default 'markdown'

Returns: { path, commit_sha, content_sha, url }

Examples:
  - "Add a README to me/myrepo" -> path='README.md', content='# Title', message='Add README'
  - Use repeatedly to push many files (one commit each).`,
      inputSchema: PutFileSchema.shape,
      annotations: {
        readOnlyHint: false,
        destructiveHint: true,
        idempotentHint: false,
        openWorldHint: true,
      },
    },
    async ({ owner, repo, path, content, message, branch, sha, response_format }) => {
      try {
        const resolvedSha = sha ?? (await lookupSha(owner, repo, path, branch));
        const body: Record<string, unknown> = {
          message,
          content: Buffer.from(content, "utf-8").toString("base64"),
        };
        if (branch) body.branch = branch;
        if (resolvedSha) body.sha = resolvedSha;

        const { data } = await githubRequest<GitHubCommitResponse>(
          `/repos/${owner}/${repo}/contents/${encodeURIComponent(path).replace(/%2F/g, "/")}`,
          "PUT",
          body
        );
        const out = {
          path: data.content?.path ?? path,
          commit_sha: data.commit.sha,
          content_sha: data.content?.sha ?? null,
          url: data.content?.html_url ?? data.commit.html_url,
          action: resolvedSha ? "updated" : "created",
        };
        const text =
          response_format === ResponseFormat.JSON
            ? JSON.stringify(out, null, 2)
            : `${out.action === "updated" ? "Updated" : "Created"} **${out.path}** in ${owner}/${repo}.\nCommit: ${out.commit_sha}\n${out.url}`;
        return toolResult(text, out);
      } catch (error) {
        return errorResult(handleApiError(error));
      }
    }
  );
}
