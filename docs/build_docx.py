#!/usr/bin/env python3
"""Bangun dokumen Word implementasi SIMRS dari sumber Markdown di docs/.

Sumber kebenaran tetap file Markdown dan screenshot:
  - README.md, docs/arsitektur.md, docs/erd.md, docs/api.md, docs/pengujian.md
  - docs/bukti/*.png + tabel keterangan di docs/bukti/README.md
  - diagram Mermaid dirender ke docs/gambar/ lalu disisipkan sebagai gambar

Jalankan dari mana saja:
  python docs/build_docx.py

Kebutuhan: pip install python-docx pillow
"""

from __future__ import annotations

import base64
import re
import sys
import time
import urllib.request
from pathlib import Path

from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.style import WD_STYLE_TYPE
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

ROOT = Path(__file__).resolve().parent
PROJECT = ROOT.parent
BUKTI = ROOT / "bukti"
GAMBAR = ROOT / "gambar"
OUTPUT = ROOT / "Dokumentasi-Implementasi-SIMRS-Inova-Medika.docx"

NAVY = RGBColor(0x1F, 0x38, 0x64)
BLUE = RGBColor(0x2E, 0x54, 0x96)
GRAY = RGBColor(0x59, 0x59, 0x59)
DARK = RGBColor(0x26, 0x26, 0x26)
CODE = RGBColor(0xA3, 0x15, 0x15)

BODY_PT = 10.5
CONTENT_CM = 16.2

STAGE_TITLES = {
    1: "Autentikasi, Hak Akses, dan Tampilan Responsif",
    2: "Master Data",
    3: "Pendaftaran dan Antrean",
    4: "Pemeriksaan dan Rekam Medis Elektronik",
    5: "Farmasi dan Penyerahan Resep",
    6: "Kasir, Tagihan, dan Invoice",
    7: "Laboratorium",
    8: "Laporan Operasional dan Performa",
}

SECTIONS = [
    ("README.md", "Gambaran Umum dan Cara Menjalankan", {"Pengujian", "Dokumen lanjutan"}),
    ("docs/arsitektur.md", "Arsitektur Sistem", set()),
    ("docs/erd.md", "Entity Relationship Diagram (ERD)", set()),
    ("docs/api.md", "Dokumentasi API", set()),
    ("docs/pengujian.md", "Pengujian dan Hasil", set()),
]

# Urutan elemen anak pPr/tcPr/trPr menurut skema OOXML, agar Word tidak protes.
PPR_ORDER = [
    "w:pStyle", "w:keepNext", "w:keepLines", "w:pageBreakBefore", "w:framePr",
    "w:widowControl", "w:numPr", "w:suppressLineNumbers", "w:pBdr", "w:shd",
    "w:tabs", "w:suppressAutoHyphens", "w:kinsoku", "w:wordWrap",
    "w:overflowPunct", "w:topLinePunct", "w:autoSpaceDE", "w:autoSpaceDN",
    "w:bidi", "w:adjustRightInd", "w:snapToGrid", "w:spacing", "w:ind",
    "w:contextualSpacing", "w:mirrorIndents", "w:suppressOverlap", "w:jc",
    "w:textDirection", "w:textAlignment", "w:textboxTightWrap", "w:outlineLvl",
    "w:divId", "w:cnfStyle", "w:rPr", "w:sectPr", "w:pPrChange",
]
TCPR_ORDER = [
    "w:cnfStyle", "w:tcW", "w:gridSpan", "w:hMerge", "w:vMerge", "w:tcBorders",
    "w:shd", "w:noWrap", "w:tcMar", "w:textDirection", "w:tcFitText",
    "w:vAlign", "w:hideMark", "w:headers", "w:cellIns", "w:cellDel",
    "w:cellMerge", "w:tcPrChange",
]
TRPR_ORDER = [
    "w:cnfStyle", "w:divId", "w:gridBefore", "w:gridAfter", "w:wBefore",
    "w:wAfter", "w:cantSplit", "w:trHeight", "w:tblHeader", "w:tblCellSpacing",
    "w:jc", "w:hidden", "w:ins", "w:del", "w:trPrChange",
]


