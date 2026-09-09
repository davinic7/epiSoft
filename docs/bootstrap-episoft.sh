#!/usr/bin/env bash
# ---------------------------------------------------------------
# epiSoft — bootstrap del repositorio y del Project
#
# Crea el repo en GitHub, la estructura inicial de carpetas y
# documentos, el primer commit, y luego los milestones, etiquetas,
# Project e issues del backlog.
#
# Requisitos:
#   gh auth login
#   gh auth refresh -s project,read:project
#   backlog.txt y setup-github-project.sh en la misma carpeta
#
# Uso:
#   ./bootstrap-episoft.sh <tu-usuario-github> [ruta/al/prototipo.html]
# ---------------------------------------------------------------
set -euo pipefail

OWNER="${1:-}"
PROTOTIPO="${2:-}"
REPO_NAME="epiSoft"
AQUI="$(cd "$(dirname "$0")" && pwd)"

if [[ -z "$OWNER" ]]; then
  echo "Uso: $0 <tu-usuario-github> [ruta/al/prototipo.html]" >&2
  exit 1
fi

REPO="$OWNER/$REPO_NAME"
echo "==> Creando $REPO"

if [[ -d "$REPO_NAME" ]]; then
  echo "Ya existe la carpeta ./$REPO_NAME. Movela o borrala antes de seguir." >&2
  exit 1
fi

mkdir -p "$REPO_NAME"
cd "$REPO_NAME"

# ── Estructura de carpetas ──────────────────────────────────────
mkdir -p docs/adr docs/prototipo docs/modelo-datos .github/ISSUE_TEMPLATE

# ── README ──────────────────────────────────────────────────────
cat > README.md << 'EOF'
# epiSoft

Sistema de gestión integral para Espacios de Primera Infancia (EPI).
Despliegue web único, multi-institución: una sola instalación da servicio
a varias EPI, con los datos de cada una aislados entre sí.

## Módulos

| Módulo | Alcance |
|---|---|
| Niños | Legajos, referentes, salas, salud, asistencia diaria |
| RRHH | Personal, documentación con vencimientos, capacitaciones, horarios |
| Economato | Stock por lote, libro de movimientos, menú semanal, inventario |
| Pedagógico | Registro diario, seguimientos, desafíos del desarrollo, informes |
| Institucional | Mapa de riesgos, articulaciones, recursero, vulneración de derechos |
| Alertas | Motor de alertas derivadas de los datos de los demás módulos |

## Estado

En fase M0 (fundaciones). El stack todavía no está definido: ver
[ADR-001](docs/adr/001-stack.md).

El [prototipo HTML original](docs/prototipo/) es la referencia de interfaz.
No es la aplicación: los datos que muestra son de ejemplo.

## Documentación

- [Backlog completo](docs/BACKLOG.md)
- [Decisiones de arquitectura](docs/adr/)
- [Convenciones de trabajo](CONTRIBUTING.md)

## Datos sensibles

El sistema almacena datos personales de niños y niñas, incluida información
de salud y de situaciones de vulneración de derechos. Todo desarrollo sobre
este repositorio está sujeto a la Ley 25.326 de Protección de Datos
Personales. No se cargan datos reales en entornos de desarrollo ni de prueba.
EOF

# ── .gitignore ──────────────────────────────────────────────────
cat > .gitignore << 'EOF'
# Entorno
.env
.env.*
!.env.example

# Dependencias
/vendor/
/node_modules/

