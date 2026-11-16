# Changelog

## 1.0.2

- Exclude non-automatically gradable questions from deferred-feedback practice sessions.
- Validate all Question Engine sequence-check tokens before processing a practice submission.
- Pass the actual POST payload explicitly to `process_all_actions()`.
- Use a hidden submit marker so form submission is not dependent on the clicked submit button being included.
- Add a clear error for incomplete/stale practice forms.
