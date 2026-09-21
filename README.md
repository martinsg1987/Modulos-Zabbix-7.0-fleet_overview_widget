# Módulo Zabbix 7.0 — Fleet overview

Widget de dashboard: tarjetas por host (icono de SO, IP, uptime, badge de
problemas activos por severidad) + tabla compacta de detalle de problemas.

## Requisitos

- Zabbix 7.0.x (frontend).
- Hosts con los items `system.sw.os` y `system.uptime` (vienen en los
  templates de agente Linux y Windows). Si faltan, la tarjeta muestra
  "Sin dato de SO" y uptime "—".

## Instalación

Copiar la carpeta a los módulos del frontend (en este servidor:
`/usr/share/zabbix/modules/`) y dar permisos al usuario del web server:

```
sudo cp -r fleet_overview /usr/share/zabbix/modules/
sudo chown -R www-data:www-data /usr/share/zabbix/modules/fleet_overview
```

Luego, en la interfaz web:

1. *Administration → General → Modules*
2. *Scan directory*, buscar "Fleet overview" y habilitarlo.
3. Editar el dashboard → *Add widget* → *Fleet overview*.
4. Elegir grupo(s) de hosts y/o hosts individuales. Si elegís ambos se muestra la unión de las dos selecciones; sin selección se muestran todos los monitoreados.

## Reglas de estructura que este módulo respeta

- Las carpetas van en **minúscula**: `actions/`, `includes/`, `views/`, `assets/`
  (Linux distingue mayúsculas y Zabbix no encuentra las clases si no).
- Los namespaces de módulos de terceros son `Modules\FleetOverview\...`.
- La action del manifest es `widget.<id>.view`, con el id exacto del módulo.
- En `assets` del manifest van solo los nombres de archivo; Zabbix ya
  antepone `assets/css/` y `assets/js/`.

## Rollback

Deshabilitar el módulo en *Administration → General → Modules*, o borrar
`/usr/share/zabbix/modules/fleet_overview`.

## Notas

- El módulo solo lee datos con la API interna (`Host`, `Item`, `Trigger`,
  `Problem`) y respeta los permisos del usuario que ve el dashboard.
- Los colores de los badges están fijos en el CSS; si en el tema oscuro no
  se leen bien, se ajustan en `assets/css/fleet_overview.css`.
