# Convenciones de trabajo

## Ramas

- `main` — código desplegable. No se commitea directo.
- `feat/<nro-issue>-descripcion-corta` — una rama por issue, sale de `main` y vuelve a `main`.
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

- Los criterios de aceptación del issue tienen que estar tildados.
- No se mergea con pruebas en rojo.

## Datos de prueba

Nunca cargar datos reales de niños, familias o personal en entornos de
desarrollo. Usar el seed de la institución ficticia.
