from pathlib import Path
from html import escape
from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
TMP = ROOT / 'tmp' / 'docx'
TMP.mkdir(parents=True, exist_ok=True)

for case in ('p1', 'p2', 'p3'):
    source = ROOT / 'evidencias' / f'postman-{case}.png'
    assert source.exists(), source
    image = Image.open(source).convert('RGB')
    # El recorte conserva íntegra el área de solicitud, estado y respuesta.
    image.crop((315, 73, 1025, 695)).save(TMP / f'postman-{case}-recorte.png')


def img(case):
    return (TMP / f'postman-{case}-recorte.png').as_uri()


def code(value):
    return '<pre>' + escape(value) + '</pre>'


def table(headers, rows, widths=None):
    colgroup = ''
    if widths:
        colgroup = '<colgroup>' + ''.join(f'<col style="width:{w}%">' for w in widths) + '</colgroup>'
    head = '<tr>' + ''.join(f'<th>{escape(x)}</th>' for x in headers) + '</tr>'
    body = ''.join('<tr>' + ''.join(f'<td>{escape(str(x))}</td>' for x in row) + '</tr>' for row in rows)
    return '<table>' + colgroup + head + body + '</table>'


parts = []
parts.append('''
<html><head><meta charset="utf-8"><style>
@page { size: 8.5in 11in; margin: 0.75in 0.73in 0.70in 0.73in; }
body { font-family: Arial, sans-serif; font-size: 10.5pt; line-height: 1.28; color: #24313a; }
h1 { font-family: Arial, sans-serif; font-size: 17pt; color: #24495b; margin: 0 0 11pt 0; }
h2 { font-family: Arial, sans-serif; font-size: 12pt; color: #24495b; margin: 15pt 0 6pt 0; }
p { margin: 0 0 9pt 0; }
.cover { text-align: center; padding-top: 85pt; }
.kicker { color: #648f80; font-size: 11pt; font-weight: bold; letter-spacing: 1pt; }
.title { color: #24495b; font-size: 26pt; font-weight: bold; line-height: 1.1; margin: 22pt 0; }
.subtitle { font-size: 13pt; margin-bottom: 55pt; }
.field { font-weight: bold; color: #24495b; margin-top: 17pt; margin-bottom: 2pt; }
.value { font-size: 12pt; }
.break { page-break-before: always; }
.small { font-size: 9pt; }
.caption { text-align: center; font-size: 9pt; color: #53636b; margin-top: 5pt; }
table { border-collapse: collapse; width: 100%; margin: 8pt 0 13pt 0; font-size: 8.5pt; }
th { background-color: #24495b; color: white; text-align: left; font-weight: bold; }
td, th { border: 0.5pt solid #c6d1d3; padding: 6pt 7pt; vertical-align: middle; }
tr:nth-child(odd) td { background-color: #f4f7f8; }
pre { font-family: Consolas, monospace; font-size: 8pt; line-height: 1.2; background-color: #f4f7f8; padding: 9pt; border: 0.5pt solid #c6d1d3; white-space: pre-wrap; }
.evidence { width: 6.9in; height: auto; display: block; margin: 11pt auto 0 auto; }
.diagram { font-family: Consolas, monospace; font-size: 9pt; background-color: #f4f7f8; padding: 11pt; text-align: center; }
</style></head><body>
''')

parts.append('''<div class="cover">
<p class="kicker">PRÁCTICA INDIVIDUAL DE INTEGRACIÓN DE SISTEMAS</p>
<p class="title">Registro de dirección de entrega<br>y contrato JSON</p>
<p class="subtitle">Laravel, API REST y SQLite<br>Tema 2.2</p>
<p class="field">Estudiante</p><p class="value">Maykel Josué Menéndez Vélez</p>
<p class="field">Curso</p><p class="value">Octavo semestre, paralelo A</p>
<p class="field">Docente</p><p class="value">Junior Zamora</p>
<p class="field">Entrega</p><p class="value">Código funcional, README y evidencias de Postman</p>
</div>''')

