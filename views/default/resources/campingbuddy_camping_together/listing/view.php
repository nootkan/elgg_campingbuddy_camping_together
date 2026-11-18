<?php

/**
 * views/default/resources/campingbuddy_camping_together/listing/view.php
 * yoursite.com/camping-together/view/{guid}/{title?}
 *
 * Route registered as 'view:object:campingbuddy_ct_listing' in
 * elgg-plugin.php -- that naming convention is what makes
 * CampingTogetherListing::getURL() automatically resolve to this page,
 * same confirmed pattern as every prior plugin's entity view route.
 *
 * No extra per-viewer field-hiding logic needed here: per spec Section
 * 9, everything stored on the listing entity itself IS the "general
 * fields" every member is meant to see -- the exact destination,
 * discussion, and other members live entirely inside the underlying
 * Trip Room (a separate entity this page never touches), not as
 * fields on this listing that would need conditional hiding.
 *
 * Step 3/4 update (2026-08-22): this page shows either --
 *   - the organizer's own "Manage requests" link, if the current
 *     viewer owns this listing;
 *   - the member's own existing request status, if they've already
 *     requested to join (any status);
 *   - a "Request to join" BUTTON linking to its own dedicated page
 *     (not an inline form -- moved there 2026-08-22 per direct user
 *     feedback that the full form took up too much of this page), if
 *     neither of the above and the listing is currently accepting
 *     requests (Gauging Interest or Open); or
 *   - a plain "not currently accepting requests" note otherwise.
 * Exactly one of these four is shown.
 */

elgg_gatekeeper();

$guid = (int) elgg_extract('guid', $vars, 0);
$listing = get_entity($guid);

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException();
}

$page_title = $listing->title;
$user = elgg_get_logged_in_user_entity();

$back_link = elgg_view('output/url', [
	'href' => 'camping-together',
	'text' => '&larr; Back to Camping Together',
	'is_trusted' => true,
]);

$content = $back_link . elgg_view_entity($listing, ['full_view' => true]);

$is_owner = ((int) $listing->owner_guid === (int) $user->guid);

if ($is_owner) {
	$content .= '<p>';
	$content .= elgg_view('output/url', [
		'href' => 'camping-together/edit/' . $listing->guid,
		'text' => 'Edit listing',
		'is_trusted' => true,
		'class' => 'elgg-button elgg-button-action',
	]);
	$content .= ' ';
	$content .= elgg_view('output/url', [
		'href' => 'camping-together/manage/' . $listing->guid,
		'text' => 'Manage requests',
		'is_trusted' => true,
		'class' => 'elgg-button elgg-button-action',
	]);
	$content .= '</p>';
} else {
	// Does this member already have a request against this listing?
	// Runs as the current user (no access override needed) -- under
	// normal Elgg rules a member can already see their own
	// ACCESS_PRIVATE entities, which is exactly the check needed here.
	$existing_requests = elgg_get_entities([
		'type' => 'object',
		'subtype' => 'campingbuddy_ct_request',
		'owner_guid' => $user->guid,
		'metadata_name_value_pairs' => [
			['name' => 'listing_guid', 'value' => $listing->guid],
		],
		'limit' => 1,
	]);

	$status_labels = [
		'requested' => 'Your request is pending -- the organizer hasn\'t decided yet.',
		'approved' => 'Your request was approved -- you should now have access to the trip space.',
		'declined' => 'Your request was declined.',
		'waitlisted' => 'You\'re on the waitlist for this trip.',
		'withdrawn' => 'You withdrew your request.',
	];

	if (!empty($existing_requests)) {
		$existing = $existing_requests[0];
		if ($existing instanceof \CampingBuddy\CampingTogether\CampingTogetherRequest) {
			$status = $existing->getStatus();
			$content .= '<p><strong>' . htmlspecialchars($status_labels[$status] ?? ucfirst($status), ENT_QUOTES, 'UTF-8') . '</strong></p>';
		}
	} elseif (in_array($listing->getStatus(), ['gauging_interest', 'open'], true)) {
		$content .= '<p>' . elgg_view('output/url', [
			'href' => 'camping-together/request/add/' . $listing->guid,
			'text' => 'Request to join',
			'is_trusted' => true,
			'class' => 'elgg-button elgg-button-action',
		]) . '</p>';
	} else {
		$content .= '<p><em>This listing is not currently accepting requests.</em></p>';
	}
}

$body = elgg_view_layout('content', [
	'title' => $page_title,
	'content' => $content,
]);

echo elgg_view_page($page_title, $body);
