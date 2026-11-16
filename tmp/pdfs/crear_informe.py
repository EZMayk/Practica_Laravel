from pathlib import Path
import json
import re

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, Image, Flowable, KeepTogether, Preformatted,
)


ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output' / 'pdf' / 'Informe_Tema_2_2_Direcciones_Maykel_Menendez.pdf'
OUT.parent.mkdir(parents=True, exist_ok=True)

pdfmetrics.registerFont(TTFont('Arial', r'C:\Windows\Fonts\arial.ttf'))
pdfmetrics.registerFont(TTFont('Arial-Bold', r'C:\Windows\Fonts\arialbd.ttf'))
pdfmetrics.registerFont(TTFont('Consolas', r'C:\Windows\Fonts\consola.ttf'))

NAVY = colors.HexColor('#24495B')
GREEN = colors.HexColor('#618D7D')
PALE = colors.HexColor('#EAF1F0')
INK = colors.HexColor('#223039')
MUTED = colors.HexColor('#53636B')
GRID = colors.HexColor('#C6D1D3')
LIGHT = colors.HexColor('#F6F8F8')
PAGE_W, PAGE_H = letter
LEFT = 45
RIGHT = 45
CONTENT_W = PAGE_W - LEFT - RIGHT

styles = {
    'cover_kicker': ParagraphStyle('cover_kicker', fontName='Arial-Bold', fontSize=11, leading=16, textColor=GREEN, alignment=TA_CENTER, spaceAfter=14),
    'cover_title': ParagraphStyle('cover_title', fontName='Arial-Bold', fontSize=24, leading=30, textColor=NAVY, alignment=TA_CENTER, spaceAfter=14),
    'cover_sub': ParagraphStyle('cover_sub', fontName='Arial', fontSize=12, leading=18, textColor=INK, alignment=TA_CENTER, spaceAfter=23),
    'cover_label': ParagraphStyle('cover_label', fontName='Arial-Bold', fontSize=10, leading=14, textColor=NAVY, alignment=TA_CENTER, spaceAfter=4),
    'cover_value': ParagraphStyle('cover_value', fontName='Arial', fontSize=12, leading=18, textColor=INK, alignment=TA_CENTER, spaceAfter=22),
    'h1': ParagraphStyle('h1', fontName='Arial-Bold', fontSize=16, leading=21, textColor=NAVY, spaceAfter=12),
    'h2': ParagraphStyle('h2', fontName='Arial-Bold', fontSize=11.5, leading=16, textColor=NAVY, spaceBefore=9, spaceAfter=6),
    'body': ParagraphStyle('body', fontName='Arial', fontSize=9.5, leading=14, textColor=INK, spaceAfter=9),
    'small': ParagraphStyle('small', fontName='Arial', fontSize=8.5, leading=12, textColor=INK, spaceAfter=7),
    'caption': ParagraphStyle('caption', fontName='Arial', fontSize=8, leading=11, textColor=MUTED, alignment=TA_CENTER, spaceBefore=5, spaceAfter=10),
    'cell': ParagraphStyle('cell', fontName='Arial', fontSize=8.1, leading=11, textColor=INK),
    'cell_head': ParagraphStyle('cell_head', fontName='Arial-Bold', fontSize=8.1, leading=11, textColor=colors.white),
    'mono': ParagraphStyle('mono', fontName='Consolas', fontSize=7.8, leading=11, textColor=INK),
}


def para(text, style='body'):
    return Paragraph(text, styles[style])


def heading(text):
    return para(text, 'h1')


def subheading(text):
    return para(text, 'h2')


def table(headers, rows, widths):
    data = [[para(h, 'cell_head') for h in headers]]
    data.extend([[para(str(v), 'cell') for v in row] for row in rows])
    t = Table(data, colWidths=widths, repeatRows=1, hAlign='LEFT')
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), NAVY),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, LIGHT]),
        ('GRID', (0, 0), (-1, -1), 0.45, GRID),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('LEFTPADDING', (0, 0), (-1, -1), 7),
        ('RIGHTPADDING', (0, 0), (-1, -1), 7),
        ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
    ]))
    return t


def code(obj):
    if isinstance(obj, str):
        content = obj
    else:
        content = json.dumps(obj, ensure_ascii=False, indent=2)
    pre = Preformatted(content, styles['mono'])
    box = Table([[pre]], colWidths=[CONTENT_W])
    box.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), LIGHT),
        ('BOX', (0, 0), (-1, -1), 0.5, GRID),
        ('LEFTPADDING', (0, 0), (-1, -1), 10),
        ('RIGHTPADDING', (0, 0), (-1, -1), 10),
        ('TOPPADDING', (0, 0), (-1, -1), 8),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
    ]))
    return box