# ---------------------------------------------------------------- XML helpers

def ordered_insert(parent, child, order):
    """Sisipkan child sesuai urutan skema, bukan sekadar append."""
    rank = {qn(name): i for i, name in enumerate(order)}
    child_rank = rank.get(child.tag, len(order))
    for existing in parent:
        if rank.get(existing.tag, len(order)) > child_rank:
            existing.addprevious(child)
            return
    parent.append(child)


def shade_paragraph(par, fill):
    pPr = par._p.get_or_add_pPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), fill)
    ordered_insert(pPr, shd, PPR_ORDER)


def shade_cell(cell, fill):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), fill)
    ordered_insert(tcPr, shd, TCPR_ORDER)


def paragraph_border(par, edge="bottom", color="C9D2E3", size=6, space=4):
    pPr = par._p.get_or_add_pPr()
    pBdr = pPr.find(qn("w:pBdr"))
    if pBdr is None:
        pBdr = OxmlElement("w:pBdr")
        ordered_insert(pPr, pBdr, PPR_ORDER)
    line = OxmlElement(f"w:{edge}")
    line.set(qn("w:val"), "single")
    line.set(qn("w:sz"), str(size))
    line.set(qn("w:space"), str(space))
    line.set(qn("w:color"), color)
    pBdr.append(line)


def table_borders(table, color="C9D2E3"):
    tblPr = table._tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        line = OxmlElement(f"w:{edge}")
        line.set(qn("w:val"), "single")
        line.set(qn("w:sz"), "4")
        line.set(qn("w:space"), "0")
        line.set(qn("w:color"), color)
        borders.append(line)
    tblPr.append(borders)


def remove_table_borders(table):
    tblPr = table._tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        line = OxmlElement(f"w:{edge}")
        line.set(qn("w:val"), "nil")
        borders.append(line)
    tblPr.append(borders)


def table_width_pct(table, pct=100):
    tblPr = table._tbl.tblPr
    tblW = OxmlElement("w:tblW")
    tblW.set(qn("w:w"), str(pct * 50))
    tblW.set(qn("w:type"), "pct")
    tblPr.append(tblW)


def row_properties(row, header=False):
    trPr = row._tr.get_or_add_trPr()
    cant = OxmlElement("w:cantSplit")
    ordered_insert(trPr, cant, TRPR_ORDER)
    if header:
        hdr = OxmlElement("w:tblHeader")
        ordered_insert(trPr, hdr, TRPR_ORDER)


def add_field(par, instruction, placeholder=""):
    run = par.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    begin.set(qn("w:dirty"), "true")
    run._r.append(begin)

    run = par.add_run()
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = f" {instruction} "
    run._r.append(instr)

    run = par.add_run()
    sep = OxmlElement("w:fldChar")
    sep.set(qn("w:fldCharType"), "separate")
    run._r.append(sep)

    if placeholder:
        par.add_run(placeholder)

    run = par.add_run()
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.append(end)


def picture_border(run, color="C9D2E3"):
    inline = run._r.xpath(".//wp:inline")
    if not inline:
        return
    spPr = inline[0].graphic.graphicData.pic.spPr
    line = OxmlElement("a:ln")
    line.set("w", "6350")
    fill = OxmlElement("a:solidFill")
    clr = OxmlElement("a:srgbClr")
    clr.set("val", color)
    fill.append(clr)
    line.append(fill)
    spPr.append(line)


# ------------------------------------------------------------- Markdown parser

HEADING_RE = re.compile(r"^(#{1,6})\s+(.*)$")
BULLET_RE = re.compile(r"^\s*[-*]\s+")
NUMBER_RE = re.compile(r"^\s*\d+\.\s+")


def looks_like_table(lines, index):
    if index + 1 >= len(lines) or "|" not in lines[index]:
        return False
    separator = lines[index + 1].strip()
    if not separator.startswith("|"):
        return False
    if not re.fullmatch(r"\|?[\s:\-|]+\|", separator):
        return False
    return "-" in separator


