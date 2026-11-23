---
paths:
  - "docs/posts/**"
  - ".claude/skills/new-blog-post/**"
---

# Matt Hummel Journal: editorial rules (this repo)

What the Journal covers and how a post is shaped. Voice, reading level, glossary and the one CTA stay in `content.md`. Drafting steps stay in the `new-blog-post` and `geo-blog-post` skills. If this file conflicts with `content.md`, `content.md` wins on voice and copy location.

## 1. Core positioning and editorial identity

- **Publication name:** The Matt Hummel Journal (`matthummel.com/blog/`).
- **Role:** independent WordPress and web technology field journal. Not a news aggregator or a generic dev blog. Beginner how-tos belong here only when they help a website owner run their own site.
- **Core mission:** explain where the web is going, test the technology firsthand, separate hype from reality, and turn findings into steps a reader can act on.
- **Two audiences, one primary reader per post:**
  - **Business owners (shops):** what a technical shift means for website ownership, revenue, customers, operations, security, and total cost of ownership.
  - **Developers and agencies:** real implementation patterns, architecture trade-offs, security boundaries, and failure modes.
  - Pick the primary reader before drafting (Rule of One). The other audience gets one short section, not half the post.
- **Voice:** welcoming, human, direct. No AI buzzwords, hype, or filler. Reading level and glossary: `content.md`.

## 2. The five editorial pillars

Shares are a rough mix across a quarter, not a quota per month.

1. **The future of the web (~25%):** agentic web, WordPress Abilities API, MCP Adapter, AI search, automation, and digital ownership.
2. **WordPress engineering (~25%):** Gutenberg, Sage themes and block themes, modern plugin architecture, APIs, performance, PHP, security, and WCAG accessibility.
3. **Business technology (~20%):** small business website ownership, SEO and AI search readiness, website handoffs, SaaS sprawl, and tech decisions.
4. **Developer field notes (~20%):** firsthand experiments, AI-assisted development workflows, multi-agent governance, code review standards, and open-source playbooks.
5. **Resources and research (~10%):** ungated checklists, comparison tables, evaluation worksheets, templates, and curated reference libraries.

## 3. Signature format: Field Notes

For posts in the **Field Notes** series (`Field Notes: Research, Test, Explain`), keep this section order.

Open with the bottom line: one or two sentences with the finding and what to do about it, before the first heading.

The names below are section roles. Write the real H2 for each post, as a question where it reads naturally (`seo-analytics` AEO rule).

1. **The discovery:** what changed, what was found, and why it caught attention.
2. **The bigger picture:** plain-language explanation of the technology.
3. **Why businesses should care:** direct operational, financial, or ownership consequences.
4. **The developer perspective:** architecture, implementation details, tooling, and hard limits.
5. **Hands-on testing:** real results from Matt's themes, plugins, or repos: what worked, what failed. Only tests Matt ran. If there is no test yet, ask Matt for the results or leave the section out. Never invent results, numbers, or timings.
6. **Useful resources:** official docs, GitHub repos (`ai-dev-standards-playbook`), and related Journal posts.
7. **My take:** direct, opinionated recommendation on what to do next. It expands the opening bottom line; it does not contradict it.

## Done when

- The post names one primary reader and fits one pillar.
- A Field Notes post opens with the bottom line and has no test results Matt did not supply.