parts.append('''<div class="break"></div><h1>1 Introducción y alcance</h1>
<p>Esta práctica implementa el registro de una dirección de entrega para pedidos a domicilio mediante <b>POST /api/v1/clientes/direcciones</b>. Un cliente web o móvil envía JSON; Laravel interpreta los valores, los valida, guarda la dirección en SQLite y devuelve un JSON definido por un API Resource.</p>
<p>Es una adaptación educativa independiente del sistema de restaurante. En el sistema original, la dirección pertenece a un cliente autenticado. Aquí se omiten las cuentas y la autenticación para concentrar la práctica en el contrato de entrada, la validación, la persistencia y el contrato de salida. La práctica no está conectada al sistema original y no afirma que ambas APIs sean idénticas.</p>
<h2>Objetivo</h2>
<p>Comprobar que una solicitud válida produce HTTP 201 y una respuesta estructurada, mientras que una solicitud sin un campo obligatorio o con un tipo incorrecto produce HTTP 422 con errores por campo, sin crear registros.</p>
<h2>Proceso, actores y recorrido</h2>
<p><b>Proceso:</b> registrar una dirección de entrega. <b>Actores:</b> cliente web o móvil y API Laravel. <b>Base de datos:</b> SQLite local para la ejecución y SQLite en memoria para las pruebas automatizadas.</p>
<p class="diagram">Cliente web o móvil → API Laravel → Form Request → controlador y modelo → SQLite<br>Cliente web o móvil ← JSON de respuesta ← API Resource ← dirección registrada</p>
<p>El Form Request se ejecuta antes del controlador. Si los datos son válidos, el controlador guarda solo los valores validados. El API Resource selecciona los campos públicos y Laravel genera el JSON final.</p>
<h2>Referencia del proceso del restaurante</h2>
<p>En el proyecto de aula, el cliente autenticado desde la web o la aplicación móvil registra una dirección que podrá usar para pedidos a domicilio. Esta práctica usa ese proceso como referencia funcional. La correspondencia es conceptual y se basa en la descripción del proyecto y en los límites del DTO Java proporcionados; no se inspeccionó ni se modificó el sistema original.</p>
''')
parts.append(table(['Referencia del proyecto de aula', 'Componente de esta práctica'], [
    ('Cliente web o móvil envía una dirección', 'Postman simula al cliente y envía JSON a la ruta POST'),
    ('DTO Java: etiqueta 40, direccion 200, telefono 20, observaciones y referencia 200', 'StoreDireccionEntregaRequest aplica esos límites y valida tipos, obligatoriedad y coordenadas'),
    ('Dirección asociada al cliente autenticado', 'Se omiten cuentas, autenticación y cliente_id para centrarse en el contrato'),
    ('Registro de la dirección para un futuro pedido a domicilio', 'DireccionEntregaController y modelo guardan la dirección en SQLite; no se implementan pedidos'),
    ('El cliente necesita leer la dirección registrada', 'DireccionEntregaResource define la respuesta pública bajo data'),
], [45,55]))