def split_row(line):
    text = line.strip()
    if text.startswith("|"):
        text = text[1:]
    if text.endswith("|"):
        text = text[:-1]
    return [cell.strip() for cell in text.split("|")]


def parse_md(text):
    blocks = []
    lines = text.splitlines()
    i = 0
    while i < len(lines):
        line = lines[i]

        if line.strip().startswith("```"):
            lang = line.strip()[3:].strip()
            i += 1
            buffer = []
            while i < len(lines) and not lines[i].strip().startswith("```"):
                buffer.append(lines[i])
                i += 1
            i += 1
            blocks.append(("code", lang, "\n".join(buffer)))
            continue

        match = HEADING_RE.match(line)
        if match:
            blocks.append(("h", len(match.group(1)), match.group(2).strip()))
            i += 1
            continue

        if not line.strip():
            i += 1
            continue

        if looks_like_table(lines, i):
            headers = split_row(lines[i])
            i += 2
            rows = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                rows.append(split_row(lines[i]))
                i += 1
            blocks.append(("table", headers, rows))
            continue

        if BULLET_RE.match(line):
            items = []
            while i < len(lines) and BULLET_RE.match(lines[i]):
                items.append(BULLET_RE.sub("", lines[i]).strip())
                i += 1
            blocks.append(("ul", items))
            continue

        if NUMBER_RE.match(line):
            items = []
            while i < len(lines) and NUMBER_RE.match(lines[i]):
                items.append(NUMBER_RE.sub("", lines[i]).strip())
                i += 1
            blocks.append(("ol", items))
            continue

        buffer = [line.strip()]
        i += 1
        while i < len(lines):
            nxt = lines[i]
            if (
                not nxt.strip()
                or HEADING_RE.match(nxt)
                or nxt.strip().startswith("```")
                or BULLET_RE.match(nxt)
                or NUMBER_RE.match(nxt)
                or looks_like_table(lines, i)
            ):
                break
            buffer.append(nxt.strip())
            i += 1
        blocks.append(("p", " ".join(buffer)))

    return blocks


INLINE_RE = re.compile(r"(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\))")


def add_inline(par, text, size=None, bold=False):
    for part in INLINE_RE.split(text):
        if not part:
            continue
        if part.startswith("**") and part.endswith("**"):
            run = par.add_run(part[2:-2])
            run.bold = True
            if size:
                run.font.size = Pt(size)
        elif part.startswith("`") and part.endswith("`"):
            run = par.add_run(part[1:-1])
            run.font.name = "Consolas"
            run.font.size = Pt((size or BODY_PT) - 1.5)
            run.font.color.rgb = CODE
        elif part.startswith("[") and "](" in part:
            label, _, url = part[1:-1].partition("](")
            run = par.add_run(label)
            run.font.color.rgb = BLUE
            run.underline = True
            if size:
                run.font.size = Pt(size)
            if url.startswith("http"):
                tail = par.add_run(f" ({url})")
                tail.font.color.rgb = GRAY
                if size:
                    tail.font.size = Pt(size - 0.5)
        else:
            run = par.add_run(part)
            if bold:
                run.bold = True
            if size:
                run.font.size = Pt(size)


def strip_numbering(text):
    return re.sub(r"^\d+(?:\.\d+)*[.)]?\s+", "", text).strip()


# ------------------------------------------------------------------ Mermaid

def render_mermaid(code, name, width):
    GAMBAR.mkdir(parents=True, exist_ok=True)
    target = GAMBAR / f"{name}.png"
    encoded = base64.urlsafe_b64encode(code.encode("utf-8")).decode("ascii")
    url = (
        f"https://mermaid.ink/img/{encoded}"
        f"?type=png&bgColor=FFFFFF&width={width}"
    )
    last_error = None
    for attempt in range(3):
        try:
            request = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
            data = urllib.request.urlopen(request, timeout=90).read()
            if not data.startswith(b"\x89PNG"):
                raise ValueError("respons bukan PNG")
            target.write_bytes(data)
            print(f"  diagram  {target.name} ({len(data) // 1024} KB)")
            return target
        except Exception as exc:  # layanan render kadang 5xx; coba lagi
            last_error = exc
            time.sleep(3 + attempt * 4)

    if target.exists():  # tetap bisa membangun dokumen saat offline
        print(f"  peringatan: render {name} gagal ({last_error}); memakai gambar lama")
        return target
    raise RuntimeError(f"gagal merender diagram {name}: {last_error}")


