/**
 * Clase JS minima. No se necesita logica extra: el widget solo muestra
 * contenido generado en el servidor (widget.view.php) y se refresca solo
 * via el ciclo de actualizacion estandar del dashboard (refresh_rate en
 * manifest.json). Si en tu build el core requiere una firma distinta,
 * compara contra assets/js/class.widget.js de un widget nativo simple
 * (ej. "clock" o "url").
 */
class CWidgetFleetOverview extends CWidget {
}
