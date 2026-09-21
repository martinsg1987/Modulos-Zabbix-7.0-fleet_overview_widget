<?php declare(strict_types = 0);

namespace Modules\FleetOverview\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$groupids = $this->fields_values['groupids'];
		$selected_hostids = $this->fields_values['hostids'];
		$hide_ok = (bool) $this->fields_values['hide_ok'];
		$problems_limit = max(1, min(100, (int) $this->fields_values['problems_limit']));

		// 1) Hosts monitoreados. Sin seleccion = todos. Con grupos y/o hosts
		// individuales se muestra la UNION de ambas selecciones.
		$host_options = [
			'output' => ['hostid', 'host', 'name', 'status'],
			'selectInterfaces' => ['ip', 'available', 'type', 'main'],
			'monitored_hosts' => true,
			'preservekeys' => true
		];

		if (!$groupids && !$selected_hostids) {
			$hosts = API::Host()->get($host_options);
		}
		else {
			$hosts = [];

			if ($groupids) {
				$hosts += API::Host()->get($host_options + ['groupids' => $groupids]);
			}

			if ($selected_hostids) {
				$hosts += API::Host()->get($host_options + ['hostids' => $selected_hostids]);
			}
		}

		$hostids = array_keys($hosts);

		foreach ($hosts as &$host) {
			$host['os'] = null;
			$host['uptime'] = null;
			$host['problems_count'] = 0;
			$host['max_severity'] = -1;
		}
		unset($host);

		$problems = [];

		if ($hostids) {
			// 2) Items de SO y uptime (requiere que existan en el host, ej.
			// templates "Linux by Zabbix agent" / "Windows by Zabbix agent")
			$items = API::Item()->get([
				'output' => ['hostid', 'key_', 'lastvalue'],
				'hostids' => $hostids,
				'filter' => ['key_' => ['system.sw.os', 'system.uptime']]
			]);

			foreach ($items as $item) {
				if (!array_key_exists($item['hostid'], $hosts)) {
					continue;
				}
				if ($item['key_'] === 'system.sw.os') {
					$hosts[$item['hostid']]['os'] = $item['lastvalue'];
				}
				elseif ($item['key_'] === 'system.uptime') {
					$hosts[$item['hostid']]['uptime'] = $item['lastvalue'];
				}
			}

			// 3) Problemas activos de esos hosts.
			// problem.get no soporta selectHosts: se mapea trigger -> host.
			$triggers = API::Trigger()->get([
				'output' => ['triggerid'],
				'hostids' => $hostids,
				'selectHosts' => ['hostid', 'name'],
				'monitored' => true,
				'preservekeys' => true
			]);

			if ($triggers) {
				$raw_problems = API::Problem()->get([
					'output' => ['eventid', 'objectid', 'name', 'severity', 'clock'],
					'source' => EVENT_SOURCE_TRIGGERS,
					'object' => EVENT_OBJECT_TRIGGER,
					'objectids' => array_keys($triggers),
					'recent' => false,
					'sortfield' => ['eventid'],
					'sortorder' => 'DESC'
				]);

				foreach ($raw_problems as $problem) {
					$problem_hosts = [];

					foreach ($triggers[$problem['objectid']]['hosts'] ?? [] as $trigger_host) {
						if (array_key_exists($trigger_host['hostid'], $hosts)) {
							$problem_hosts[] = $trigger_host;

							$hosts[$trigger_host['hostid']]['problems_count']++;
							$hosts[$trigger_host['hostid']]['max_severity'] = max(
								$hosts[$trigger_host['hostid']]['max_severity'],
								(int) $problem['severity']
							);
						}
					}

					if ($problem_hosts) {
						$problem['hosts'] = $problem_hosts;
						$problems[] = $problem;
					}
				}
			}
		}

		// Resumen global (antes de aplicar "ocultar hosts sin problemas")
		$summary = ['total' => count($hosts), 'ok' => 0, 'problems' => 0, 'critical' => 0];

		foreach ($hosts as $summary_host) {
			if ($summary_host['problems_count'] === 0) {
				$summary['ok']++;
			}
			else {
				$summary['problems']++;
			}

			if ($summary_host['max_severity'] >= 4) {
				$summary['critical']++;
			}
		}

		if ($hide_ok) {
			$hosts = array_filter($hosts, static fn(array $h): bool => $h['problems_count'] > 0);
		}

		// Orden: primero los que tienen problemas (mayor severidad primero), despues OK
		uasort($hosts, static function (array $a, array $b): int {
			return $b['max_severity'] <=> $a['max_severity'];
		});

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'summary' => $summary,
			'hosts' => $hosts,
			'problems' => array_slice($problems, 0, $problems_limit),
			'user' => ['debug_mode' => $this->getDebugMode()]
		]));
	}
}
