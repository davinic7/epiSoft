# Política de tratamiento de datos personales

**Estado:** Borrador — pendiente de revisión legal e institucional antes de
regir en producción.

Este documento identifica qué datos personales trata epiSoft, con qué
finalidad, quién accede a cada categoría y por cuánto tiempo se conservan.
Es la base para cumplir la Ley 25.326 de Protección de Datos Personales
(Argentina) y debe revisarse cada vez que se agregue un módulo que trate
datos nuevos.

## Alcance

epiSoft es un sistema de gestión para Espacios de Primera Infancia (EPI)
dependientes de un organismo público provincial. Trata datos personales de
tres grupos de titulares:

- **Niños y niñas** matriculados en un EPI, y sus referentes familiares.
- **Personal** de cada institución (educadoras/es, coordinación, economato,
  cocina, mantenimiento).
- **Usuarios del sistema** (las mismas personas del punto anterior, en su
  rol de acceso a la aplicación).

A la fecha de este borrador, el sistema está en fase de fundaciones (M0):
solo existen las tablas de usuarios, instituciones y roles. Las categorías
de datos de niños, familias y personal descritas abajo corresponden a los
módulos planificados (ver [BACKLOG](BACKLOG.md), M1 y M4) y a
[docs/guia-buenas-practicas-epi.md](guia-buenas-practicas-epi.md); se
listan acá para fijar la política *antes* de construir esas tablas, no
porque ya estén en la base de datos.

## Categorías de datos y finalidad

| Categoría | Datos | Finalidad | Base normativa/contractual |
|---|---|---|---|
| Cuenta de usuario | Nombre, email, contraseña (hash), rol por institución | Autenticación y autorización de quien opera el sistema | Necesario para prestar el servicio |
| Identificación de niños/as | Nombre, DNI, fecha de nacimiento, alias | Matrícula, legajo, asistencia | Prestación del servicio de cuidado (interés público, programa provincial) |
| Salud de niños/as | Vacunas, alergias, restricciones alimentarias | Cuidado cotidiano seguro (economato, sala, emergencias) | Dato sensible (art. 2 y 7, Ley 25.326): requiere consentimiento del referente o excepción legal expresa; **pendiente de definir la base legal exacta con el organismo** |
| Vulneración de derechos | Bitácora de intervenciones y derivaciones | Protección de derechos del niño/a, articulación interinstitucional | Dato sensible con protección reforzada; acceso restringido (ver M5, módulo de vulneraciones) |
| Referentes familiares | Nombre, DNI, contacto, parentesco, autorización de retiro | Contacto y retiro seguro del niño/a | Prestación del servicio |
| Personal | Datos personales, función, documentación con vencimientos (antecedentes, libreta sanitaria) | Gestión de RRHH, cumplimiento de requisitos del organismo | Relación laboral/contractual |
| Auditoría | Usuario, acción, entidad, fecha, IP | Trazabilidad de accesos y cambios sobre datos sensibles | Seguridad de la información (ver issue de auditoría) |

## Quién accede a cada categoría

El acceso se resuelve por rol institucional y por institución activa (ver
[ADR-002](adr/002-multi-institucion.md) y el modelo de roles en
`app/Enums/RolInstitucional.php`), nunca de forma global salvo el
superadmin técnico:

- **Datos de salud y de niños en general**: equipo de coordinación,
  coordinador/a pedagógico/a, educadoras/es de la sala del niño/a,
  encargado/a de economato (solo alergias/restricciones).
- **Vulneración de derechos**: exclusivamente equipo de coordinación,
  sin permiso de eliminar (ver criterios del módulo en el backlog M5).
- **Documentación de personal**: equipo de coordinación.
- **Auditoría**: equipo de coordinación y superadmin.
- **Superadmin técnico**: acceso transversal para soporte de la
  plataforma, fuera del esquema de roles por institución (bypass de
  Gate documentado en `AppServiceProvider`). Su uso debe quedar
  registrado en la auditoría igual que cualquier otro acceso.

## Conservación

- **Mientras dure la matrícula o la relación laboral**: los datos se
  mantienen activos para el uso cotidiano del módulo correspondiente.
- **Baja lógica**: al egresar un niño/a o darse de baja el personal, el
  registro no se elimina; se marca como inactivo y deja de aparecer en
  listados operativos (ver criterios de "Egreso y baja" en el backlog).
- **Plazo de conservación tras la baja**: pendiente de definir con el
  organismo (propuesta a validar: alineado al plazo de guarda de legajos
  que exija la normativa provincial de la Dirección de Primera Infancia).
  Hasta que se defina, no se purgan datos dados de baja.
- **Auditoría**: se conserva sin plazo de purga automática mientras no se
  defina lo contrario, dado su rol de evidencia ante incidentes.

## Medidas de seguridad aplicadas

- Aislamiento de datos por institución mediante *global scope* de
  Eloquent, con fallo cerrado si no hay institución activa
  ([ADR-002](adr/002-multi-institucion.md)).
- Autenticación con hash de contraseña, verificación de email y bloqueo
  temporal tras intentos fallidos (ver issue de autenticación).
- Cabeceras de seguridad HTTP y protección CSRF (ver issue de
  protecciones básicas).
- No se cargan datos reales de niños, familias o personal en entornos de
  desarrollo o de prueba (ver [CONTRIBUTING.md](../CONTRIBUTING.md)).

## Pendiente de definir con el organismo

Este borrador no reemplaza asesoramiento legal. Antes de tratar datos
reales, falta:

1. Confirmar la base legal exacta para tratar datos sensibles de salud y
   de vulneración de derechos (consentimiento del referente, interés
   público del programa, u otra prevista en el art. 5 de la Ley 25.326).
2. Definir el plazo concreto de conservación post-baja.
3. Definir el procedimiento para que un referente familiar ejerza los
   derechos de acceso, rectificación y supresión (art. 14-16, Ley
   25.326) sobre los datos de su hijo/a.
4. Revisión por quien tenga la responsabilidad legal del organismo antes
   de salir a producción con datos reales (ver milestone M6).