# Compilados y caché
/public/build/
/public/hot
/storage/*.key
*.cache
.phpunit.result.cache

# Adjuntos subidos por usuarios (nunca al repo)
/storage/app/adjuntos/
/uploads/

# Editores y sistema
.idea/
.vscode/
.DS_Store
Thumbs.db
*.swp

# Backups y volcados
*.sql
*.sql.gz
*.dump
EOF

# ── Plantilla de ADR ────────────────────────────────────────────
cat > docs/adr/000-plantilla.md << 'EOF'
# ADR-000: Título de la decisión

**Estado:** Propuesto | Aceptado | Reemplazado por ADR-XXX
**Fecha:** AAAA-MM-DD
**Decide:** quién firma

## Contexto

Cuál es la situación y qué fuerzas están en juego.

## Decisión

Qué se decide hacer.

## Opciones consideradas

### Opción A: nombre

| Dimensión | Evaluación |
|---|---|
| Complejidad | |
| Costo | |
| Escalabilidad | |
| Conocimiento previo | |

**A favor:**
**En contra:**

### Opción B: nombre

Mismo formato.

## Consecuencias

- Qué se vuelve más fácil
- Qué se vuelve más difícil
- Qué habrá que revisar más adelante
EOF

# ── ADR-001: stack ──────────────────────────────────────────────
cat > docs/adr/001-stack.md << 'EOF'
# ADR-001: Stack de la aplicación

**Estado:** Propuesto
**Fecha:**
**Decide:**

## Contexto

epiSoft es una aplicación web multi-institución con siete módulos, informes
exportables a PDF y Word, y datos personales sensibles de menores. La va a
mantener una sola persona, al menos al principio. Debe correr en un hosting
accesible y sostenible en el tiempo para instituciones sin presupuesto de
infraestructura.

Restricciones a tener en cuenta al elegir:

- Mantenimiento a largo plazo por una persona
- Hosting barato y disponible en Argentina
- Necesidad de generar documentos ofimáticos
- Aislamiento de datos por institución desde el día cero
- Backups y restauración simples

## Decisión

Pendiente.

## Opciones consideradas

Completar con las alternativas evaluadas y sus dimensiones.

## Consecuencias

Pendiente.
EOF

# ── ADR-002: multi-institución ──────────────────────────────────
cat > docs/adr/002-multi-institucion.md << 'EOF'
# ADR-002: Estrategia de multi-institución

**Estado:** Propuesto
**Fecha:**
**Decide:**

## Contexto

Una sola instalación da servicio a varias EPI. Los datos de una institución
no pueden ser accesibles desde otra bajo ninguna circunstancia, incluida la
manipulación de identificadores en la URL. Hay usuarios que pueden pertenecer
a más de una institución con roles distintos en cada una.

## Opciones consideradas

### Opción A: base compartida con `institucion_id`

| Dimensión | Evaluación |
|---|---|
| Complejidad | Media |
| Costo | Bajo, una sola base |
| Riesgo | Una consulta sin filtrar expone datos de otra institución |
| Backups | Un solo backup, pero no se restaura una institución sola |

### Opción B: una base por institución

| Dimensión | Evaluación |
|---|---|
| Complejidad | Alta, conexión dinámica y migraciones por base |
| Costo | Mayor, según el hosting |
| Riesgo | Aislamiento garantizado por diseño |
| Backups | Restauración individual posible |

## Decisión

Pendiente.

## Consecuencias

Pendiente.
EOF

# ── Convenciones ────────────────────────────────────────────────
cat > CONTRIBUTING.md << 'EOF'
# Convenciones de trabajo

## Ramas

- `main` — código desplegable. No se commitea directo.
- `develop` — integración.
- `feat/<nro-issue>-descripcion-corta` — una rama por issue.
- `fix/<nro-issue>-descripcion-corta`

## Commits

Formato: `tipo(alcance): descripción en presente`

Tipos: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`

```
feat(ninos): alta de legajo en tres pasos
fix(economato): impedir salida mayor al stock disponible
docs(adr): aceptar ADR-002
```

Cerrar el issue desde el PR con `Closes #12`.

## Pull requests

- Un PR por issue.
- Los criterios de aceptación del issue tienen que estar tildados.
- No se mergea con pruebas en rojo.

## Datos de prueba

Nunca cargar datos reales de niños, familias o personal en entornos de
desarrollo. Usar el seed de la institución ficticia.
EOF

# ── Plantillas de issue ─────────────────────────────────────────
cat > .github/ISSUE_TEMPLATE/tarea.yml << 'EOF'
name: Tarea
description: Funcionalidad o trabajo planificado
labels: []
body:
  - type: dropdown
    id: area
    attributes:
      label: Área
      options: [core, ninos, rrhh, economato, pedagogico, institucional, alertas, seguridad, infra, ux]
    validations:
      required: true
  - type: textarea
    id: contexto
    attributes:
      label: Contexto
      description: Qué problema resuelve
    validations:
      required: true
  - type: textarea
    id: criterios
    attributes:
      label: Criterios de aceptación
      value: |
        - [ ]
        - [ ]
    validations:
      required: true
  - type: textarea
    id: notas
    attributes:
      label: Notas técnicas
EOF

cat > .github/ISSUE_TEMPLATE/bug.yml << 'EOF'
name: Error
description: Algo que no funciona como debería
labels: ["tipo:bug"]
body:
  - type: textarea
    id: pasos
    attributes:
      label: Pasos para reproducir
      value: |
        1.
        2.
        3.
    validations:
      required: true
  - type: textarea
    id: esperado
    attributes:
      label: Qué debería pasar
    validations:
      required: true
  - type: textarea
    id: real
    attributes:
      label: Qué pasa en realidad
    validations:
      required: true
  - type: input
    id: entorno
    attributes:
      label: Entorno
      placeholder: producción / desarrollo, navegador, rol del usuario
EOF

cat > .github/pull_request_template.md << 'EOF'
## Qué hace

Closes #

## Criterios de aceptación

- [ ] Todos los criterios del issue están cumplidos

## Verificación

- [ ] Probado a mano
- [ ] Pruebas automatizadas agregadas o actualizadas
- [ ] No expone datos entre instituciones
- [ ] No hay datos reales en el código ni en los seeds
EOF

# ── Backlog y prototipo ─────────────────────────────────────────
[[ -f "$AQUI/BACKLOG.md" ]] && cp "$AQUI/BACKLOG.md" docs/BACKLOG.md
[[ -f "$AQUI/backlog.txt" ]] && cp "$AQUI/backlog.txt" docs/backlog.txt

if [[ -n "$PROTOTIPO" && -f "$PROTOTIPO" ]]; then
  cp "$PROTOTIPO" docs/prototipo/sistema_gestion_epi.html
  echo "  ok  prototipo copiado"
else
  echo "  --  sin prototipo (pasalo como segundo argumento)"
fi

cat > docs/prototipo/README.md << 'EOF'
# Prototipo original

Maqueta HTML de un solo archivo con los siete módulos, usada como referencia
de interfaz y de alcance funcional.

No es la aplicación. Los datos que muestra están escritos en el código y solo
persiste legajos y movimientos en `localStorage`. Sirve para dos cosas:

- Reutilizar el CSS y la estructura de navegación
- Discutir alcance con la institución antes de construir cada módulo
EOF

cat > docs/modelo-datos/README.md << 'EOF'
# Modelo de datos

El esquema se define en migraciones versionadas, no en este documento.
Acá va el diagrama entidad-relación y las decisiones que no se leen del
esquema: reglas de negocio, cálculos derivados y campos que existen por
requisito normativo.

Regla transversal: toda tabla de negocio lleva `institucion_id` y toda
consulta filtra por la institución activa.
EOF

# ── Commit inicial y push ───────────────────────────────────────
echo "==> Commit inicial"
git init -q -b main
git add -A
git commit -qm "chore: estructura inicial del repositorio

Documentación base, ADR en borrador, plantillas de issue y PR,
backlog y prototipo de referencia."

echo "==> Publicando en GitHub"
gh repo create "$REPO" --private --source=. --remote=origin --push \
  --description "Sistema de gestión integral para Espacios de Primera Infancia"

git checkout -qb develop && git push -q -u origin develop && git checkout -q main
echo "  ok  ramas main y develop"

# ── Issues, milestones y Project ────────────────────────────────
if [[ -x "$AQUI/setup-github-project.sh" ]]; then
  echo "==> Backlog"
  "$AQUI/setup-github-project.sh" "$REPO" "epiSoft"
else
  echo "  !!  no encuentro setup-github-project.sh; corrélo aparte"
fi

echo
echo "Listo. Repo en https://github.com/$REPO"
