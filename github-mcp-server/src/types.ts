/** Minimal shapes of the GitHub REST objects this server consumes. */

export interface GitHubUser {
  login: string;
  id: number;
  html_url: string;
  name?: string | null;
  company?: string | null;
  public_repos?: number;
  followers?: number;
  following?: number;
}

export interface GitHubRepo {
  id: number;
  name: string;
  full_name: string;
  private: boolean;
  html_url: string;
  description?: string | null;
  fork: boolean;
  default_branch?: string;
  language?: string | null;
  stargazers_count?: number;
  forks_count?: number;
  open_issues_count?: number;
  updated_at?: string;
}

export interface GitHubIssue {
  number: number;
  title: string;
  state: string;
  html_url: string;
  user?: { login: string } | null;
  labels?: Array<{ name: string } | string>;
  comments?: number;
  created_at?: string;
  pull_request?: unknown;
}

export interface GitHubPullRequest {
  number: number;
  title: string;
  state: string;
  html_url: string;
  user?: { login: string } | null;
  head?: { ref: string };
  base?: { ref: string };
  draft?: boolean;
  created_at?: string;
}

export interface GitHubBranch {
  name: string;
  protected?: boolean;
  commit?: { sha: string };
}

export interface GitHubContentFile {
  type: string;
  name: string;
  path: string;
  sha: string;
  size: number;
  encoding?: string;
  content?: string;
  html_url: string;
}

export interface GitHubCommitResponse {
  content: { path: string; sha: string; html_url: string } | null;
  commit: { sha: string; html_url: string };
}
