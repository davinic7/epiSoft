#!/usr/bin/env bash
# ---------------------------------------------------------------
# Sistema EPI — creación del proyecto en GitHub
#
# Crea etiquetas, milestones, el Project (v2) y los 62 issues
# definidos en backlog.txt.
#
# Requisitos: gh CLI autenticado con permisos de repo y project.
#   gh auth login
#   gh auth refresh -s project,read:project
#
# Uso:
#   ./setup-github-project.sh <owner/repo> ["Título del Project"]
#   ./setup-github-project.sh --dry-run <owner/repo>
# ---------------------------------------------------------------
set -euo pipefail

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then DRY_RUN=1; shift; fi

REPO="${1:-}"
PROJECT_TITLE="${2:-Sistema EPI}"
BACKLOG_FILE="$(dirname "$0")/backlog.txt"

if [[ -z "$REPO" ]]; then
  echo "Uso: $0 [--dry-run] <owner/repo> [\"Título del Project\"]" >&2
  exit 1
fi
if [[ ! -f "$BACKLOG_FILE" ]]; then
  echo "No encuentro backlog.txt junto al script." >&2
  exit 1
fi

OWNER="${REPO%%/*}"
run() { if [[ $DRY_RUN -eq 1 ]]; then echo "  [dry-run] $*"; else "$@"; fi; }

echo "==> Repositorio: $REPO"
[[ $DRY_RUN -eq 1 ]] && echo "==> MODO SIMULACIÓN: no se crea nada"

# ── 1. Etiquetas ────────────────────────────────────────────────
echo "==> Etiquetas"
crear_label() {
  if [[ $DRY_RUN -eq 1 ]]; then echo "  [dry-run] label $1"; return; fi
  gh label create "$1" --repo "$REPO" --color "$2" --description "$3" --force >/dev/null \
    && echo "  ok  $1" || echo "  !!  $1 (omitida)"
}
crear_label "area:core"           "0E8A16" "Base del sistema, transversal"
crear_label "area:ninos"          "1D76DB" "Legajos, salas, asistencia"
crear_label "area:rrhh"           "5319E7" "Personal y documentación"
crear_label "area:economato"      "D93F0B" "Stock, movimientos, menú"
crear_label "area:pedagogico"     "FBCA04" "Registro diario, seguimientos, informes"
crear_label "area:institucional"  "006B75" "Riesgos, articulaciones, derechos"
crear_label "area:alertas"        "B60205" "Motor de alertas y dashboard"
crear_label "area:seguridad"      "000000" "Autenticación, permisos, auditoría"
crear_label "area:infra"          "BFDADC" "Entorno, despliegue, backups"
crear_label "area:ux"             "C5DEF5" "Interfaz y experiencia de uso"
crear_label "prioridad:alta"      "B60205" "Bloqueante para la fase"
crear_label "prioridad:media"     "FBCA04" "Importante, no bloqueante"
crear_label "prioridad:baja"      "C2E0C6" "Deseable"
crear_label "estimación:S"        "EDEDED" "Hasta 1 día"
crear_label "estimación:M"        "EDEDED" "2 a 4 días"
crear_label "estimación:L"        "EDEDED" "1 semana o más"

# ── 2. Milestones ───────────────────────────────────────────────
echo "==> Milestones"
crear_milestone() {
  if [[ $DRY_RUN -eq 1 ]]; then echo "  [dry-run] milestone $1"; return; fi
  gh api "repos/$REPO/milestones" -X POST -f title="$1" -f description="$2" >/dev/null 2>&1 \
    && echo "  ok  $1" || echo "  --  $1 (ya existe)"
}
crear_milestone "M0 · Fundaciones" \
  "Decisiones, esqueleto, seguridad y multi-institución. Nada de negocio hasta que esto cierre."
crear_milestone "M1 · Niños y asistencia" \
  "El núcleo del sistema: legajos, salas, referentes, salud y asistencia diaria."
crear_milestone "M2 · Economato" \
  "Stock por lote, libro de movimientos, menú semanal e inventario patrimonial."
crear_milestone "M3 · Pedagógico" \
  "Registro diario, seguimientos, desafíos del desarrollo e informes."
crear_milestone "M4 · RRHH" \
  "Personal, documentación con vencimientos, capacitaciones y horarios."
crear_milestone "M5 · Institucional y alertas" \
  "Mapa de riesgos, vulneración de derechos, articulaciones y motor de alertas."
