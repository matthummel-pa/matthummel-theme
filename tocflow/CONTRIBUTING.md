# Contributing to TOCflow

Thanks for your interest in improving TOCflow! Contributions of all
kinds are welcome — code, docs, bug reports, and ideas.

## Quick start

1. **Fork** this repository and **clone** your fork.
2. Run `npm install`, then `npm run start` to build in watch mode.
3. Make your change on a feature branch (`feat/...`, `fix/...`, `docs/...`).
4. Run the checks: `npm run lint:js`, `npm run lint:css`, `npm run build`.
5. Open a Pull Request against `main` and fill in the template.

For the full workflow — local WordPress setup, coding standards, testing
checklist, and the release process — see
**[`docs/DEVELOPER_SOP.md`](docs/DEVELOPER_SOP.md)**.

## Ground rules

- Keep PRs small and focused on a single change.
- Match the existing code style (WordPress PHP standards; `@wordpress/scripts`
  for JS/CSS lint + format).
- Escape all output and sanitize all input in PHP.
- Don't commit the `build/` directory — it's generated and git-ignored.
- Be respectful. See [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md).

## Reporting bugs

Open a [GitHub Issue](https://github.com/matthummel-pa/tocflow/issues) using
the **Bug report** template. Include your WordPress version, PHP version, active
theme, and steps to reproduce.

## Proposing features

Check the roadmap in [`CLAUDE.md`](CLAUDE.md) first — some ideas are intentionally
reserved for a future Pro version. Then open a **Feature request** issue to
discuss before building anything large.

## License

By contributing, you agree that your contributions will be licensed under the
**GPL-2.0-or-later** license that covers this project.