parts.append('<div class="break"></div><h1>2 Contratos JSON</h1><h2>Contrato de solicitud</h2>')
parts.append('<p>El cliente envía <b>Content-Type: application/json</b> y <b>Accept: application/json</b>. Los campos opcionales pueden omitirse o enviarse como <code>null</code>. Los límites de texto siguen el DTO Java usado como referencia del dominio.</p>')
parts.append(table(['Campo', 'Tipo JSON', 'Obligatorio', 'Restricciones', 'Significado'], [
    ('etiqueta','string','Sí','No vacío; máximo 40 caracteres','Nombre corto'),
    ('direccion','string','Sí','No vacío; máximo 200 caracteres','Dirección escrita'),
    ('telefono','string o null','No','Máximo 20 caracteres','Contacto'),
    ('observaciones','string o null','No','Máximo 200 caracteres','Instrucciones'),
    ('referencia','string o null','No','Máximo 200 caracteres','Punto de referencia'),
    ('lat','number o null','No','Número JSON entre -90 y 90; cadena numérica rechazada','Latitud'),
    ('lng','number o null','No','Número JSON entre -180 y 180; cadena numérica rechazada','Longitud'),
], [15,18,15,29,23]))
parts.append('<h2>Contrato de respuesta</h2><p>La respuesta correcta usa un envoltorio <code>data</code>. Todos los campos de la tabla aparecen dentro de ese objeto; los opcionales omitidos se devuelven como <code>null</code>.</p>')
parts.append(table(['Campo en data','Tipo JSON','Obligatorio','Significado'], [
    ('id','integer','Sí','Identificador generado por SQLite'),
    ('etiqueta','string','Sí','Nombre corto guardado'),
    ('direccion','string','Sí','Dirección guardada'),
    ('telefono','string o null','Sí','Contacto o null'),
    ('observaciones','string o null','Sí','Instrucciones o null'),
    ('referencia','string o null','Sí','Referencia o null'),
    ('lat','number o null','Sí','Latitud o null'),
    ('lng','number o null','Sí','Longitud o null'),
    ('activa','boolean','Sí','true al registrar'),
    ('creadoAt','string','Sí','Fecha ISO 8601 con desplazamiento horario'),
], [24,20,16,40]))

parts.append('''<div class="break"></div><h1>3 Implementación y ejemplos</h1>
<p>Laravel deserializa el cuerpo JSON y lo entrega a <b>StoreDireccionEntregaRequest</b>. Allí se comprueban obligatoriedad, tipo y longitud. <code>lat</code> y <code>lng</code> aceptan números JSON dentro de sus rangos; también se rechaza <code>"lat":"1.5"</code> porque es una cadena JSON. Si hay errores, Laravel devuelve HTTP 422 antes de guardar.</p>
<p><b>DireccionEntregaController</b> usa <code>$request-&gt;validated()</code> para crear el modelo <b>DireccionEntrega</b>. La migración define la tabla <code>direccion_entregas</code>. <b>DireccionEntregaResource</b> construye la salida y Laravel la serializa como JSON bajo <code>data</code> con HTTP 201.</p>
<h2>Solicitud válida mínima</h2>''')
parts.append(code('''{
  "etiqueta": "Casa de prueba",
  "direccion": "Calle Ficticia 123"
}'''))
parts.append('<h2>Respuesta observada en Postman para P1</h2>')
parts.append(code('''{
  "data": {
    "id": 5,
    "etiqueta": "Casa de prueba",
    "direccion": "Calle Ficticia 123",
    "telefono": null,
    "observaciones": null,
    "referencia": null,
    "lat": null,
    "lng": null,
    "activa": true,
    "creadoAt": "2026-11-16T03:48:06+00:00"
  }
}'''))
parts.append('<p class="small">El identificador y la fecha cambian en otra ejecución. En la captura inicial aportada por el estudiante, P1 creó el registro con id 4; al repetir el caso en Postman para guardar la evidencia local, se creó el id 5.</p>')
parts.append('<h2>Error de validación</h2>')
parts.append(code('''{
  "message": "The etiqueta field is required.",
  "errors": {
    "etiqueta": ["The etiqueta field is required."]
  }
}'''))
parts.append('<p>El código HTTP es <b>422 Unprocessable Content</b>. La estructura estable es <code>message</code> y <code>errors</code> con listas por campo; el texto de los mensajes puede depender del idioma de Laravel.</p>')

for number, case, title, interpretation in [
    ('4','p1','P1 Solicitud válida','El cuerpo contiene los dos campos obligatorios. Postman muestra HTTP 201 Created y el objeto data con id 5, valores guardados, campos opcionales null, activa true y creadoAt en formato ISO 8601.'),
    ('5','p2','P2 Falta etiqueta','El JSON envía direccion pero omite etiqueta. Postman muestra HTTP 422 Unprocessable Content y errors.etiqueta; la validación impide llegar al guardado.'),
    ('6','p3','P3 Tipo incorrecto en lat','El JSON envía lat como texto "norte". Postman muestra HTTP 422 Unprocessable Content y errors.lat; la validación impide llegar al guardado.'),
]:
    parts.append(f'<div class="break"></div><h1>{number} Evidencia de {escape(title)}</h1>')
    parts.append(f'<p>{escape(interpretation)}</p>')
    parts.append(f'<img class="evidence" src="{img(case)}">')
    parts.append(f'<p class="caption">Figura {escape(number)}. Recorte de captura auténtica de Postman: solicitud, estado HTTP y respuesta.</p>')
    parts.append(f'<p class="small">Captura completa disponible en <b>evidencias/postman-{case}.png</b>.</p>')

