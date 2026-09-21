<?php declare(strict_types = 0);

namespace Modules\FleetOverview\Includes;

use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost
};

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				new CWidgetFieldMultiSelectGroup('groupids', _('Grupos de hosts'))
			)
			->addField(
				new CWidgetFieldMultiSelectHost('hostids', _('Hosts individuales'))
			)
			->addField(
				new CWidgetFieldCheckBox('hide_ok', _('Ocultar hosts sin problemas'))
			)
			->addField(
				(new CWidgetFieldIntegerBox('problems_limit', _('Filas maximas en tabla de problemas')))
					->setDefault(15)
			);
	}
}
