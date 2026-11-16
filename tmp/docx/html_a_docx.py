from pathlib import Path
from lxml import html
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.style import WD_STYLE_TYPE
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path(__file__).resolve().parents[2]
INPUT = ROOT / 'tmp' / 'docx' / 'informe.html'
OUTPUT = ROOT / 'output' / 'word' / 'Informe_Tema_2_2_Direcciones_Maykel_Menendez_Con_Contexto.docx'
OUTPUT.parent.mkdir(parents=True, exist_ok=True)

doc = Document()
sec = doc.sections[0]
sec.page_width = Inches(8.5)
sec.page_height = Inches(11)
sec.top_margin = Inches(.75)
sec.bottom_margin = Inches(.70)
sec.left_margin = Inches(.73)
sec.right_margin = Inches(.73)
sec.header_distance = Inches(.35)
sec.footer_distance = Inches(.35)

styles = doc.styles
normal = styles['Normal']
normal.font.name = 'Arial'
normal.font.size = Pt(10.5)
normal.font.color.rgb = RGBColor(36, 49, 58)
normal.paragraph_format.space_after = Pt(8)
normal.paragraph_format.line_spacing = 1.25

def mkstyle(name, size, color='24495B', bold=False, before=0, after=7):
    s = styles.add_style(name, WD_STYLE_TYPE.PARAGRAPH)
    s.base_style = normal
    s.font.name = 'Arial'
    s.font.size = Pt(size)
    s.font.bold = bold
    s.font.color.rgb = RGBColor.from_string(color)
    s.paragraph_format.space_before = Pt(before)
    s.paragraph_format.space_after = Pt(after)
    return s

title = styles['Title']
title.font.name = 'Arial'
title.font.size = Pt(25)
title.font.bold = True
title.font.color.rgb = RGBColor(0, 0, 0)
title.paragraph_format.space_after = Pt(17)
title.paragraph_format.line_spacing = 1.1
title_ppr = title._element.get_or_add_pPr()
for border in title_ppr.findall(qn('w:pBdr')):
    title_ppr.remove(border)
mkstyle('Report Heading 1', 17, bold=True, before=0, after=11)
mkstyle('Report Heading 2', 12, bold=True, before=13, after=6)
mkstyle('Report Kicker', 11, '648F80', True, after=15)
mkstyle('Report Subtitle', 13, '24313A', False, after=28)
mkstyle('Report Field', 10, '24495B', True, before=16, after=1)
mkstyle('Report Value', 12, '24313A', False, after=5)
mkstyle('Report Small', 9, '53636B', False, after=7)
mkstyle('Report Caption', 9, '53636B', False, after=6)
code_style = mkstyle('Report Code', 8, '24313A', False, before=5, after=10)
code_style.font.name = 'Consolas'
code_style.paragraph_format.line_spacing = 1.0
mkstyle('Report Diagram', 9, '24313A', False, before=8, after=12)

def set_shading(cell, color):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:fill'), color)
    tc_pr.append(shd)

def set_borders(cell, color='C6D1D3'):
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = OxmlElement('w:tcBorders')
    for edge in ('top','left','bottom','right'):
        el = OxmlElement('w:'+edge)
        el.set(qn('w:val'),'single')
        el.set(qn('w:sz'),'4')
        el.set(qn('w:color'),color)
        borders.append(el)
    tc_pr.append(borders)

def set_cell_margins(cell):
    tc_pr = cell._tc.get_or_add_tcPr()
    mar = OxmlElement('w:tcMar')
    for edge, val in [('top','90'),('start','95'),('bottom','90'),('end','95')]:
        el = OxmlElement('w:'+edge)
        el.set(qn('w:w'),val)
        el.set(qn('w:type'),'dxa')
        mar.append(el)
    tc_pr.append(mar)

def add_inline(paragraph, node, bold=False, mono=False):
    if node.text:
        run = paragraph.add_run(node.text)
        run.bold = bold
        if mono: run.font.name = 'Consolas'
    for child in node:
        tag = child.tag.lower() if isinstance(child.tag,str) else ''
        if tag == 'br':
            paragraph.add_run().add_break()
        else:
            add_inline(paragraph, child, bold or tag in ('b','strong'), mono or tag == 'code')
        if child.tail:
            run = paragraph.add_run(child.tail)
            run.bold = bold
            if mono: run.font.name = 'Consolas'

