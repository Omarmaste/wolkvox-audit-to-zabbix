Wolkvox Audit Logger to Zabbix

Este script PHP permite consultar logs de auditoría del API de Wolkvox y enviarlos como alertas a Zabbix usando `zabbix_sender`.

## 📌 Descripción

La API de auditoría de Wolkvox permite obtener un reporte detallado de todos los movimientos realizados en el sistema, incluyendo:

- Fecha y hora del cambio
- Dirección IP pública
- Usuario
- Estación de trabajo
- Acción ejecutada

### Beneficios

Permite identificar errores como cambios no autorizados, activaciones de servicios con costo, problemas de enrutamiento, etc.

## ⚙️ ¿Cómo funciona?

- Consulta el API cada día con un script programado vía `cron`.
- Filtra solo eventos que contienen la palabra clave `ADMIN:`.
- Envía estos eventos a Zabbix mediante `zabbix_sender`.
- Guarda el último evento procesado en un archivo (`wolkvox_last_timestamp.txt`) para evitar duplicados.
- Compatible con Zabbix traps (`trapper`) para centralizar eventos textuales y auditables.

## 🧪 Requisitos

- PHP 5.4.16 o superior
- `zabbix_sender` instalado en el sistema
- Variable de entorno `WOLKVOX_TOKEN` configurada

## 🛠 Instalación

1. Clona este repositorio.
2. Define la variable de entorno en tu `~/.bashrc` o en el cron job:

```bash
export WOLKVOX_TOKEN="tu_token_aqui"