class FlowDiagram(Flowable):
    def __init__(self):
        super().__init__()
        self.width = CONTENT_W
        self.height = 74

    def draw(self):
        c = self.canv
        labels = ['Cliente web\no móvil', 'API\nLaravel', 'Form\nRequest', 'Controlador\ny modelo', 'SQLite', 'Respuesta\nJSON']
        widths = [73, 65, 76, 94, 56, 78]
        gap = (self.width - sum(widths)) / (len(widths) - 1)
        x = 0
        for i, (label, w) in enumerate(zip(labels, widths)):
            c.setFillColor(PALE if i not in (0, 5) else colors.white)
            c.setStrokeColor(GREEN)
            c.roundRect(x, 20, w, 42, 6, fill=1, stroke=1)
            c.setFillColor(NAVY)
            c.setFont('Arial-Bold', 7.7)
            lines = label.split('\n')
            for j, line in enumerate(lines):
                c.drawCentredString(x + w / 2, 42 - j * 10, line)
            if i < len(labels) - 1:
                c.setStrokeColor(NAVY)
                c.line(x + w + 2, 41, x + w + gap - 3, 41)
                c.line(x + w + gap - 6, 44, x + w + gap - 3, 41)
                c.line(x + w + gap - 6, 38, x + w + gap - 3, 41)
            x += w + gap


def header_footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(GREEN)
    canvas.setLineWidth(2)
    canvas.line(LEFT, PAGE_H - 38, PAGE_W - RIGHT, PAGE_H - 38)
    canvas.setFillColor(MUTED)
    canvas.setFont('Arial', 8)
    canvas.drawString(LEFT, PAGE_H - 29, 'Tema 2.2  |  Contratos JSON y dirección de entrega')
    canvas.line(LEFT, 38, PAGE_W - RIGHT, 38)
    canvas.drawString(LEFT, 25, 'Maykel Josué Menéndez Vélez  |  Octavo semestre, paralelo A')
    canvas.drawRightString(PAGE_W - RIGHT, 25, str(doc.page))
    canvas.restoreState()


