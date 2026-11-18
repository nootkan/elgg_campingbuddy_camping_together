<?php

/**
 * views/default/forms/campingbuddy_camping_together/request/decide.php
 *
 * A small, repeated form -- one instance per pending request on the
 * organizer's manage view (resources/campingbuddy_camping_together/
 * listing/manage.php). Expects 'request_guid' in $vars.
 */

$request_guid = (int) elgg_extract('request_guid', $vars, 0);

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'request_guid',
	'value' => $request_guid,
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Decision',
	'name' => 'decision',
	'required' => true,
	'options_values' => [
		'' => '-- Choose --',
		'approved' => 'Approve -- add to the trip',
		'declined' => 'Decline',
		'waitlisted' => 'Waitlist',
	],
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Note back to them (optional)',
	'name' => 'organizer_note',
]);

$submit = elgg_view_field([
	'#type' => 'submit',
	'value' => 'Update',
]);
elgg_set_form_footer($submit);
