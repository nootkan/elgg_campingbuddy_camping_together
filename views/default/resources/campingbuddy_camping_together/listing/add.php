<?php

/**
 * views/default/resources/campingbuddy_camping_together/listing/add.php
 *
 * yoursite.com/camping-together/add
 *
 * Any logged-in member may create a listing.
 *
 * Normalized to elgg_gatekeeper() during the 2026-08-28 security audit,
 * for consistency with every other resource file in this plugin. The
 * previous http_response_code(403)+exit pattern was already functionally
 * safe (this was a style/consistency finding, not a vulnerability) --
 * see campingbuddy_camping_together_security_audit.md.
 */

elgg_gatekeeper();

$back_link = elgg_view('output/url', [
	'href' => 'camping-together',
	'text' => '&larr; Back to Camping Together',
	'is_trusted' => true,
]);

$title = 'Create a Camping Together listing';
$content = $back_link . elgg_view_form('campingbuddy_camping_together/listing/save');

echo elgg_view_page($title, [
	'content' => $content,
]);
