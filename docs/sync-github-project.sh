#!/usr/bin/env bash
# ---------------------------------------------------------------
# Sistema EPI — sincronización del proyecto en GitHub
#
# Lleva los milestones e issues que ya existen en GitHub al estado
# de milestones.txt y backlog.txt, sin crear duplicados:
#   - Renombra los milestones según la columna titulo_anterior y
#     crea los que falten.
#   - Por cada fila de backlog.txt busca el issue por título (o por
#     su título anterior, ver TITULOS_ANTERIORES) y actualiza título,
#     cuerpo, milestone y etiquetas. Si no existe, lo crea.
#   - Cierra, con un comentario, los issues abiertos generados desde
#     backlog.txt que ya no figuran en él.
#
# Un criterio queda tildado si ya lo estaba en el issue o en
# BACKLOG.md, así no se pierde el progreso registrado en ninguno.
#
# Requisitos: gh CLI autenticado con permisos de repo y project.
#
# Uso:
#   ./sync-github-project.sh --dry-run <owner/repo> ["Título del Project"]
#   ./sync-github-project.sh <owner/repo> ["Título del Project"]
# ---------------------------------------------------------------
set -euo pipefail

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then DRY_RUN=1; shift; fi

REPO="${1:-}"
PROJECT_TITLE="${2:-Sistema EPI}"
DIR="$(dirname "$0")"
BACKLOG_FILE="$DIR/backlog.txt"
MILESTONES_FILE="$DIR/milestones.txt"
BACKLOG_MD="$DIR/BACKLOG.md"
PIE=$'---\n_Generado desde `backlog.txt`._'

if [[ -z "$REPO" ]]; then
  echo "Uso: $0 [--dry-run] <owner/repo> [\"Título del Project\"]" >&2
  exit 1
fi

OWNER="${REPO%%/*}"
run() { if [[ $DRY_RUN -eq 1 ]]; then echo "      [dry-run] ${*:1:3} …"; else "$@" >/dev/null; fi; }

# Issues renombrados en backlog.txt: título nuevo -> título con el que
# existen hoy en GitHub.
declare -A TITULOS_ANTERIORES=(
  ["CRUD de niños"]="CRUD de niños con alta en tres pasos"
  ["Salas con descripción y asignación de niños"]="Salas, turnos y cupos"
  ["Asistencia diaria de niños"]="Asistencia diaria por sala"
  ["Dashboard de la EPI con métricas reales"]="Dashboard con métricas reales"
)

echo "==> Repositorio: $REPO"
[[ $DRY_RUN -eq 1 ]] && echo "==> MODO SIMULACIÓN: no se modifica nada"

# ── 1. Milestones ───────────────────────────────────────────────
echo "==> Milestones"
declare -A MS_TITULO
declare -A MS_EXISTENTE
while IFS=$'\t' read -r num titulo; do
  MS_EXISTENTE[$titulo]="$num"
done < <(gh api "repos/$REPO/milestones?state=all&per_page=100" --jq '.[] | "\(.number)\t\(.title)"')

while IFS='|' read -r fase titulo descripcion anterior; do
  [[ "$fase" == "fase" || -z "$fase" ]] && continue
  MS_TITULO[$fase]="$titulo"
  if [[ -n "${MS_EXISTENTE[$titulo]:-}" ]]; then
    echo "  --  $titulo"
  elif [[ -n "$anterior" && -n "${MS_EXISTENTE[$anterior]:-}" ]]; then
    echo "  ~~  $anterior -> $titulo"
    run gh api "repos/$REPO/milestones/${MS_EXISTENTE[$anterior]}" -X PATCH \
      -f title="$titulo" -f description="$descripcion"
  else
    echo "  ++  $titulo"
    run gh api "repos/$REPO/milestones" -X POST -f title="$titulo" -f description="$descripcion"
  fi
done < "$MILESTONES_FILE"

# ── 2. Project ──────────────────────────────────────────────────
PROJECT_NUM=$(gh project list --owner "$OWNER" --format json \
  --jq ".projects[] | select(.title==\"$PROJECT_TITLE\") | .number" 2>/dev/null | head -n1) || true

# ── 3. Issues ───────────────────────────────────────────────────
echo "==> Issues"
declare -A ISSUE_NUM
declare -A ISSUE_ESTADO
while IFS=$'\t' read -r num estado titulo; do
  ISSUE_NUM[$titulo]="$num"
  ISSUE_ESTADO[$num]="$estado"
