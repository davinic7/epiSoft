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

## Stack

Laravel 13 (PHP 8.3+) + MySQL/MariaDB. Ver [ADR-001](docs/adr/001-stack.md).

## Levantar el entorno

```bash
composer install
cp .env.example .env   # si no existe ya
php artisan key:generate
php artisan migrate
npm install
npm run build           # o `npm run dev` para levantar Vite en modo watch
php artisan serve
```

Requiere PHP 8.3+, Composer, Node.js y MySQL/MariaDB corriendo localmente
(en Windows, [Laravel Herd](https://herd.laravel.com) resuelve PHP y MySQL).

Sin `npm run build` (o `npm run dev` corriendo), cualquier vista que use Vite
falla con `ViteManifestNotFoundException` porque falta `public/build/manifest.json`
— incluye las pantallas de autenticación, el dashboard y toda la suite de tests
que las ejercita.

## Estado

En fase M0 (fundaciones). Esqueleto de Laravel 13 instalado; falta el
esquema de base de datos, autenticación y el resto de los issues de M0.

El [prototipo HTML original](docs/prototipo/) es la referencia de interfaz.
No es la aplicación: los datos que muestra son de ejemplo.

## Documentación

- [Backlog completo](docs/BACKLOG.md)
- [Decisiones de arquitectura](docs/adr/)
- [Convenciones de trabajo](CONTRIBUTING.md)
- [Política de tratamiento de datos personales](docs/politica-tratamiento-datos-personales.md)

## Datos sensibles

El sistema almacena datos personales de niños y niñas, incluida información
de salud y de situaciones de vulneración de derechos. Todo desarrollo sobre
este repositorio está sujeto a la Ley 25.326 de Protección de Datos
Personales. No se cargan datos reales en entornos de desarrollo ni de prueba.
