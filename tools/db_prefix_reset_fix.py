from pathlib import Path

root = Path(__file__).resolve().parents[1]
path = root / "package/pkg_decarodcl.xml"
text = path.read_text(encoding="utf-8")
text = text.replace("com_decarodcl_0.12.2.zip", "com_decarodcl_0.13.0.zip")
text = text.replace("plg_system_decarodcl_0.12.2.zip", "plg_system_decarodcl_0.13.0.zip")
text = text.replace("mod_dcl_matchtimeline_0.12.2.zip", "mod_dcl_matchtimeline_0.13.0.zip")
text = text.replace("mod_dcl_countriesfederations_0.12.2.zip", "mod_dcl_countriesfederations_0.13.0.zip")
path.write_text(text, encoding="utf-8")
print("Competitions 0.13 nested package filenames synchronized")
