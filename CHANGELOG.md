# Changelog
* **3.0.1** (2026-10-06)
  - Fixed: channel fields could start saving an option's list position (0, 1, 2…) instead of its text once a new option had been added from an entry — `update_settings_live()` discarded the text-keyed options and the field was then read as a positional list. Options are now always keyed by their text.
  - Fixed: re-saving an entry no longer appends duplicate options; new options are trimmed and added once.
  - Changed: `field_list_items` (EE's standard list setting) is the single stored option list, read like native Select/Checkboxes; `value_label_pairs` and the legacy `options` array are merged in, so fields switched from native fields or not yet re-saved keep all options. `[[Group]]` and `value : label` lines still work.
  - Changed: single-select dropdowns always start with a blank option, so nothing is pre-selected.
  - Fixed: front-end `{option_name}` / `all_options` and the settings form now read the same option list.
* **3.0.0** (2026-07-10)
  - Added: ExpressionEngine 7 and PHP 8 (8.2 / 8.3) compatibility.
  - Added: List/Select field-type compatibility — switch between MX Select Plus and native Select/Checkboxes fields without data loss.
  - Added: Pro/Low Variables support.
  - Changed: multi-value data is stored in EE's standard pipe-delimited format (`encode_multi_field`/`decode_multi_field`), matching native list fields; legacy newline-delimited data is read and upgraded automatically.
  - Fixed: adding new multi-value options could store the literal string "Array"; each value is now registered correctly.
  - Fixed: front-end tag output and saving of legacy multi-value data (newline→pipe normalisation on output; pipe escaping on save).
  - Housekeeping: removed dead/commented-out code and personal dev markers, normalised indentation, property visibility (`var` → `public`) and `elseif`.
  - Maintained fork by Simon Andersohn. Original MIT © Max Lazar.

* **2.2.0** (2020-11-20)
  - Added: EE6 support added
  - Added: Bloqs support
 
* **1.4.0** (2015-04-23)
  - CE released to the wild.
