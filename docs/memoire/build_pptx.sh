#!/usr/bin/env bash
# Génère presentation_soutenance.pptx (images + notes orateur natives).
# Recompile d'abord le PDF propre, puis construit le pptx.
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -d .venv-pptx ]; then
  echo "==> Première utilisation : création de l'environnement Python (.venv-pptx)"
  python3 -m venv .venv-pptx
  .venv-pptx/bin/pip install --quiet -r requirements-pptx.txt
fi

echo "==> Recompilation du PDF propre"
pdflatex -interaction=nonstopmode -halt-on-error presentation_soutenance.tex >/dev/null
pdflatex -interaction=nonstopmode -halt-on-error presentation_soutenance.tex >/dev/null

echo "==> Génération du .pptx (images + notes orateur)"
.venv-pptx/bin/python build_pptx.py

echo "OK : presentation_soutenance.pptx"
