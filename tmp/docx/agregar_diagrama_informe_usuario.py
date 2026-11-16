from pathlib import Path
from docx import Document

source = Path(r'C:\Users\mayke\Documents\8vo semestre\Practicas_Integracion\Informe_Tema_2_2_ContratoJSON.docx')
target = Path(__file__).resolve().parents[2] / 'output' / 'word' / 'Informe_Tema_2_2_ContratoJSON_con_diagrama.docx'
target.parent.mkdir(parents=True, exist_ok=True)

doc = Document(source)
diagram = next(p for p in doc.paragraphs if p.text.startswith('Cliente web o móvil → API Laravel → Form Request'))
diagram.clear()
for index, line in enumerate((
    'Cliente web / móvil → API Laravel → Form Request',
    'Datos válidos → servicio de registro* → SQLite',
    'SQLite → API Resource → respuesta JSON 201 → cliente web / móvil',
    'Datos inválidos → errors por campo → respuesta JSON 422 → cliente web / móvil',
)):
    if index:
        diagram.add_run().add_break()
    diagram.add_run(line)

explanation = next(p for p in doc.paragraphs if p.text.startswith('El Form Request se ejecuta antes del controlador.'))
explanation.add_run(' En este diagrama, «servicio de registro» representa la lógica del controlador y el modelo; esta práctica no tiene una clase Service separada. La rama 422 termina antes de acceder a SQLite.')

doc.save(target)
print(target)