# ------------------------------------------------------------------- Builder

class Numbering:
    def __init__(self):
        self.counters = [0, 0, 0]

    def next(self, level):
        index = level - 1
        self.counters[index] += 1
        for rest in range(index + 1, len(self.counters)):
            self.counters[rest] = 0
        return ".".join(str(value) for value in self.counters[:level])


class Builder:
    def __init__(self, doc):
        self.doc = doc
        self.numbering = Numbering()
        self.figures = 0

    # --- teks

    def h1(self, text):
        par = self.doc.add_paragraph(style="Heading 1")
        add_inline(par, f"{self.numbering.next(1)}  {strip_numbering(text)}")
        return par

    def h2(self, text, page_break=False):
        par = self.doc.add_paragraph(style="Heading 2")
        if page_break:
            par.paragraph_format.page_break_before = True
        add_inline(par, f"{self.numbering.next(2)}  {strip_numbering(text)}")
        return par

    def h3(self, text):
        par = self.doc.add_paragraph(style="Heading 3")
        add_inline(par, f"{self.numbering.next(3)}  {strip_numbering(text)}")
        return par

    def h4(self, text):
        par = self.doc.add_paragraph(style="Heading 4")
        add_inline(par, strip_numbering(text))

    def paragraph(self, text, size=None, color=None, italic=False, space_after=None):
        par = self.doc.add_paragraph()
        add_inline(par, text, size=size)
        for run in par.runs:
            if color is not None:
                run.font.color.rgb = color
            if italic:
                run.italic = True
        if space_after is not None:
            par.paragraph_format.space_after = Pt(space_after)
        return par

    def bullets(self, items):
        for item in items:
            par = self.doc.add_paragraph(style="List Bullet")
            add_inline(par, item)

    def numbers(self, items):
        for item in items:
            par = self.doc.add_paragraph(style="List Number")
            add_inline(par, item)

    def code(self, text, size=8.5):
        lines = text.rstrip("\n").split("\n")
        last = None
        for line in lines:
            par = self.doc.add_paragraph(style="Kode")
            par.paragraph_format.space_before = Pt(0)
            par.paragraph_format.space_after = Pt(0)
            run = par.add_run(line if line.strip() else " ")
            run.font.size = Pt(size)
            shade_paragraph(par, "F4F5F7")
            last = par
        if last is not None:
            last.paragraph_format.space_after = Pt(8)

    # --- tabel

    def table(self, headers, rows, size=9.0):
        width = max(len(headers), max((len(row) for row in rows), default=0))
        table = self.doc.add_table(rows=1, cols=width)
        table.style = "Table Grid"
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        table.autofit = True
        table_width_pct(table, 100)
        table_borders(table)

        header_cells = table.rows[0].cells
        for index in range(width):
            cell = header_cells[index]
            shade_cell(cell, "1F3864")
            text = headers[index] if index < len(headers) else ""
            par = cell.paragraphs[0]
            par.paragraph_format.space_before = Pt(1)
            par.paragraph_format.space_after = Pt(1)
            add_inline(par, text, size=size, bold=True)
            for run in par.runs:
                run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
                run.bold = True
        row_properties(table.rows[0], header=True)

        for row_index, values in enumerate(rows, start=1):
            cells = table.add_row().cells
            for index in range(width):
                cell = cells[index]
                if row_index % 2 == 0:
                    shade_cell(cell, "F2F5FA")
                par = cell.paragraphs[0]
                par.paragraph_format.space_before = Pt(1)
                par.paragraph_format.space_after = Pt(1)
                text = values[index] if index < len(values) else ""
                add_inline(par, text, size=size)
            row_properties(table.rows[row_index])

        spacer = self.doc.add_paragraph()
        spacer.paragraph_format.space_after = Pt(4)
        spacer.paragraph_format.space_before = Pt(0)
        return table

    # --- gambar

    def figure(self, path, caption, width_cm=CONTENT_CM):
        self.figures += 1
        par = self.doc.add_paragraph()
        par.alignment = WD_ALIGN_PARAGRAPH.CENTER
        par.paragraph_format.space_before = Pt(4)
        par.paragraph_format.space_after = Pt(2)
        par.paragraph_format.keep_with_next = True
        run = par.add_run()
        run.add_picture(str(path), width=Cm(width_cm))
        picture_border(run)

        cap = self.doc.add_paragraph(style="Keterangan Gambar")
        add_inline(cap, f"Gambar {self.figures}. {caption}", size=8.5)
        cap.paragraph_format.space_after = Pt(10)

    # --- blok Markdown

    def emit(self, blocks, diagram=None, skip_h2=(), heading_shift=0):
        skipping = False
        for block in blocks:
            kind = block[0]

            if kind == "h":
                level = block[1]
                title = block[2]
                if level == 1:
                    continue
                if level == 2 and strip_numbering(title) in skip_h2:
                    skipping = True
                    continue
                if skipping:
                    if level <= 2:
                        skipping = False
                    else:
                        continue
                target = level + heading_shift
                if target == 2:
                    self.h2(title)
                elif target == 3:
                    self.h3(title)
                else:
                    self.h4(title)
                continue

            if skipping:
                continue

            if kind == "p":
                self.paragraph(block[1])
            elif kind == "ul":
                self.bullets(block[1])
            elif kind == "ol":
                self.numbers(block[1])
            elif kind == "table":
                self.table(block[1], block[2])
            elif kind == "code":
                lang, content = block[1], block[2]
                if lang == "mermaid":
                    if diagram:
                        path, caption, width = diagram
                        self.figure(path, caption, width)
                    continue
                self.code(content)