done < <(gh issue list --repo "$REPO" --state all --limit 500 \
  --json number,state,title --jq '.[] | "\(.number)\t\(.state)\t\(.title)"')

declare -A EN_BACKLOG
while IFS='|' read -r fase area prioridad estimacion titulo criterios; do
  [[ "$fase" == "fase" || -z "$fase" ]] && continue
  milestone="${MS_TITULO[$fase]}"
  anterior="${TITULOS_ANTERIORES[$titulo]:-}"
  num="${ISSUE_NUM[$titulo]:-}"
  [[ -z "$num" && -n "$anterior" ]] && num="${ISSUE_NUM[$anterior]:-}"
  cuerpo_actual=""
  [[ -n "$num" ]] && { EN_BACKLOG[$num]=1; cuerpo_actual=$(gh issue view "$num" --repo "$REPO" --json body --jq .body); }

  body="**Fase:** ${milestone}"$'\n\n'"### Criterios de aceptación"$'\n'
  IFS=';' read -ra items <<< "$criterios"
  for c in "${items[@]}"; do
    c="$(echo "$c" | sed 's/^ *//;s/ *$//')"
    marca=" "
    if grep -qxF -- "- [x] $c" <<< "$cuerpo_actual" || grep -qxF -- "- [x] $c" "$BACKLOG_MD"; then
      marca="x"
    fi
    body+="- [$marca] $c"$'\n'
  done
  body+=$'\n'"$PIE"

  labels=("area:${area}" "prioridad:${prioridad}" "estimación:${estimacion}")

  if [[ -z "$num" ]]; then
    echo "  ++  $titulo"
    if [[ $DRY_RUN -eq 0 ]]; then
      url=$(gh issue create --repo "$REPO" --title "$titulo" --body "$body" --milestone "$milestone" \
        --label "${labels[0]}" --label "${labels[1]}" --label "${labels[2]}")
      [[ -n "$PROJECT_NUM" ]] && gh project item-add "$PROJECT_NUM" --owner "$OWNER" --url "$url" >/dev/null 2>&1 || true
      sleep 1   # evita el rate limit de creación de issues
    fi
    continue
  fi

  actual=$(gh issue view "$num" --repo "$REPO" --json title,milestone,labels \
    --jq '"\(.title)\t\(.milestone.title // "")\t\([.labels[].name | select(test("^(area|prioridad|estimación):"))] | sort | join(","))"')
  IFS=$'\t' read -r titulo_gh milestone_gh labels_gh <<< "$actual"
  labels_nuevos=$(printf '%s\n' "${labels[@]}" | sort | paste -sd, -)

  cambios=()
  [[ "$titulo_gh" != "$titulo" ]] && cambios+=("título")
  [[ "$milestone_gh" != "$milestone" ]] && cambios+=("milestone")
  [[ "$labels_gh" != "$labels_nuevos" ]] && cambios+=("etiquetas")
  [[ "$cuerpo_actual" != "$body" ]] && cambios+=("criterios")

  if [[ ${#cambios[@]} -eq 0 ]]; then
    echo "  --  #$num $titulo"
    continue
  fi

  echo "  ~~  #$num $titulo ($(IFS=,; echo "${cambios[*]}"))"
  args=(--title "$titulo" --body "$body" --milestone "$milestone")
  for l in "${labels[@]}"; do args+=(--add-label "$l"); done
  IFS=',' read -ra viejas <<< "$labels_gh"
  for l in "${viejas[@]}"; do
    [[ -n "$l" && ",$labels_nuevos," != *",$l,"* ]] && args+=(--remove-label "$l")
  done
  run gh issue edit "$num" --repo "$REPO" "${args[@]}"
done < "$BACKLOG_FILE"

# ── 4. Issues que salieron del backlog ──────────────────────────
echo "==> Issues fuera del backlog"
for titulo in "${!ISSUE_NUM[@]}"; do
  num="${ISSUE_NUM[$titulo]}"
  [[ -n "${EN_BACKLOG[$num]:-}" || "${ISSUE_ESTADO[$num]}" != "OPEN" ]] && continue
  gh issue view "$num" --repo "$REPO" --json body --jq .body | grep -qF '_Generado desde `backlog.txt`._' || continue
  echo "  xx  #$num $titulo"
  run gh issue close "$num" --repo "$REPO" --reason "not planned" \
    --comment "Fuera de alcance: se quitó de \`backlog.txt\`."
done

echo "==> Listo"
