<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

$form = new CWidgetFormView($data);

if (array_key_exists('groupids', $data['fields'])) {
	$form->addField(new CWidgetFieldMultiSelectGroupView($data['fields']['groupids']));
}

if (array_key_exists('hostids', $data['fields'])) {
	$form->addField(new CWidgetFieldMultiSelectHostView($data['fields']['hostids']));
}

if (array_key_exists('hide_ok', $data['fields'])) {
	$form->addField(new CWidgetFieldCheckBoxView($data['fields']['hide_ok']));
}

if (array_key_exists('problems_limit', $data['fields'])) {
	$form->addField(new CWidgetFieldIntegerBoxView($data['fields']['problems_limit']));
}

$form->show();
