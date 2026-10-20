#!/usr/bin/env python3
"""Create a deterministic, extension-root ZIP without local fixtures or secrets."""
import pathlib,sys,zipfile,hashlib,json
root=pathlib.Path(__file__).resolve().parents[1]
if len(sys.argv)!=2: raise SystemExit('Usage: package.py /absolute/path/pdf_flipper_0.1.0.zip')
target=pathlib.Path(sys.argv[1]).resolve()
if target.is_relative_to(root): raise SystemExit('Write the archive outside the extension root')
files=sorted(p for p in root.rglob('*') if p.is_file() and not any(x.startswith('.') or x in {'node_modules','__pycache__'} for x in p.relative_to(root).parts))
for p in files:
 if p.suffix.lower() in {'.env','.pdf','.key','.pem'}: raise SystemExit('Unexpected private/document source file in package: '+p.name)
target.parent.mkdir(parents=True,exist_ok=True)
with zipfile.ZipFile(target,'x',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for p in files:
  info=zipfile.ZipInfo(p.relative_to(root).as_posix(),date_time=(2026,10,20,0,0,0));info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED;z.writestr(info,p.read_bytes())
with zipfile.ZipFile(target) as z:
 assert z.testzip() is None
 assert {'composer.json','ext_emconf.php','LICENSE'} <= set(z.namelist())
print(json.dumps({'artifact':str(target),'files':len(files),'bytes':target.stat().st_size,'sha256':hashlib.sha256(target.read_bytes()).hexdigest()}))
