import { z } from "zod";
import { DEFAULT_PER_PAGE, MAX_PER_PAGE } from "../constants.js";

/** Output format shared by all data-returning tools. */
export enum ResponseFormat {
  MARKDOWN = "markdown",
  JSON = "json",
}

export const responseFormatSchema = z
  .nativeEnum(ResponseFormat)
  .default(ResponseFormat.MARKDOWN)
  .describe(
    "Output format: 'markdown' for human-readable text or 'json' for machine-readable data"
  );

/** Pagination fields reused by list/search tools. */
export const paginationFields = {
  per_page: z
    .number()
    .int()
    .min(1)
    .max(MAX_PER_PAGE)
    .default(DEFAULT_PER_PAGE)
    .describe(`Results per page (1-${MAX_PER_PAGE}, default ${DEFAULT_PER_PAGE})`),
  page: z
    .number()
    .int()
    .min(1)
    .default(1)
    .describe("Page number to fetch (1-based, default 1)"),
};

/** owner/repo identifiers reused widely. */
export const repoFields = {
  owner: z
    .string()
    .min(1)
    .describe("Repository owner (user or organization login), e.g. 'octocat'"),
  repo: z.string().min(1).describe("Repository name, e.g. 'hello-world'"),
};
