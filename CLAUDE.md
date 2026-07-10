# MX Select Plus — working notes for Claude

EE fieldtype/celltype: a Chosen-JS–powered select (single/multi), usable in
Channel fields, Matrix/Grid cells, Fluid, Bloqs, and Pro/Low Variables.

## Lineage & license
- Maintained **fork** of `MaxLazar/mx-select-plus`, now `ignetic/mx-select-plus`.
- **MIT** (© 2020 Max Lazar; fork maintained by Simon Andersohn). Retain the notice.
- Keep the `mx_select_plus` **handle** — existing fields depend on it. Do not rename.
- Remotes: `origin` = ignetic (push here), `upstream` = MaxLazar. `gh` defaults to
  upstream, so always pass `--repo ignetic/mx-select-plus` for releases.
- Latest release: **v3.0.0** (semver; supersedes the old `mx.select.plus.1.4.0` tag scheme).

## Repo layout & deploy
- Structure is the **old** EE style: `system/user/mx_select_plus/` (not
  `system/user/addons/`). Modernising the layout is a backlog item.
- Live/test site this is deployed to:
  `~/web/test.victorianemporium7.inetwebmedia.co.uk/system/user/addons/mx_select_plus/`
  and theme assets at `.../public_html/themes/user/mx_select_plus/`.
- Workflow: edit in this repo, `php -l`, then `cp` to the live addon dir and lint
  the live copy, `diff -q` to confirm parity. The repo had **drifted** from live
  before (~217 lines in `ft.mx_select_plus.php`); it was canonicalised on the live
  PHP-8 version on 2026-07-10. Keep them in sync.
- Main file: `system/user/mx_select_plus/ft.mx_select_plus.php` (~1120 lines).

## Data format / backward compat (important)
- Multi-value data is stored in EE's **standard pipe-delimited** format
  (`encode_multi_field`/`decode_multi_field`) — same as native list fields, so a
  field can be switched between MX Select Plus and native Select/Checkboxes without
  data loss.
- **Legacy** newline-delimited data is read via a `str_replace("\n","|")` shim in
  `display_field` and `replace_tag`, and upgraded on re-save. Don't remove the shim.
- Pro/Low Variables support is via `accepts_content_type('low_variables')` + the
  `_var_` methods (`display_var_field`→`display_field`, `save_var_field`→`save`,
  etc.). This is the current implementation — do NOT resurrect the old HEAD~1
  `$type=='lv'`/`_build_settings` code; it's superseded.

## Chosen JS is a CUSTOMISED FORK — do not "upgrade" naively
- Bundled `themes/.../js/chosen.jquery(.min).js` is **Chosen 0.9.12** but PATCHED:
  adds `add_new_options`, `add_new` (hidden `new_field[...]` input injection), and
  `cell_obj` (Matrix/Grid awareness). Look for `// !MX Select Plus change`.
- The addon's "allow new options" feature depends on these hooks. **Stock Chosen
  1.8.7 lacks them all** — a version bump would be a regression. Chosen itself is
  archived/EOL (last release 2017).
- It IS compatible with EE7/jQuery-3 today: the bundle uses only deprecated-but-
  present jQuery APIs (`.bind`/`.unbind`/`$.trim`), none of the removed ones
  (`.live`/`.die`/`$.browser`/`.size`/`.andSelf`).
- Proper modernisation = replace Chosen with a maintained vanilla-JS lib
  (**Tom Select** or **Choices.js**) and re-port the add-new-options + cell support.
  That's a self-contained mini-project, not a version bump. See backlog.

## Open items / backlog
- Modernise the JS layer off EOL Chosen → Tom Select / Choices.js (re-port
  add-new-options, `add_new` hidden input, `cell_obj`). Not a blocker.
- Modernise repo dir structure to `system/user/addons/`.
- Merge fork changes toward `upstream` and coordinate a major release once stable.
- **Not our bug:** editing entry 123 also threw `fieldpack:519`
  `htmlspecialchars_decode(array)` on field 136 (`fieldpack_multiselect`) — a
  third-party Fieldpack issue, unrelated to this addon. Self-resolved after first
  save. Don't chase it here.

## History
See `CHANGELOG.md` (3.0.0 = EE7/PHP8, List/Select compat, Pro/Low Variables, the
pipe-format change, "Array" bug fix, and a housekeeping/formatting pass).
