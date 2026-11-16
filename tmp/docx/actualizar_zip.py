from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[2]
old = root / 'entrega-practica-direcciones-sqlite.zip'
new = root / 'entrega-practica-direcciones-sqlite-con-contexto.zip'

with ZipFile(old) as source, ZipFile(new, 'w', ZIP_DEFLATED) as target:
    for name in source.namelist():
        path = root / name
        if path.is_file():
            target.write(path, name)
    for name in (
        'evidencias/postman-p1.png',
        'evidencias/postman-p2.png',
        'evidencias/postman-p3.png',
        'output/word/Informe_Tema_2_2_Direcciones_Maykel_Menendez_Con_Contexto.docx',
    ):
        target.write(root / name, name)

with ZipFile(new) as check:
    assert check.testzip() is None
    for name in (
        'README.md',
        'evidencias/postman-p1.png',
        'evidencias/postman-p2.png',
        'evidencias/postman-p3.png',
        'output/word/Informe_Tema_2_2_Direcciones_Maykel_Menendez_Con_Contexto.docx',
    ):
        assert name in check.namelist(), name
    assert '.env' not in check.namelist()
    assert 'database/database.sqlite' not in check.namelist()
    print(new, 'archivos:', len(check.namelist()))