crear_milestone "M6 · Endurecimiento y salida a producción" \
  "Backups, despliegue, pruebas, rendimiento y capacitación."

ms_de_fase() {
  case "$1" in
    M0) echo "M0 · Fundaciones" ;;
    M1) echo "M1 · Niños y asistencia" ;;
    M2) echo "M2 · Economato" ;;
    M3) echo "M3 · Pedagógico" ;;
    M4) echo "M4 · RRHH" ;;
    M5) echo "M5 · Institucional y alertas" ;;
    M6) echo "M6 · Endurecimiento y salida a producción" ;;
  esac
}

# ── 3. Project ──────────────────────────────────────────────────
echo "==> Project"
PROJECT_NUM=""
if [[ $DRY_RUN -eq 0 ]]; then
  PROJECT_NUM=$(gh project list --owner "$OWNER" --format json \
    --jq ".projects[] | select(.title==\"$PROJECT_TITLE\") | .number" 2>/dev/null | head -n1)
  if [[ -z "$PROJECT_NUM" ]]; then
    PROJECT_NUM=$(gh project create --owner "$OWNER" --title "$PROJECT_TITLE" --format json \
      --jq ".number" 2>/dev/null) || true
    if [[ -n "$PROJECT_NUM" ]]; then
      echo "  ok  creado (#$PROJECT_NUM)"
    else
      echo "  !!  no se pudo crear el project (revisar scopes: gh auth refresh -s project,read:project)"
    fi
  else
    echo "  --  ya existe (#$PROJECT_NUM)"
  fi
  if [[ -n "$PROJECT_NUM" ]]; then
    gh project field-create "$PROJECT_NUM" --owner "$OWNER" --name "Área" \
      --data-type SINGLE_SELECT --single-select-options \
      "core,ninos,rrhh,economato,pedagogico,institucional,alertas,seguridad,infra,ux" >/dev/null 2>&1 \
      && echo "  ok  campo Área" || echo "  --  campo Área (ya existe)"
    gh project field-create "$PROJECT_NUM" --owner "$OWNER" --name "Estimación" \
      --data-type SINGLE_SELECT --single-select-options "S,M,L" >/dev/null 2>&1 \
      && echo "  ok  campo Estimación" || echo "  --  campo Estimación (ya existe)"
    gh project field-create "$PROJECT_NUM" --owner "$OWNER" --name "Prioridad" \
      --data-type SINGLE_SELECT --single-select-options "alta,media,baja" >/dev/null 2>&1 \
      && echo "  ok  campo Prioridad" || echo "  --  campo Prioridad (ya existe)"
  fi
else
  echo "  [dry-run] project \"$PROJECT_TITLE\""
fi

# ── 4. Issues ───────────────────────────────────────────────────
echo "==> Issues"
N=0
while IFS='|' read -r fase area prioridad estimacion titulo criterios; do
  [[ "$fase" == "fase" || -z "$fase" ]] && continue
  N=$((N+1))
  milestone="$(ms_de_fase "$fase")"

  body="**Fase:** ${milestone}"$'\n\n'"### Criterios de aceptación"$'\n'
  IFS=';' read -ra items <<< "$criterios"
  for c in "${items[@]}"; do
    body+="- [ ] $(echo "$c" | sed 's/^ *//;s/ *$//')"$'\n'
  done
  body+=$'\n'"---"$'\n'"_Generado desde \`backlog.txt\`._"

  if [[ $DRY_RUN -eq 1 ]]; then
    printf "  [dry-run] %-2s %s\n" "$N" "$titulo"
    continue
  fi

  url=$(gh issue create --repo "$REPO" \
    --title "$titulo" \
    --body "$body" \
    --milestone "$milestone" \
    --label "area:${area}" \
    --label "prioridad:${prioridad}" \
    --label "estimación:${estimacion}" 2>/dev/null) || { echo "  !!  falló: $titulo"; continue; }

  printf "  ok  %-2s %s\n" "$N" "$titulo"
  if [[ -n "$PROJECT_NUM" ]]; then
    gh project item-add "$PROJECT_NUM" --owner "$OWNER" --url "$url" >/dev/null 2>&1 || true
  fi
  sleep 1   # evita el rate limit de creación de issues
done < "$BACKLOG_FILE"

echo "==> Listo: $N issues procesados"