parts.append('''<div class="break"></div><h1>7 Resultados y verificación</h1>
<p>Las tres capturas de Postman muestran la solicitud y la respuesta correspondiente. Las pruebas automatizadas verifican adicionalmente los datos persistidos y que las solicitudes inválidas no insertan filas. La ejecución previa por HTTP con curl registró conteos de SQLite de 2→3 para P1 y 3→3 para P2 y P3, conservados en <code>evidencias/conteos-sqlite.txt</code>. Tras repetir P1 en Postman para este informe, el archivo local contiene 5 direcciones ficticias.</p>''')
parts.append(table(['Caso','Entrada','HTTP observado','Resultado'], [
    ('P1','etiqueta y direccion válidas','201 Created','data con id y fecha; registro creado'),
    ('P2','falta etiqueta','422 Unprocessable Content','errors.etiqueta; sin registro nuevo'),
    ('P3','lat = "norte"','422 Unprocessable Content','errors.lat; sin registro nuevo'),
], [12,29,26,33]))
parts.append('<p>Se ejecutó <code>php artisan test --compact tests/Feature/RegistrarDireccionEntregaTest.php</code>: <b>8 pruebas aprobadas y 74 aserciones</b>. También se ejecutó <code>php artisan test --compact</code>: <b>10 pruebas aprobadas y 76 aserciones</b>. Ambas suites usan SQLite en memoria y no alteran el archivo de la base local.</p>')
parts.append('<h2>Cómo repetir la práctica</h2>')
parts.append(code('''composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env; php artisan key:generate --no-interaction }
if (-not (Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File | Out-Null }
php artisan config:clear --no-interaction
php artisan migrate --no-interaction
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction'''))
parts.append('<p class="small">Comandos PowerShell desde la raíz del repositorio. En <code>.env</code>, usar <code>DB_CONNECTION=sqlite</code> y no fijar <code>DB_DATABASE</code>. En Postman, usar POST, la URL indicada, Body → raw → JSON y los encabezados Content-Type y Accept como application/json. Los cuerpos de P1, P2 y P3 también están en <code>examples/</code>.</p>')
parts.append('<h2>Archivos de entrega</h2><p>La implementación está en <code>app/Http/Requests/StoreDireccionEntregaRequest.php</code>, <code>app/Http/Controllers/Api/DireccionEntregaController.php</code>, <code>app/Http/Resources/DireccionEntregaResource.php</code>, el modelo, la migración y <code>tests/Feature/RegistrarDireccionEntregaTest.php</code>. El contrato, los comandos y las evidencias se encuentran en <code>README.md</code> y <code>evidencias/</code>. El código se puede entregar mediante <code>entrega-practica-direcciones-sqlite.zip</code>, junto con este informe.</p>')
parts.append('<h2>Reflexión individual</h2><p>El problema de integración consistía en definir cómo el cliente web y el cliente móvil envían e interpretan una dirección de entrega. El contrato JSON fija nombres, tipos, límites y estructura de respuesta. La decisión técnica fue usar un Form Request antes del guardado y un API Resource para la salida. Los resultados P1, P2 y P3, los conteos SQLite registrados y las pruebas automatizadas permiten comprobar el comportamiento. La práctica conserva el proceso de registro, pero omite la cuenta del cliente autenticado del sistema original.</p>')
parts.append('</body></html>')

target = TMP / 'informe.html'
target.write_text('\n'.join(parts), encoding='utf-8')
print(target)
