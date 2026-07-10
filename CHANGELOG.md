# Changelog
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
