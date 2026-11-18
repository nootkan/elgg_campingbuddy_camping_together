<?php

/**
 * views/default/resources/campingbuddy_camping_together/request/add.php
 * yoursite.com/camping-together/request/add/{guid}
 *
 * The "Request to join" form now lives on its own dedicated page,
 * linked from a button on the listing view page, rather than being
 * embedded inline there -- per direct user feedback (2026-08-22): the
 * full form (message, camping setup, seven preference dropdowns,
 * additional comments, guidelines, acknowledgment checkbox) took up too
 * much of the listing page itself.
 *
 * All the same eligibility checks the inline version used to do are
 * still done here, just as plain redirects back to the listing instead
 * of conditionally hiding a form section -- the listing page's own
 * existing logic (organizer sees "Manage requests", an existing
 * requester sees their status, a non-accepting listing shows a plain
 * note) already correctly communicates *why* someone landed back there,
 * so no flash-message mechanism is needed to explain the redirect.
 *
 * Deliberately uses a plain PHP header()/exit redirect rather than
 * Elgg's forward() function -- forward() is confirmed removed/broken in
 * this Elgg version (campingbuddy_coding_conventions.md Section 11).
 * header() is core PHP, not an Elgg API, so it carries none of that risk.
 */

elgg_gatekeeper();

$guid = (int) elgg_extract('guid', $vars, 0);
$listing = get_entity($guid);

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException();
}

$user = elgg_get_logged_in_user_entity();

// An organizer requesting to join their own listing doesn't make sense
// -- send them back to the listing, where they'll see the "Manage
// requests" button instead.
if ((int) $listing->owner_guid === (int) $user->guid) {
	header('Location: ' . $listing->getURL());
	exit;
}

// Already has an active request? Send them back to the listing, where
// their current status is already shown.
$existing_requests = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_request',
	'owner_guid' => $user->guid,
	'metadata_name_value_pairs' => [
		['name' => 'listing_guid', 'value' => $listing->guid],
	],
	'limit' => 1,
]);

if (!empty($existing_requests)) {
	header('Location: ' . $listing->getURL());
	exit;
}

// Listing not currently accepting requests? Same -- back to the
// listing, where a plain note already explains this.
if (!in_array($listing->getStatus(), ['gauging_interest', 'open'], true)) {
	header('Location: ' . $listing->getURL());
	exit;
}

$back_link = elgg_view('output/url', [
	'href' => $listing->getURL(),
	'text' => '&larr; Back to ' . htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8'),
	'is_trusted' => true,
]);

$content = $back_link;
$content .= '<h2>Request to join: ' . htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8') . '</h2>';
$content .= elgg_view_form('campingbuddy_camping_together/request/save', [], [
	'listing_guid' => (int) $listing->guid,
]);

$body = elgg_view_layout('content', [
	'title' => 'Request to join',
	'content' => $content,
]);

echo elgg_view_page('Request to join: ' . $listing->title, $body);
