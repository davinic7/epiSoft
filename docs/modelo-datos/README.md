# Modelo de datos

El esquema se define en migraciones versionadas, no en este documento.
Acá va el diagrama entidad-relación y las decisiones que no se leen del
esquema: reglas de negocio, cálculos derivados y campos que existen por
requisito normativo.

Regla transversal: toda tabla de negocio lleva `institucion_id` y toda
consulta filtra por la institución activa (ver
[ADR-002](../adr/002-multi-institucion.md)).
