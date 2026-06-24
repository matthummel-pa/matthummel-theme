# github-mcp-server

An [MCP](https://modelcontextprotocol.io) server that exposes the **GitHub REST API**
as tools — repositories, file contents, issues, pull requests, branches, and search.
It authenticates with your own **personal access token**, so it works even where
OAuth app connectors don't, and everything it creates is owned by your account.

Built with the MCP TypeScript SDK. Runs locally over **stdio**.

---

## Tools

| Tool | Description | Write? |
| --- | --- | --- |
| `github_get_authenticated_user` | Who am I — confirm auth, get your login | read |
| `github_list_repos` | List repositories you can access | read |
| `github_get_repo` | Details for one repository | read |
| `github_create_repo` | Create a new repo (public/private, optional org) | **write** |
| `github_get_file` | Read a file's decoded contents | read |
| `github_create_or_update_file` | Commit a file (auto-resolves sha on update) | **write** |
| `github_list_issues` | List issues (PRs filtered out) | read |
| `github_create_issue` | Open an issue | **write** |
| `github_list_pull_requests` | List pull requests | read |
| `github_create_pull_request` | Open a pull request | **write** |
| `github_list_branches` | List branches | read |
| `github_search_repositories` | Search repos with GitHub query syntax | read |
| `github_search_code` | Search code (default-branch index) | read |

Every tool supports `response_format: "markdown"` (default) or `"json"`, and the
list/search tools support `page` / `per_page` pagination.

---

## SOP — Setup & use

### 1. Create a personal access token
Go to **GitHub → Settings → Developer settings → Personal access tokens**
(<https://github.com/settings/tokens>) and create one:

- **Fine-grained** (recommended): give it access to the repos you want, with
  **Repository permissions** → Contents (RW), Issues (RW), Pull requests (RW),
  Administration (RW, needed to *create* repos), Metadata (read).
- **Classic**: the `repo` scope (and `read:user`) is enough.

Copy the token — you won't see it again.

### 2. Install & build
```bash
cd github-mcp-server
npm install
npm run build      # compiles to dist/
node dist/index.js --help   # sanity check (no token needed)
```

### 3. Register it with your MCP client
Add the server to your client's MCP config. For **Claude Desktop**
(`claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "github": {
      "command": "node",
      "args": ["C:\\ClaudeCowork\\Projects\\DevProjects\\github-mcp-server\\dist\\index.js"],
      "env": {
        "GITHUB_TOKEN": "ghp_your_token_here"
      }
    }
  }
}
```

Restart the client. The 13 `github_*` tools will appear.

> **Security:** the token is read from the environment only — it is never logged
> (stdio servers log to stderr) and never written to disk by this server. Keep it
> out of source control (`.env` is git-ignored).

### 4. Verify
Ask your client to run **`github_get_authenticated_user`**. It should return your
login — that confirms the token works.

---

## Examples

- **Publish a project:** `github_create_repo` (name `tocflow`, public) →
  `github_create_or_update_file` for each file (README, src/…, etc.).
- **Check a name is free:** `github_search_repositories` with `query="tocflow in:name"`.
- **Triage:** `github_list_issues` with `state="open", labels="bug"`.
- **Open a PR:** `github_create_pull_request` with `head="feat/x", base="main"`.

---

## Configuration

| Env var | Required | Purpose |
| --- | --- | --- |
| `GITHUB_TOKEN` | yes | Personal access token (also accepts `GITHUB_PERSONAL_ACCESS_TOKEN`) |
| `GITHUB_API_URL` | no | Override API base for GitHub Enterprise Server |

---

## Development

```bash
npm run dev        # tsx watch mode
npm run build      # type-check + emit dist/
```

Project layout:
```
src/
  index.ts            # entry point, server wiring, stdio transport
  constants.ts        # API URL, limits, timeouts
  types.ts            # GitHub REST object shapes
  schemas/common.ts   # shared Zod fragments (pagination, owner/repo, format)
  services/github.ts  # auth, request client, error handling, result helpers
  tools/              # repos, files, issues, pulls, search
```

## License

MIT