# ------------------------------------------------------------------ dokumen

def setup_styles(doc):
    normal = doc.styles["Normal"]
    normal.font.name = "Calibri"
    normal.font.size = Pt(BODY_PT)
    normal.font.color.rgb = DARK
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing = 1.15
    rPr = normal.element.get_or_add_rPr()
    rFonts = rPr.get_or_add_rFonts()
    rFonts.set(qn("w:eastAsia"), "Calibri")

    headings = {
        1: (18, NAVY, 0, 12, True),
        2: (13.5, BLUE, 14, 6, True),
        3: (11.5, NAVY, 10, 4, True),
        4: (10.5, DARK, 8, 3, True),
    }
    for level, (size, color, before, after, keep) in headings.items():
        style = doc.styles[f"Heading {level}"]
        style.font.name = "Calibri"
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = color
        style.paragraph_format.space_before = Pt(before)
        style.paragraph_format.space_after = Pt(after)
        style.paragraph_format.keep_with_next = keep
        style.paragraph_format.line_spacing = 1.0
    doc.styles["Heading 1"].paragraph_format.page_break_before = True

    caption = doc.styles.add_style("Keterangan Gambar", WD_STYLE_TYPE.PARAGRAPH)
    caption.base_style = doc.styles["Normal"]
    caption.font.size = Pt(8.5)
    caption.font.italic = True
    caption.font.color.rgb = GRAY
    caption.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
    caption.paragraph_format.space_before = Pt(0)
    caption.paragraph_format.space_after = Pt(10)
    caption.paragraph_format.line_spacing = 1.0

    code = doc.styles.add_style("Kode", WD_STYLE_TYPE.PARAGRAPH)
    code.base_style = doc.styles["Normal"]
    code.font.name = "Consolas"
    code.font.size = Pt(8.5)
    code.font.color.rgb = RGBColor(0x1A, 0x1A, 0x1A)
    code.paragraph_format.left_indent = Cm(0.3)
    code.paragraph_format.right_indent = Cm(0.1)
    code.paragraph_format.space_before = Pt(0)
    code.paragraph_format.space_after = Pt(0)
    code.paragraph_format.line_spacing = 1.0