def cover(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(NAVY)
    canvas.rect(0, PAGE_H - 19, PAGE_W * .38, 19, fill=1, stroke=0)
    canvas.setFillColor(GREEN)
    canvas.rect(PAGE_W * .65, PAGE_H - 19, PAGE_W * .35, 19, fill=1, stroke=0)
    canvas.setFillColor(NAVY)
    canvas.rect(0, 0, PAGE_W * .25, 13, fill=1, stroke=0)
    canvas.setFillColor(GREEN)
    canvas.rect(PAGE_W * .72, 0, PAGE_W * .28, 13, fill=1, stroke=0)
    canvas.restoreState()


class ReportDoc(BaseDocTemplate):
    def __init__(self, filename):
        super().__init__(str(filename), pagesize=letter, leftMargin=LEFT, rightMargin=RIGHT,
                         topMargin=58, bottomMargin=55, title='Práctica individual Tema 2.2 - Dirección de entrega',
                         author='Maykel Josué Menéndez Vélez')
        frame = Frame(LEFT, 55, CONTENT_W, PAGE_H - 113, leftPadding=0, rightPadding=0,
                      topPadding=0, bottomPadding=0)
        self.addPageTemplates(PageTemplate(id='all', frames=[frame], onPage=self.page_art))

    def page_art(self, canvas, doc):
        if doc.page == 1:
            cover(canvas, doc)
        else:
            header_footer(canvas, doc)


def parse_evidence(case):
    text = (ROOT / 'evidencias' / f'{case}-http.txt').read_text(encoding='utf-8-sig')
    sent = re.search(r'^Solicitud JSON: (.+)$', text, re.MULTILINE)
    status = re.search(r'^HTTP/1\.1 (\d{3}) ([^\r\n]+)', text, re.MULTILINE)
    payload = next(line for line in reversed(text.splitlines()) if line.startswith('{'))
    assert sent and status
    return json.loads(sent.group(1)), int(status.group(1)), json.loads(payload)


p1_req, p1_status, p1_res = parse_evidence('p1')
p2_req, p2_status, p2_res = parse_evidence('p2')
p3_req, p3_status, p3_res = parse_evidence('p3')
assert [p1_status, p2_status, p3_status] == [201, 422, 422]
assert p1_res['data']['etiqueta'] == 'Casa de prueba'
assert 'etiqueta' in p2_res['errors'] and 'lat' in p3_res['errors']
counts = (ROOT / 'evidencias' / 'conteos-sqlite.txt').read_text(encoding='utf-8-sig')
assert '2 -> 3' in counts and counts.count('3 -> 3') == 2

story = []

# Cubierta
story += [Spacer(1, 65), para('PRÁCTICA INDIVIDUAL  ·  TEMA 2.2', 'cover_kicker'),
          para('Capa de intercambio de información semántica', 'cover_title'),
          para('Registro de dirección de entrega mediante contratos JSON, Laravel y SQLite', 'cover_sub'),
          Spacer(1, 27), para('Autor', 'cover_label'), para('Maykel Josué Menéndez Vélez', 'cover_value'),
          para('Docente', 'cover_label'), para('Junior Zamora', 'cover_value'),
          para('Curso', 'cover_label'), para('Octavo semestre · Paralelo A', 'cover_value'),
          para('Asignatura', 'cover_label'), para('Integración e Implementación de Software', 'cover_value'),
          Spacer(1, 18), para('Práctica independiente basada en el proceso de entrega del proyecto de aula.', 'cover_sub'),
          PageBreak()]

# Introducción y arquitectura
story += [heading('1. Introducción y propósito'),
          para('Esta práctica demuestra cómo un cliente web o móvil puede enviar una dirección de entrega en JSON y cómo una API Laravel interpreta, valida, guarda y devuelve información siguiendo un contrato explícito. El proceso elegido es <b>registrar una dirección de entrega</b> para pedidos a domicilio.'),
          para('Es una adaptación educativa reducida. En el sistema original la dirección pertenece a un cliente autenticado; aquí no hay cuentas ni autenticación. Esta API no está conectada al sistema original.'),
          subheading('Objetivo general'),
          para('Implementar y comprobar el intercambio de datos JSON para registrar una dirección válida y rechazar solicitudes incorrectas con errores por campo.'),
          subheading('Objetivos específicos'),
          para('• Definir contratos de solicitud y respuesta con nombres, tipos, obligatoriedad y significado.<br/>• Validar campos, longitudes y coordenadas antes de persistir.<br/>• Verificar HTTP 201, HTTP 422 y la ausencia de registros ante errores.'),
          subheading('Actores y operación'),
          table(['Actor o componente', 'Responsabilidad'], [
              ['Cliente web o móvil', 'Envía JSON de una dirección y consume la respuesta. Los clientes no se implementan en esta práctica.'],
              ['API Laravel', 'Recibe POST /api/v1/clientes/direcciones y coordina la operación.'],
              ['Form Request', 'Valida datos obligatorios, tipos, límites y rangos.'],
              ['SQLite', 'Guarda únicamente las direcciones aceptadas.'],
          ], [148, CONTENT_W - 148]),
          subheading('Diagrama de la arquitectura'), FlowDiagram(),
          para('<b>Endpoint único de la práctica:</b> POST /api/v1/clientes/direcciones.', 'small'),
          PageBreak()]

# Contrato de solicitud
story += [heading('2. Contrato JSON de solicitud'),
          para('La petición debe enviar <b>Content-Type: application/json</b>. Los campos opcionales pueden omitirse o enviarse como <b>null</b>. Los límites de texto siguen la referencia del DTO Java del dominio original.'),
          table(['Campo', 'Tipo JSON', 'Oblig.', 'Restricción', 'Significado'], [
              ['etiqueta', 'string', 'Sí', 'No vacío; máximo 40 caracteres', 'Nombre corto de la dirección'],
              ['direccion', 'string', 'Sí', 'No vacío; máximo 200 caracteres', 'Dirección escrita'],
              ['telefono', 'string / null', 'No', 'Máximo 20 caracteres', 'Contacto de la entrega'],
              ['observaciones', 'string / null', 'No', 'Máximo 200 caracteres', 'Instrucciones adicionales'],
              ['referencia', 'string / null', 'No', 'Máximo 200 caracteres', 'Punto de referencia'],
              ['lat', 'number / null', 'No', 'Entre -90 y 90, inclusive', 'Latitud'],
              ['lng', 'number / null', 'No', 'Entre -180 y 180, inclusive', 'Longitud'],
          ], [75, 76, 48, 161, 162]),
          Spacer(1, 13),
          para('Para las coordenadas se exige un número JSON real: <b>1.5</b> es válido como tipo, mientras <b>"1.5"</b> y <b>"norte"</b> se rechazan. La aceptación del valor numérico depende además de su rango.'),
          subheading('Solicitud mínima válida, usada en P1'), code(p1_req),
          subheading('Solicitud válida con campos opcionales'),
          code({'etiqueta':'Oficina de prueba','direccion':'Avenida Imaginaria 45',
                'telefono':'0990000000','observaciones':'Portería ficticia',
                'referencia':'Edificio de prueba','lat':-0.2,'lng':-78.5}),
          PageBreak()]

# Contrato de respuesta
story += [heading('3. Contrato JSON de respuesta'),
          para('Una creación correcta devuelve <b>HTTP 201 Created</b>. La respuesta tiene un objeto exterior con la clave <b>data</b>; dentro aparecen exactamente los campos siguientes. Los opcionales no enviados se representan como null.'),
          table(['Campo en data', 'Tipo JSON', 'Oblig.', 'Significado'], [
              ['id', 'integer', 'Sí', 'Identificador asignado por SQLite'],
              ['etiqueta', 'string', 'Sí', 'Nombre corto guardado'],
              ['direccion', 'string', 'Sí', 'Dirección guardada'],
              ['telefono', 'string / null', 'Sí', 'Contacto o null'],
              ['observaciones', 'string / null', 'Sí', 'Instrucciones o null'],
              ['referencia', 'string / null', 'Sí', 'Referencia o null'],
              ['lat', 'number / null', 'Sí', 'Latitud o null'],
              ['lng', 'number / null', 'Sí', 'Longitud o null'],
              ['activa', 'boolean', 'Sí', 'true al registrar'],
              ['creadoAt', 'string', 'Sí', 'Fecha ISO 8601 con zona horaria'],
          ], [101, 98, 57, 266]),
          subheading('Respuesta real observada en P1'), code(p1_res), Spacer(1, 10),
          para('El identificador y la marca temporal cambian entre ejecuciones. En esta respuesta, el desplazamiento <b>+00:00</b> indica UTC.'),
          PageBreak()]

# Validación e implementación
story += [heading('4. Implementación en Laravel'),
          para('Laravel deserializa el cuerpo JSON de la solicitud. <b>StoreDireccionEntregaRequest</b> aplica la validación antes de ejecutar el controlador. El controlador guarda solo <b>$request-&gt;validated()</b> mediante el modelo. <b>DireccionEntregaResource</b> selecciona los campos públicos y Laravel serializa ese arreglo como JSON bajo <b>data</b>.'),
          table(['Componente', 'Archivo', 'Función'], [
              ['Ruta', 'routes/api.php', 'Expone solo POST /api/v1/clientes/direcciones para esta práctica.'],
              ['Validación', 'app/Http/Requests/StoreDireccionEntregaRequest.php', 'Controla obligatoriedad, tipo, longitud y coordenadas.'],
              ['Controlador', 'app/Http/Controllers/Api/DireccionEntregaController.php', 'Crea la dirección validada y devuelve 201.'],
              ['Modelo', 'app/Models/DireccionEntrega.php', 'Limita los atributos asignables y define tipos de salida.'],
              ['Recurso JSON', 'app/Http/Resources/DireccionEntregaResource.php', 'Define exactamente la respuesta.'],
              ['Persistencia', 'database/migrations/2026_11_15_234102_create_direccion_entregas_table.php', 'Crea la tabla SQLite.'],
          ], [88, 220, 214]),
          subheading('Errores controlados'),
          para('Si una regla falla, Laravel devuelve <b>HTTP 422 Unprocessable Content</b> con <b>message</b> y <b>errors</b>, donde cada clave identifica el campo inválido. No se ejecuta el registro.'),
          code(p2_res), Spacer(1, 10),
          para('La validación rechaza también textos vacíos, límites excedidos, coordenadas fuera de rango y cadenas numéricas como "1.5". Esos casos se comprobaron en la suite automatizada.'),
          PageBreak()]


def evidence_page(case, title, explanation, expected):
    request, status, response = parse_evidence(case)
    postman_images = list((ROOT / 'evidencias').glob(f'postman-{case}.*'))
    image_path = postman_images[0] if postman_images else ROOT / 'evidencias' / f'{case}-captura.jpg'
    assert image_path.is_file()
    image_width, image_height = ImageReader(str(image_path)).getSize()
    figure_width = min(CONTENT_W, 395 * image_width / image_height)
    figure_height = figure_width * image_height / image_width
    figure = Image(str(image_path), width=figure_width, height=figure_height, hAlign='CENTER')
    caption = (f'Figura {case.upper()}. Captura real de Postman/Thunder Client: solicitud, estado y respuesta.'
               if postman_images else
               f'Figura {case.upper()}. Captura real en VS Code del registro HTTP obtenido con curl.exe; se ven solicitud, estado y respuesta. Esta captura no procede de Postman.')
    story.extend([
        heading(title),
        para(explanation),
        table(['Dato verificado', 'Resultado real'], [
            ['Método y ruta', 'POST /api/v1/clientes/direcciones'],
            ['Estado HTTP', f'{status} {expected}'],
            ['Dato clave', ('data.id = ' + str(response['data']['id'])) if case == 'p1' else ('errors.' + ('etiqueta' if case == 'p2' else 'lat'))],
        ], [130, CONTENT_W - 130]),
        Spacer(1, 15),
        figure,
        para(caption, 'caption'),
        PageBreak(),
    ])


evidence_page('p1', '5. Prueba P1: registro válido',
              'Se envió el JSON mínimo con etiqueta y direccion. El Form Request lo aceptó, la API guardó una dirección ficticia en SQLite y devolvió el objeto data definido por el recurso JSON.',
              'Created')
evidence_page('p2', '6. Prueba P2: falta etiqueta',
              'La solicitud omitió etiqueta. Laravel devolvió errors.etiqueta con HTTP 422. El total de direcciones permaneció en tres, por lo que no se registró la solicitud inválida.',
              'Unprocessable Content')
evidence_page('p3', '7. Prueba P3: tipo incorrecto',
              'La solicitud envió lat como el texto "norte". La validación rechazó el tipo y devolvió errors.lat con HTTP 422. El total de direcciones permaneció en tres.',
              'Unprocessable Content')

# Cierre
story += [heading('8. Resultados, reproducción y reflexión'),
          para('Antes de P1 había dos direcciones ficticias en el archivo SQLite. P1 elevó el total a tres; P2 y P3 lo dejaron en tres. Las pruebas específicas pasaron <b>8 pruebas y 74 aserciones</b>; la suite completa pasó <b>10 pruebas y 76 aserciones</b>. Las pruebas automatizadas usan SQLite en memoria y no alteran el archivo local.'),
          table(['Caso', 'Entrada', 'Estado', 'Persistencia'], [
              ['P1', 'JSON mínimo válido', '201', '2 → 3 registros'],
              ['P2', 'Sin etiqueta', '422', '3 → 3 registros'],
              ['P3', 'lat = "norte"', '422', '3 → 3 registros'],
          ], [56, 230, 64, 172]),
          subheading('Cómo repetir la práctica'),
          para('Desde la raíz del código fuente, instalar dependencias con <b>composer install</b>, crear <b>.env</b> a partir de <b>.env.example</b>, generar APP_KEY, crear <b>database/database.sqlite</b> y ejecutar <b>php artisan migrate</b>. Levantar la API con <b>php artisan serve --host=127.0.0.1 --port=8000</b>. Los tres cuerpos JSON y las órdenes curl están en <b>examples/</b> y en el README. Repetir la suite con <b>php artisan test --compact</b>.'),
          subheading('Entrega del código'),
          para('El proyecto funcional, el README, las pruebas y las evidencias se entregan en <b>entrega-practica-direcciones-sqlite.zip</b>. El archivo .env y la base SQLite local no se incluyen; el README explica cómo prepararlos. No se presenta un enlace Git porque esta carpeta no contiene metadatos de repositorio.'),
          subheading('Reflexión individual'),
          para('<b>Problema de integración:</b> un cliente web y uno móvil necesitan interpretar los mismos nombres de campos, tipos y respuestas. El contrato JSON establece esa interpretación común.'),
          para('<b>Decisión técnica:</b> usé Form Request para validar antes de guardar y API Resource para controlar la salida. SQLite mantiene la práctica pequeña e independiente.'),
          para('<b>Verificación:</b> comprobé P1, P2 y P3 con HTTP real y ejecuté la suite automatizada. Las dos solicitudes inválidas devolvieron 422 por campo sin crear registros.'),
          para('El documento de ejemplo facilitado se usó como referencia de estructura. El proceso, contrato, código y resultados de este informe corresponden a esta práctica individual.', 'small')]

ReportDoc(OUT).build(story)
print(str(OUT))
