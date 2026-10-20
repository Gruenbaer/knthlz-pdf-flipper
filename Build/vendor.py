#!/usr/bin/env python3
"""Rebuild bundled runtime assets from exact official npm tarballs."""
import pathlib,json,urllib.request,hashlib,base64,tarfile,io
root=pathlib.Path(__file__).resolve().parents[1]
for row in json.loads((root/'Build/vendor-lock.json').read_text()):
    raw=urllib.request.urlopen(row['tarball'],timeout=60).read()
    algorithm,digest=row['integrity'].split('-',1)
    if algorithm!='sha512' or base64.b64encode(hashlib.sha512(raw).digest()).decode()!=digest or hashlib.sha256(raw).hexdigest()!=row['sha256']:raise SystemExit('npm archive integrity mismatch')
    pdf=row['package']=='pdfjs-dist'
    names={'package/LICENSE':'LICENSE'}
    names.update({'package/build/pdf.min.mjs':'pdf.js','package/build/pdf.worker.min.mjs':'pdf.worker.js'} if pdf else {'package/dist/js/page-flip.browser.js':'page-flip.js'})
    out=root/'Resources/Public/Vendor'/('pdfjs' if pdf else 'page-flip')
    files=0
    with tarfile.open(fileobj=io.BytesIO(raw),mode='r:gz') as archive:
        for member in archive.getmembers():
            if not member.isfile():continue
            name=names.get(member.name)
            if pdf and any(member.name.startswith('package/'+directory+'/') for directory in ['cmaps','standard_fonts','wasm']):name=member.name[len('package/'):]
            if name:
                relative=pathlib.PurePosixPath(name)
                if relative.is_absolute() or '..' in relative.parts:raise SystemExit('Unsafe upstream path')
                target=out/name;target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(archive.extractfile(member).read());files+=1
    if files!=row['files']:raise SystemExit('Upstream file inventory mismatch')
    print(row['package'],row['version'],'integrity verified,',files,'files')
