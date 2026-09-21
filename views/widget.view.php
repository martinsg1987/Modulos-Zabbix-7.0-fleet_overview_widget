<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

$hosts = $data['hosts'] ?? [];
$problems = $data['problems'] ?? [];
$summary = $data['summary'] ?? ['total' => count($hosts), 'ok' => 0, 'problems' => 0, 'critical' => 0];

$severity_style = static function (int $severity): string {
	return [0 => 'na', 1 => 'info', 2 => 'warning', 3 => 'average', 4 => 'high', 5 => 'disaster'][$severity] ?? 'na';
};

$severity_name = static function (int $severity): string {
	return [
		0 => _('Not classified'),
		1 => _('Information'),
		2 => _('Warning'),
		3 => _('Average'),
		4 => _('High'),
		5 => _('Disaster')
	][$severity] ?? _('Not classified');
};

// Etiqueta corta del SO (el valor completo queda en el tooltip).
$os_label = static function (string $os): string {
	if ($os === '') {
		return '';
	}
	if (stripos($os, 'windows') !== false && preg_match('/^(.+?)\s+\d{5}\./u', $os, $m)) {
		return $m[1];
	}
	if (preg_match('/^Linux version (\S+)/i', $os, $m)) {
		return 'Linux ' . $m[1];
	}

	return mb_strimwidth($os, 0, 42, '…');
};

$tile = static function (string $label, $value, string $sub, string $accent): CDiv {
	return (new CDiv([
		(new CDiv($label))->addClass('fo-tile-label'),
		(new CDiv((string) $value))->addClass('fo-tile-value'),
		(new CDiv($sub))->addClass('fo-tile-sub')
	]))->addClass('fo-tile fo-accent-' . $accent);
};

$summary_row = (new CDiv([
	$tile(_('Total'), $summary['total'], _('hosts monitoreados'), 'info'),
	$tile(_('Sin problemas'), $summary['ok'], _('hosts estables'), 'ok'),
	$tile(_('Con problemas'), $summary['problems'], _('hosts afectados'), 'warning'),
	$tile(_('Criticos'), $summary['critical'], _('High o Disaster'), 'disaster')
]))->addClass('fo-summary');

$cards_wrapper = (new CDiv())->addClass('fo-grid');

foreach ($hosts as $host) {
	$os = (string) ($host['os'] ?? '');

	if ($os !== '' && stripos($os, 'windows') !== false) {
		$type_class = 'fo-type-windows';
		$type_text = 'WIN';
	}
	elseif ($os !== '' && stripos($os, 'linux') !== false) {
		$type_class = 'fo-type-linux';
		$type_text = 'LNX';
	}
	else {
		$type_class = 'fo-type-other';
		$type_text = 'N/A';
	}

	$uptime_text = ($host['uptime'] !== null) ? convertUnitsS((int) $host['uptime']) : '—';

	$ip = '—';
	foreach ($host['interfaces'] as $iface) {
		if ((int) $iface['main'] === 1 && $iface['ip'] !== '') {
			$ip = $iface['ip'];
			break;
		}
	}

	if ($host['problems_count'] === 0) {
		$style = 'ok';
		$badge = (new CSpan(_('Sin problemas')))->addClass('fo-badge fo-badge-ok');
	}
	else {
		$style = $severity_style((int) $host['max_severity']);
		$badge = (new CSpan($host['problems_count'] . ' ' . $severity_name((int) $host['max_severity'])))
			->addClass('fo-badge fo-badge-' . $style);
	}

	$os_text = $os !== '' ? $os_label($os) : _('Sin dato de SO');

	$card = (new CDiv())
		->addClass('fo-card fo-accent-' . $style)
		->addItem(
			(new CDiv())
				->addClass('fo-card-header')
				->addItem((new CSpan($type_text))->addClass('fo-type ' . $type_class))
				->addItem(
					(new CDiv())
						->addClass('fo-title-wrap')
						->addItem((new CDiv($host['name']))->addClass('fo-host-name'))
						->addItem((new CDiv($ip))->addClass('fo-host-ip'))
				)
				->addItem((new CSpan())->addClass('fo-dot'))
		)
		->addItem(
			(new CDiv())
				->addClass('fo-card-body')
				->addItem(
					(new CDiv())
						->addClass('fo-row')
						->addItem((new CSpan(_('SO')))->addClass('fo-label'))
						->addItem((new CSpan($os_text))->addClass('fo-value')->setAttribute('title', $os))
				)
				->addItem(
					(new CDiv())
						->addClass('fo-row')
						->addItem((new CSpan(_('Uptime')))->addClass('fo-label'))
						->addItem((new CSpan($uptime_text))->addClass('fo-value'))
				)
		)
		->addItem((new CDiv($badge))->addClass('fo-card-footer'));

	$cards_wrapper->addItem($card);
}

if (!$hosts) {
	$cards_wrapper->addItem((new CDiv(_('No hay hosts que coincidan con el filtro.')))->addClass('fo-empty'));
}

// Tabla de problemas al pie, compacta
$problems_table = (new CTableInfo())
	->setHeader([_('Host'), _('Problema'), _('Severidad'), _('Hora')])
	->addClass('fo-problems-table');

foreach ($problems as $problem) {
	$style = $severity_style((int) $problem['severity']);

	$problems_table->addRow([
		$problem['hosts'][0]['name'] ?? '',
		$problem['name'],
		(new CSpan($severity_name((int) $problem['severity'])))->addClass('fo-badge fo-badge-' . $style),
		zbx_date2str(DATE_TIME_FORMAT, $problem['clock'])
	]);
}

if (!$problems) {
	$problems_table->setNoDataMessage(_('Sin problemas activos.'));
}

(new CWidgetView($data))
	->addItem(
		(new CDiv([
			$summary_row,
			$cards_wrapper,
			(new CDiv([
				(new CDiv(_('Detalle de problemas')))->addClass('fo-section-title'),
				$problems_table
			]))->addClass('fo-problems-wrapper')
		]))->addClass('fo-widget-root')
	)
	->show();
