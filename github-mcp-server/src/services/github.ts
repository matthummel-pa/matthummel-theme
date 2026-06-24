/** Shared GitHub REST API client, error handling, and formatting helpers. */

import axios, { AxiosError, type AxiosRequestConfig } from "axios";
import {
  DEFAULT_API_URL,
  REQUEST_TIMEOUT_MS,
  CHARACTER_LIMIT,
  SERVER_NAME,
  SERVER_VERSION,
} from "../constants.js";

function getToken(): string {
  const token =
    process.env.GITHUB_TOKEN ||
    process.env.GITHUB_PERSONAL_ACCESS_TOKEN ||
    "";
  if (!token) {
    throw new Error(
      "Missing GitHub token. Set the GITHUB_TOKEN environment variable to a personal access token."
    );
  }
  return token;
}

function getApiUrl(): string {
  return (process.env.GITHUB_API_URL || DEFAULT_API_URL).replace(/\/+$/, "");
}

export interface GitHubRequestResult<T> {
  data: T;
  /** Link header (used to detect more pages) if present. */
  link?: string;
  status: number;
}

/**
 * Make an authenticated request to the GitHub REST API.
 * @param endpoint Path beginning with "/" (e.g. "/user/repos").
 */
export async function githubRequest<T>(
  endpoint: string,
  method: "GET" | "POST" | "PUT" | "PATCH" | "DELETE" = "GET",
  body?: unknown,
  params?: Record<string, unknown>
): Promise<GitHubRequestResult<T>> {
  const config: AxiosRequestConfig = {
    method,
    url: `${getApiUrl()}${endpoint}`,
    data: body,
    params,
    timeout: REQUEST_TIMEOUT_MS,
    headers: {
      Authorization: `Bearer ${getToken()}`,
      Accept: "application/vnd.github+json",
      "X-GitHub-Api-Version": "2022-11-28",
      "User-Agent": `${SERVER_NAME}/${SERVER_VERSION}`,
    },
    // We handle non-2xx ourselves for clearer messages.
    validateStatus: (s) => s >= 200 && s < 300,
  };

  const response = await axios.request<T>(config);
  return {
    data: response.data,
    link: response.headers?.link as string | undefined,
    status: response.status,
  };
}

/** Convert any thrown error into a clear, actionable message string. */
export function handleApiError(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const err = error as AxiosError<{ message?: string; errors?: unknown }>;
    if (err.response) {
      const apiMsg = err.response.data?.message;
      const detail = apiMsg ? ` GitHub says: "${apiMsg}".` : "";
      switch (err.response.status) {
        case 401:
          return `Error: Authentication failed (401). Check that GITHUB_TOKEN is valid and not expired.${detail}`;
        case 403:
          return `Error: Forbidden (403). This is often a rate limit or a missing token scope/permission.${detail}`;
        case 404:
          return `Error: Not found (404). Check the owner/repo/path, and that your token can access it.${detail}`;
        case 422:
          return `Error: Validation failed (422). The request was understood but rejected — e.g. the resource already exists or a field is invalid.${detail}`;
        default:
          return `Error: GitHub API request failed with status ${err.response.status}.${detail}`;
      }
    }
    if (err.code === "ECONNABORTED") {
      return "Error: Request timed out. Please try again.";
    }
    return `Error: Network error contacting GitHub: ${err.message}`;
  }
  return `Error: ${error instanceof Error ? error.message : String(error)}`;
}

/** Whether a Link header indicates a next page exists. */
export function hasNextPage(link?: string): boolean {
  return !!link && /rel="next"/.test(link);
}

/**
 * Build a standard tool result, truncating overly large text payloads.
 * Returns the MCP content + structuredContent shape.
 */
export function toolResult(text: string, structured: Record<string, unknown>) {
  let out = text;
  if (out.length > CHARACTER_LIMIT) {
    out =
      out.slice(0, CHARACTER_LIMIT) +
      `\n\n[Output truncated at ${CHARACTER_LIMIT} characters. Use pagination (page/per_page) or filters, or request response_format='json' with narrower scope.]`;
  }
  return {
    content: [{ type: "text" as const, text: out }],
    structuredContent: structured,
  };
}

/** Build an error tool result (isError: true). */
export function errorResult(message: string) {
  return {
    isError: true as const,
    content: [{ type: "text" as const, text: message }],
  };
}
