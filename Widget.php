<?php declare(strict_types = 0);

namespace Modules\FleetOverview;

use Zabbix\Core\CWidget;

/**
 * Clase base del widget. El core de Zabbix la instancia segun lo declarado
 * en manifest.json ("widget.class"). No requiere logica adicional salvo
 * que quieras sobreescribir metodos como getDefaultName().
 */
class Widget extends CWidget {

	public function getDefaultName(): string {
		return _('Fleet overview');
	}
}
