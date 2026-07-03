#!/usr/bin/env bash
# Compile la présentation de soutenance en DEUX PDF :
#   presentation_soutenance.pdf        -> PROPRE, sans notes (à projeter : Meet, vidéoprojecteur)
#   presentation_soutenance_notes.pdf  -> AVEC notes orateur (pour vous, en aide-mémoire)
set -euo pipefail
cd "$(dirname "$0")"

echo "==> Version propre (sans notes) : presentation_soutenance.pdf"
pdflatex -interaction=nonstopmode -halt-on-error presentation_soutenance.tex >/dev/null
pdflatex -interaction=nonstopmode -halt-on-error presentation_soutenance.tex >/dev/null

echo "==> Version orateur (avec notes) : presentation_soutenance_notes.pdf"
pdflatex -interaction=nonstopmode -halt-on-error -jobname=presentation_soutenance_notes presentation_soutenance.tex >/dev/null
pdflatex -interaction=nonstopmode -halt-on-error -jobname=presentation_soutenance_notes presentation_soutenance.tex >/dev/null

echo "OK :"
echo "  - presentation_soutenance.pdf       (à projeter)"
echo "  - presentation_soutenance_notes.pdf (pour vous, avec les notes)"
