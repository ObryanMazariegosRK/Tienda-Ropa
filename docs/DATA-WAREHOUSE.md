# Data warehouse y bitácora

> Solo en la rama `app-movil` (proyecto universitario). La rama `main` no lo incluye.

La tienda usa **dos bases de datos** en el mismo servidor MySQL:

| Base | Tipo | Para qué |
|---|---|---|
| `tienda_ropa` | OLTP (operativa) | El día a día: productos, carritos, pedidos, subastas. Sus datos cambian todo el tiempo. |
| `tienda_dw` | OLAP (data warehouse) + bitácora | Historia del negocio para análisis y registro de auditoría. **Inmutable**: solo se agregan filas. |

```
 tienda_ropa (OLTP)                       tienda_dw (OLAP)
┌──────────────────┐   ETL cada hora   ┌──────────────────────────────┐
│ orders           │ ───────────────▶  │ hechos_ventas    dim_fecha    │
│ order_items      │   php artisan     │ hechos_subastas  dim_producto │
│ products         │   dw:cargar       │ etl_cargas       dim_cliente  │
│ categories       │                   │                               │
│ auctions, bids   │   middleware      │ bitacora                      │
│ users            │ ───────────────▶  │                               │
└──────────────────┘  al momento       └──────────────────────────────┘
```

## Bitácora

Registra **quién hizo qué y cuándo**: inicios de sesión (correctos y fallidos), registro de
cuentas, pedidos, cambios de estado de pedidos, y todo lo que el admin hace con productos,
categorías, banners, subastas y cuentas de correo.

- Se captura sola con el middleware `App\Http\Middleware\RegistrarBitacora`. Qué se registra
  y qué datos se guardan está en `App\Infraestructure\Bitacora\CatalogoBitacora`: es una
  **lista blanca** de campos, así nunca se guardan contraseñas ni códigos.
- Guarda cómo estaba el registro **antes** del cambio (por ejemplo, el estado anterior del
  pedido), lo que se envió, la IP y el navegador.
- Si la bitácora falla, la acción del usuario sigue normal: nunca se interrumpe una venta.
- Se consulta en el panel: **Configuración → Bitácora** (solo lectura, con filtros).

## Data warehouse (esquema estrella)

```
                    dim_fecha
             (día, mes, trimestre, año,
              día de la semana, fin de semana)
                        │
 dim_producto ──── hechos_ventas ──── dim_cliente
 (nombre, categoría,  (cantidad, ingreso,    (fecha de registro,
  categoría principal, costo, ganancia,       correo o Google)
  fecha de publicación) días para vender,
                       venta directa o subasta)
                        │
                 hechos_subastas
            (precio inicial y final, % de
             aumento, pujas, postores, resultado)
```

- **Hechos:** los eventos medibles. `hechos_ventas` tiene una fila por prenda vendida;
  `hechos_subastas`, una por subasta terminada.
- **Dimensiones:** el contexto para agrupar (cuándo, qué producto, qué cliente).
- **Granularidad de las ventas:** una línea de pedido, en el día de Guatemala en que se
  confirmó el pago.

### Cómo se mantiene inmutable

1. **Permisos:** la tienda escribe con el usuario `tienda_dw`, que solo tiene `SELECT` e
   `INSERT` en esa base.
2. **Triggers:** todas las tablas tienen triggers `BEFORE UPDATE` y `BEFORE DELETE` que
   rechazan la operación con el mensaje *"El data warehouse es inmutable"*.
3. **Anulaciones en lugar de borrados:** si un pedido confirmado se cancela después, no se
   borra su venta: se agrega una fila `anulacion` con cantidad y montos negativos. Las sumas
   dan el neto y la historia queda completa.
4. **Dimensiones de cambio lento tipo 2, sin updates:** si un producto cambia de nombre o de
   categoría se agrega una **versión nueva** con su `valido_desde`; las ventas anteriores
   siguen apuntando a la versión que existía cuando ocurrieron.

### ETL (Extraer, Transformar, Cargar)

`php artisan dw:cargar` (caso de uso `CargarDataWarehouseUseCase`):

1. **Extrae** de `tienda_ropa` las líneas de los pedidos confirmados y las subastas con
   resultado definitivo (`OrigenDatosRepository`).
2. **Transforma:** calcula ingreso, costo, ganancia, días para vender, el día en hora de
   Guatemala y el resultado de cada subasta; resuelve la versión vigente de cada dimensión.
3. **Carga** solo lo nuevo en `tienda_dw` (`DataWarehouseRepository`). Es incremental e
   idempotente: se puede correr las veces que sea sin duplicar (índice único por línea y tipo
   de movimiento).

Lo corre el scheduler **cada hora**; también el botón **Actualizar ahora** de la pantalla
Análisis. Cada ejecución queda registrada en `etl_cargas`.

### Análisis

Panel → **Análisis**. Muestra lo que el dashboard (que lee la base operativa) no tiene:

- Tendencia de 12 meses comparada con el año anterior.
- Ventas por día de la semana.
- Margen de ganancia por categoría y subcategoría.
- Desempeño de las subastas (precio inicial vs final, pujas, resultados).
- Clientes nuevos vs recurrentes y ticket promedio.
- Días promedio que tarda un producto en venderse, por categoría.

## Instalación

### En tu computadora (Docker de desarrollo)

```bash
# 1. Crear la base tienda_dw y el usuario de solo SELECT/INSERT (una vez)
docker compose exec app php artisan dw:sql-instalacion | docker compose exec -T mysql mysql -uroot -proot

# 2. Crear las tablas y los triggers
docker compose exec app php artisan dw:migrar

# 3. Primera carga
docker compose exec app php artisan dw:cargar
```

Variables del `.env`: `DW_DB_DATABASE`, `DW_DB_USERNAME`, `DW_DB_PASSWORD` (ver `.env.example`).

### En el servidor (Coolify)

1. En Coolify → *Environment Variables*, agrega `DW_DB_PASSWORD` con una contraseña larga
   (las demás tienen valor por defecto) y despliega.
2. Por SSH, crea la base y el usuario **una vez**:
   ```bash
   APP=$(sudo docker ps --filter "name=^app-" --format "{{.Names}}" | head -1)
   MYSQL=$(sudo docker ps --filter "name=^mysql-" --format "{{.Names}}" | head -1)
   sudo docker exec "$APP" php artisan dw:sql-instalacion \
     | sudo docker exec -i "$MYSQL" sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
   sudo docker exec "$APP" php artisan dw:migrar
   sudo docker exec "$APP" php artisan dw:cargar
   ```
3. Desde ahí, cada despliegue aplica solo las migraciones nuevas del warehouse
   (`dw:migrar` corre al arrancar el contenedor) y el scheduler lo carga cada hora.

## Comprobar la inmutabilidad (para la presentación)

```bash
# Con el usuario de la tienda: no tiene permiso de UPDATE
mysql -u tienda_dw -p tienda_dw -e "UPDATE bitacora SET descripcion = 'x'"
# ERROR 1142: UPDATE command denied to user 'tienda_dw'

# Aunque tuviera permiso (root), el trigger lo rechaza
mysql -u root -p tienda_dw -e "DELETE FROM hechos_ventas"
# ERROR 1644: El data warehouse es inmutable: no se puede borrar en hechos_ventas
```
