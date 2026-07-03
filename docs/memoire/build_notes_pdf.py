#!/usr/bin/env python3
"""
Génère notes_imprimables.pdf : le discours complet (tous les \\note{...} du
.tex), en A4, sans les diapositives -- à imprimer ou garder sur un
téléphone/tablette comme filet de sécurité pendant la présentation.

Usage : python3 build_notes_pdf.py
"""
import os
import re
import subprocess
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
TEX = os.path.join(HERE, "presentation_soutenance.tex")
OUT_TEX = os.path.join(HERE, "notes_imprimables.tex")


def strip_braced_command(text, marker):
    idx = text.find(marker)
    if idx == -1:
        return text
    brace_start = text.index("{", idx + len(marker))
    depth = 1
    i = brace_start + 1
    while depth > 0:
        if text[i] == "{":
            depth += 1
        elif text[i] == "}":
            depth -= 1
        i += 1
    return text[:idx] + text[i:]


def extract_braced(text, start_after_brace):
    depth = 1
    i = start_after_brace
    buf = []
    while depth > 0:
        c = text[i]
        if c == "{":
            depth += 1
        elif c == "}":
            depth -= 1
            if depth == 0:
                break
        buf.append(c)
        i += 1
    return "".join(buf), i + 1


def parse_notes_in_order():
    """Returns (total_frame_count, [(slide_number, note_body), ...]) -- slide_number
    matches exactly what LibreOffice Impress / PowerPoint show in the slide panel
    (1..N over ALL slides, title/section dividers included)."""
    with open(TEX, encoding="utf-8") as f:
        src = f.read()
    clean_lines = [ln for ln in src.split("\n") if not ln.lstrip().startswith("%")]
    src_nc = "\n".join(clean_lines)
    src_nc = strip_braced_command(src_nc, r"\newcommand{\sectionslide}[3]")

    frame_re = re.compile(r"\\begin\{frame\}|\\sectionslide\{")
    note_re = re.compile(r"\\note\{")
    events = []
    for m in frame_re.finditer(src_nc):
        events.append((m.start(), "frame", None))
    for m in note_re.finditer(src_nc):
        events.append((m.start(), "note", m.end()))
    events.sort(key=lambda e: e[0])

    frame_index = 0
    notes = []
    for _, kind, payload in events:
        if kind == "frame":
            frame_index += 1
        else:
            content, _ = extract_braced(src_nc, payload)
            notes.append((frame_index, content.strip()))
    return frame_index, notes


def split_label(note_body):
    """Pull the leading \\textbf{[LABEL]}\\\\[Npt] off, return (label, rest)."""
    m = re.match(r"\\textbf\{(\[[^\]]*\])\}\s*\\\\\[\d+pt\]\s*", note_body)
    if m:
        return m.group(1), note_body[m.end():].strip()
    return None, note_body


PREAMBLE = r"""\documentclass[a4paper,11pt]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage[french]{babel}
\usepackage{geometry}
\geometry{top=1.8cm,bottom=1.8cm,left=2.2cm,right=2.2cm}
\usepackage{xcolor}
\usepackage{enumitem}
\usepackage{parskip}
\definecolor{primary}{RGB}{0,82,165}
\definecolor{textdark}{RGB}{18,22,55}
\definecolor{muted}{RGB}{100,100,110}
\pagestyle{empty}
\setlength{\parindent}{0pt}
\newcommand{\notehead}[2]{%
  \par\vspace{14pt}%
  {\color{white}\colorbox{primary}{\parbox{\dimexpr\linewidth-12pt}{\vspace{3pt}%
    \bfseries\large Diapo #1~--- #2\vspace{3pt}}}}%
  \par\vspace{6pt}%
}
\begin{document}
\begin{center}
  {\LARGE\bfseries\color{primary} RansomShield --- Aide-m\'emoire de soutenance}\\[4pt]
  {\large\color{textdark} Discours complet --- 10 min de pr\'esentation + 5 min de d\'emo}\\[2pt]
  {\small\color{muted} Codjo Fr\'ejus Lo\"ic SANGRONIO --- 24 juin 2026}
\end{center}
\vspace{6pt}
{\color{muted}\small\'A lire au calme avant la soutenance ; en s\'eance, s'en servir comme filet de s\'ecurit\'e, pas comme texte \`a r\'eciter mot \`a mot. Le num\'ero \guillemotleft~Diapo N~\guillemotright\ correspond exactement au num\'ero affich\'e dans le panneau de diapositives d'Impress~: si vous \^etes sur la bonne note, vous \^etes sur la bonne diapo.}
\vspace{2pt}
\hrule
"""

POSTAMBLE = r"""
\end{document}
"""


def main():
    total_slides, notes = parse_notes_in_order()
    body_parts = []
    for slide_num, note in notes:
        label, rest = split_label(note)
        if label is None:
            # Q&A note has no leading [LABEL] line in the same form; handle generically
            m = re.match(r"\\textbf\{(\[[^\]]*\])\}\s*", note)
            if m:
                label = m.group(1)
                rest = note[m.end():].strip()
            else:
                label = "[NOTE]"
                rest = note
        label_clean = label.strip("[]")
        body_parts.append(
            f"\\notehead{{{slide_num}/{total_slides}}}{{{label_clean}}}\n{rest}\n"
        )

    tex = PREAMBLE + "\n".join(body_parts) + POSTAMBLE
    with open(OUT_TEX, "w", encoding="utf-8") as f:
        f.write(tex)

    for _ in range(2):
        result = subprocess.run(
            ["pdflatex", "-interaction=nonstopmode", "-halt-on-error", "notes_imprimables.tex"],
            cwd=HERE, capture_output=True, text=True,
        )
        if result.returncode != 0:
            print(result.stdout[-4000:])
            sys.exit("Échec de compilation pdflatex")

    print("OK :", os.path.join(HERE, "notes_imprimables.pdf"), f"({len(notes)} notes)")


if __name__ == "__main__":
    main()
