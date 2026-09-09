# ADR-001: Stack de la aplicación

**Estado:** Aceptado
**Fecha:** 2026-09-09
**Decide:** davinic7 (responsable único del proyecto)

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
- Preferencia por encarar el proyecto con una tecnología nueva para quien lo mantiene

La elección de motor de base de datos depende de una suposición concreta de
despliegue: que epiSoft va a vivir en hosting compartido (cPanel) en
Argentina, no en un VPS. Esa suposición es la que descarta PostgreSQL en
favor de MySQL/MariaDB — no hay una razón técnica de Eloquent para preferir
uno sobre otro, y con el ORM de por medio la migración entre ambos motores
no tiene costo relevante en código de aplicación. Si en algún momento se
pasa a VPS, vale la pena revisar si esta suposición sigue en pie antes de
asumir que MySQL sigue siendo la mejor opción.

## Decisión

Laravel 13 (PHP 8.3 como mínimo) como framework de aplicación, con
MySQL/MariaDB como motor de base de datos.

Se descarta Laravel 12 aunque también cumple los requisitos funcionales:
a la fecha de esta decisión (septiembre de 2026) ya salió de soporte de
bugfixes (13/08/2026) y solo recibe parches de seguridad hasta el
24/02/2027. Arrancar un proyecto nuevo sobre una versión así es asumir
deuda técnica desde el día uno. Laravel 13, en cambio, recibe bugfixes
hasta Q3 2027 y seguridad hasta el 17/03/2028.

**Pendiente de confirmar con el hosting elegido:** Laravel 13 exige PHP 8.3
como mínimo (soporta hasta 8.5). Antes de contratar, verificar que el
proveedor de hosting compartido lo ofrezca — buena parte del hosting barato
en Argentina va un par de versiones de PHP atrás. Si ofrece 8.4 o 8.5,
preferir esa versión: da más margen antes del próximo salto obligado.

## Opciones consideradas

### Opción A: Laravel (PHP 8.3+) + MySQL/MariaDB

| Dimensión | Evaluación |
|---|---|
| Complejidad | Media. Convenciones fuertes reducen decisiones repetidas. |
| Costo | Bajo, condicionado a hosting compartido (cPanel): es la oferta barata y difundida en Argentina, sin necesitar VPS. |
| Escalabilidad | Suficiente para el volumen esperado (instituciones chicas/medianas); migrar a VPS si hace falta no exige cambiar de stack. |
| Conocimiento previo | Tecnología nueva a aprender; ecosistema y documentación muy extensos, incluida comunidad en español. |

**A favor:** Eloquent ofrece *global scopes*, que resuelven de forma directa
el requisito de que toda consulta filtre automáticamente por la institución
activa (ver [ADR-002](002-multi-institucion.md)). Paquetes maduros para PDF
y Word (`barryvdh/laravel-dompdf`, `PhpWord`) y para backups
(`spatie/laravel-backup`). Migraciones, auth y colas vienen resueltas de
fábrica, lo que reduce superficie de mantenimiento para una sola persona.

**En contra:** curva de aprendizaje inicial del framework y de convenciones
de Eloquent/Artisan.

### Opción B: Node.js (NestJS) + PostgreSQL

| Dimensión | Evaluación |
|---|---|
| Complejidad | Media-alta. Arquitectura modular explícita (más boilerplate). |
| Costo | Mayor: necesita un proceso Node corriendo permanentemente, lo que en la práctica exige VPS en vez de hosting compartido. |
| Escalabilidad | Buena. |
| Conocimiento previo | Tecnología nueva; ecosistema amplio pero más fragmentado para generación de documentos ofimáticos. |

**A favor:** tipado estático con TypeScript, buen soporte para APIs.

**En contra:** el costo de hosting y de operar un VPS en soledad pesa más
que el beneficio, dado que no hay necesidad de alta concurrencia.

### Opción C: Python (Django) + PostgreSQL

| Dimensión | Evaluación |
|---|---|
| Complejidad | Media. Admin de Django acelera pantallas internas. |
| Costo | Similar a Node: la mayoría del hosting barato en Argentina no ofrece WSGI/ASGI persistente, empuja a VPS. |
| Escalabilidad | Buena. |
| Conocimiento previo | Tecnología nueva. |

**A favor:** el admin autogenerado es útil para pantallas de soporte interno.

**En contra:** mismo problema de hosting que Node; generación de Word/PDF
menos directa que en el ecosistema PHP.

## Consecuencias

- Se vuelve más fácil: desplegar en hosting compartido económico, generar
  PDF/Word, implementar el filtro por institución con un mecanismo del
  propio framework (global scopes) en vez de uno construido a medida.
- Se vuelve más difícil: aprovechar librerías de IA/data science que suelen
  estar primero en el ecosistema Python/Node, aunque no es un requisito de
  epiSoft hoy.
- Habrá que revisar más adelante: si el volumen de datos o de instituciones
  crece al punto de necesitar un VPS, evaluar si conviene mantener PHP-FPM
  con más recursos o migrar de hosting sin migrar de stack.