def setup_page(doc):
    section = doc.sections[0]
    section.page_width = Cm(21)
    section.page_height = Cm(29.7)
    section.top_margin = Cm(2.2)
    section.bottom_margin = Cm(2.0)
    section.left_margin = Cm(2.4)
    section.right_margin = Cm(2.4)
    section.header_distance = Cm(1.0)
    section.footer_distance = Cm(1.0)
    section.different_first_page_header_footer = True

    header = section.header.paragraphs[0]
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = header.add_run("Dokumentasi Implementasi  ·  SIMRS Inova Medika")
    run.font.size = Pt(8.5)
    run.font.color.rgb = GRAY
    paragraph_border(header)

    footer = section.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = footer.add_run("Real Case Test — Senior Programmer  ·  Halaman ")
    run.font.size = Pt(8.5)
    run.font.color.rgb = GRAY
    add_field(footer, "PAGE", "1")
    run = footer.add_run(" dari ")
    run.font.size = Pt(8.5)
    run.font.color.rgb = GRAY
    add_field(footer, "NUMPAGES", "1")
    for run in footer.runs:
        run.font.size = Pt(8.5)
        run.font.color.rgb = GRAY


def cover(doc):
    for _ in range(7):
        doc.add_paragraph()

    par = doc.add_paragraph()
    par.paragraph_format.space_after = Pt(8)
    run = par.add_run("REAL CASE TEST — SENIOR PROGRAMMER")
    run.font.size = Pt(10)
    run.bold = True
    run.font.color.rgb = GRAY

    par = doc.add_paragraph()
    par.paragraph_format.space_after = Pt(2)
    run = par.add_run("SIMRS Inova Medika")
    run.font.size = Pt(38)
    run.bold = True
    run.font.color.rgb = NAVY

    par = doc.add_paragraph()
    par.paragraph_format.space_after = Pt(14)
    run = par.add_run(
        "Sistem Informasi Manajemen Rumah Sakit Rawat Jalan Terintegrasi"
    )
    run.font.size = Pt(13.5)
    run.font.color.rgb = BLUE

    par = doc.add_paragraph()
    par.paragraph_format.space_after = Pt(18)
    paragraph_border(par, "bottom", "1F3864", 18, 2)

    par = doc.add_paragraph()
    par.paragraph_format.space_after = Pt(24)
    run = par.add_run("Dokumentasi Implementasi dan Bukti Pengujian")
    run.font.size = Pt(15)
    run.bold = True
    run.font.color.rgb = DARK

    info = [
        (
            "Ruang lingkup",
            "Pendaftaran → pemeriksaan (EMR) → farmasi → laboratorium → "
            "kasir → laporan",
        ),
        (
            "Teknologi",
            "React 19 · TypeScript · Tailwind 4 · Yii2 (PHP 8.3) · "
            "Node.js 20 · PostgreSQL 16",
        ),
        (
            "Pengujian",
            "Codeception (77 test / 470 assertion) · Postman · Playwright",
        ),
        ("Dokumen", "Versi 1.0 · 25 November 2026"),
    ]
    table = doc.add_table(rows=0, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.LEFT
    table.autofit = False
    remove_table_borders(table)
    for label, value in info:
        cells = table.add_row().cells
        cells[0].width = Cm(3.8)
        cells[1].width = Cm(12.0)
        par = cells[0].paragraphs[0]
        par.paragraph_format.space_after = Pt(6)
        run = par.add_run(label)
        run.bold = True
        run.font.size = Pt(9.5)
        run.font.color.rgb = NAVY
        par = cells[1].paragraphs[0]
        par.paragraph_format.space_after = Pt(6)
        add_inline(par, value, size=9.5)

    doc.add_page_break()


def table_of_contents(doc):
    par = doc.add_paragraph()
    par.paragraph_format.space_after = Pt(4)
    run = par.add_run("Daftar Isi")
    run.bold = True
    run.font.size = Pt(18)
    run.font.color.rgb = NAVY
    run.font.name = "Calibri"

    note = doc.add_paragraph()
    note.paragraph_format.space_after = Pt(10)
    run = note.add_run(
        "Daftar isi memuat judul bab dan subbab. Bila belum tampil, klik kanan "
        "lalu pilih Perbarui Bidang."
    )
    run.font.size = Pt(8.5)
    run.italic = True
    run.font.color.rgb = GRAY

    par = doc.add_paragraph()
    add_field(par, r'TOC \o "1-2" \h \z \u', "Memuat daftar isi…")

    settings = doc.settings.element
    update = OxmlElement("w:updateFields")
    update.set(qn("w:val"), "true")
    settings.append(update)


def bukti_reference():
    blocks = parse_md((BUKTI / "README.md").read_text(encoding="utf-8"))
    intro = next(block[1] for block in blocks if block[0] == "p")
    ringkasan = next(
        block[1] for block in blocks if block[0] == "p" and block[1].startswith("Ringkasan performa")
    )
    commands = [block[2] for block in blocks if block[0] == "code"][-1]
    rows = next(block[2] for block in blocks if block[0] == "table")
    shots = [
        (row[0].strip("`"), row[2])
        for row in rows
        if row[0].strip("`").endswith(".png")
    ]
    return intro, ringkasan, commands, shots


def execution_times(path):
    text = path.read_text(encoding="utf-8")
    return [float(value) for value in re.findall(r"Execution Time: ([\d.]+) ms", text)]


def format_ms(value):
    return f"{value:.2f} ms".replace(".", ",")


def evidence(builder):
    doc = builder.doc
    intro, ringkasan, commands, shots = bukti_reference()

    builder.h1("Bukti Implementasi")
    builder.paragraph(intro)
    builder.paragraph(ringkasan)
    builder.paragraph("Cara mengulang bukti:")
    builder.code(commands)

    grouped = {}
    for filename, caption in shots:
        stage = int(filename.split("-")[0])
        grouped.setdefault(stage, []).append((filename, caption))

    for stage in sorted(grouped):
        builder.h2(
            f"Tahap {stage} — {STAGE_TITLES[stage]}",
            page_break=True,
        )
        for filename, caption in grouped[stage]:
            width = 7.0 if "mobile" in filename else CONTENT_CM
            builder.figure(BUKTI / filename, caption, width)

    builder.h2("Transkrip Pengujian Gangguan Integrasi Laboratorium (D5)")
    transcript = parse_md(
        (BUKTI / "7-lab" / "gangguan-integrasi.txt").read_text(encoding="utf-8")
    )
    builder.emit(transcript, heading_shift=1)

    builder.h2("Rencana Eksekusi Query dan Dampak Index (D6)")
    before = execution_times(BUKTI / "8-performa" / "explain-sebelum.txt")
    after = execution_times(BUKTI / "8-performa" / "explain-sesudah.txt")
    builder.paragraph(
        "Pengukuran `EXPLAIN (ANALYZE, BUFFERS)` dilakukan pada 95.005 kunjungan "
        "data performa (prefix `PF-`). Index `resep.kunjungan_id` ditambahkan "
        "sebelum pengukuran kedua."
    )
    labels = [
        "Kunjungan per poli",
        "Obat terpakai (resep ⋈ kunjungan)",
    ]
    rows = []
    for index, label in enumerate(labels):
        if index >= len(before) or index >= len(after):
            continue
        change = (after[index] - before[index]) / before[index] * 100
        rows.append([
            label,
            format_ms(before[index]),
            format_ms(after[index]),
            f"{change:+.1f}%".replace(".", ","),
        ])
    builder.table(
        ["Query", "Sebelum index", "Sesudah index", "Perubahan"],
        rows,
    )

    builder.h3("Rencana eksekusi sebelum index")
    builder.code(
        (BUKTI / "8-performa" / "explain-sebelum.txt").read_text(encoding="utf-8"),
        size=6.5,
    )
    builder.h3("Rencana eksekusi sesudah index")
    builder.code(
        (BUKTI / "8-performa" / "explain-sesudah.txt").read_text(encoding="utf-8"),
        size=6.5,
    )


def finalize_with_word(path):
    """Perbarui field (daftar isi, nomor halaman) memakai Word bila tersedia.

    Tanpa langkah ini dokumen tetap sah: Word akan memperbarui field saat
    dibuka (w:updateFields). Finalisasi hanya agar berkas yang dibagikan
    sudah memuat daftar isi dan nomor halaman yang benar.
    """
    try:
        import win32com.client
    except Exception as exc:
        print(f"  finalisasi Word dilewati ({exc})")
        return

    word = None
    try:
        word = win32com.client.Dispatch("Word.Application")
        word.Visible = False
        word.DisplayAlerts = 0
        document = word.Documents.Open(str(path))
        document.Fields.Update()
        for toc in document.TablesOfContents:
            toc.Update()
            # Rapatkan entri agar 6 bab + 33 subbab muat satu halaman.
            toc.Range.Font.Size = 10
            toc.Range.Font.Bold = False
            for paragraph in toc.Range.Paragraphs:
                paragraph.Format.SpaceBefore = 0
                paragraph.Format.SpaceAfter = 0
                paragraph.Format.LineSpacingRule = 4  # wdLineSpaceExactly
                paragraph.Format.LineSpacing = 12.5
        document.Save()
        document.Close(False)
        print("  daftar isi dan nomor halaman diperbarui via Word")
        return True
    except Exception as exc:
        print(f"  peringatan: finalisasi Word gagal ({exc})")
        return False
    finally:
        if word is not None:
            word.Quit()


def strip_update_fields(path):
    """Jangan paksa Word memperbarui field saat dibuka (TOC sudah final)."""
    document = Document(path)
    settings = document.settings.element
    for element in settings.findall(qn("w:updateFields")):
        settings.remove(element)
    document.save(path)


def main():
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    doc = Document()
    setup_styles(doc)
    setup_page(doc)

    props = doc.core_properties
    props.title = "Dokumentasi Implementasi SIMRS Inova Medika"
    props.subject = "Real Case Test — Senior Programmer"
    props.author = "Inova Medika"
    props.last_modified_by = "Inova Medika"
    props.category = "Dokumentasi teknis"
    props.comments = (
        "Dihasilkan dari Markdown docs/ dan bukti screenshot docs/bukti/ "
        "oleh docs/build_docx.py"
    )

    cover(doc)
    table_of_contents(doc)

    builder = Builder(doc)
    print("Menyiapkan diagram…")
    arsitektur_png = render_mermaid(
        re.search(
            r"```mermaid\n(.*?)```",
            (ROOT / "arsitektur.md").read_text(encoding="utf-8"),
            re.S,
        ).group(1),
        "arsitektur",
        2400,
    )
    erd_png = render_mermaid(
        re.search(
            r"```mermaid\n(.*?)```",
            (ROOT / "erd.md").read_text(encoding="utf-8"),
            re.S,
        ).group(1),
        "erd",
        3000,
    )
    diagrams = {
        "arsitektur": (
            arsitektur_png,
            "Arsitektur sistem: React SPA, nginx, Yii2, PostgreSQL, "
            "dan layanan Node (worker lab + dashboard)",
            CONTENT_CM,
        ),
        "erd": (
            erd_png,
            "Entity relationship diagram SIMRS rawat jalan",
            CONTENT_CM,
        ),
    }

    print("Menyusun isi…")
    for filename, title, skip in SECTIONS:
        builder.h1(title)
        blocks = parse_md((PROJECT / filename).read_text(encoding="utf-8"))
        key = Path(filename).stem
        builder.emit(blocks, diagram=diagrams.get(key), skip_h2=skip)

    evidence(builder)

    doc.save(OUTPUT)
    finalize_with_word(OUTPUT)
    print(f"\nSelesai: {OUTPUT}")
    print(f"Gambar disisipkan: {builder.figures}")


if __name__ == "__main__":
    main()