def add_p(node, style=None, align=None):
    p = doc.add_paragraph(style=style)
    if align is not None: p.alignment = align
    add_inline(p,node)
    return p

def add_pre(node):
    p = doc.add_paragraph(style='Report Code')
    p.paragraph_format.left_indent = Inches(.12)
    p.paragraph_format.right_indent = Inches(.12)
    shd = OxmlElement('w:shd')
    shd.set(qn('w:fill'),'F4F7F8')
    p._p.get_or_add_pPr().append(shd)
    for i, line in enumerate((node.text_content() or '').split('\n')):
        if i: p.add_run().add_break()
        r = p.add_run(line)
        r.font.name = 'Consolas'
        r.font.size = Pt(8)

def add_table(node):
    rows = node.xpath('./tr')
    if not rows: rows = node.xpath('./tbody/tr')
    cols = len(rows[0].xpath('./th|./td'))
    t = doc.add_table(rows=len(rows), cols=cols)
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    t.autofit = False
    widths = []
    for col in node.xpath('./colgroup/col'):
        w = col.attrib.get('style','').split('width:')[-1].split('%')[0]
        try: widths.append(float(w))
        except ValueError: pass
    available = 7.04
    for r_idx, row in enumerate(rows):
        cells = row.xpath('./th|./td')
        for c_idx, source in enumerate(cells):
            cell = t.cell(r_idx,c_idx)
            if c_idx < len(widths): cell.width = Inches(available*widths[c_idx]/100)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            set_borders(cell)
            set_cell_margins(cell)
            set_shading(cell, '24495B' if r_idx==0 else ('F4F7F8' if r_idx%2==0 else 'FFFFFF'))
            p = cell.paragraphs[0]
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.line_spacing = 1.15
            add_inline(p,source)
            for run in p.runs:
                run.font.name = 'Arial'
                run.font.size = Pt(8.3)
                if r_idx==0:
                    run.font.bold = True
                    run.font.color.rgb = RGBColor(255,255,255)
    header = t.rows[0]._tr.get_or_add_trPr()
    el = OxmlElement('w:tblHeader')
    el.set(qn('w:val'),'true')
    header.append(el)
    doc.add_paragraph().paragraph_format.space_after = Pt(1)

body = html.fromstring(INPUT.read_text(encoding='utf-8')).find('body')
for node in body:
    tag = node.tag.lower() if isinstance(node.tag,str) else ''
    cls = node.attrib.get('class','')
    if tag=='div' and cls=='cover':
        doc.add_paragraph().paragraph_format.space_after = Pt(70)
        for pnode in node.xpath('./p'):
            c = pnode.attrib.get('class','')
            style = {'kicker':'Report Kicker','title':'Title','subtitle':'Report Subtitle','field':'Report Field','value':'Report Value'}.get(c)
            add_p(pnode,style,WD_ALIGN_PARAGRAPH.CENTER)
    elif tag=='div' and cls=='break':
        doc.add_page_break()
    elif tag=='h1': add_p(node,'Report Heading 1')
    elif tag=='h2': add_p(node,'Report Heading 2')
    elif tag=='p':
        style = {'small':'Report Small','caption':'Report Caption','diagram':'Report Diagram'}.get(cls)
        p = add_p(node, style, WD_ALIGN_PARAGRAPH.CENTER if cls in ('caption','diagram') else None)
        if cls=='diagram':
            shd = OxmlElement('w:shd')
            shd.set(qn('w:fill'),'F4F7F8')
            p._p.get_or_add_pPr().append(shd)
    elif tag=='pre': add_pre(node)
    elif tag=='table': add_table(node)
    elif tag=='img':
        case = Path(node.attrib['src']).name
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.add_run().add_picture(str(ROOT/'tmp'/'docx'/case),width=Inches(6.85))
        p.paragraph_format.space_after = Pt(3)

footer = sec.footer.paragraphs[0]
footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = footer.add_run('Tema 2.2  ·  Registro de dirección de entrega')
run.font.name = 'Arial'
run.font.size = Pt(8)
run.font.color.rgb = RGBColor(83,99,107)

doc.core_properties.title = 'Registro de dirección de entrega y contrato JSON'
doc.core_properties.author = 'Maykel Josué Menéndez Vélez'
doc.core_properties.subject = 'Práctica individual Tema 2.2'
doc.save(OUTPUT)
print(OUTPUT)
