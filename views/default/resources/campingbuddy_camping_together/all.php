<?php

/**
 * views/default/resources/campingbuddy_camping_together/all.php
 * yoursite.com/camping-together
 *
 * The main browse page, split into two collapsible sections per spec
 * Section 10: "Gauging Interest" and "Confirmed Trips" (Open listings).
 * Uses plain <details>/<summary> HTML, no JS -- the same pattern
 * confirmed working well on mobile for Trip Planner's Candidate
 * Destinations/Packing Checklist sections (campingbuddy_build_log.md,
 * 2026-08-18).
 *
 * No filtering by region/dates/camping style yet (spec Section 10
 * mentions this as part of the browse page) -- deliberately deferred
 * past this first vertical slice, same "get the list working first,
 * add filtering once there's enough real content to need it" approach
 * used for Logbook's Place dropdown. Not forgotten, just not in Step 2's
 * scope.
 *
 * Every listing here has access_id = ACCESS_LOGGED_IN (set unconditionally
 * server-side in the save action, spec Section 9/2) -- elgg_get_entities()
 * already enforces normal Elgg access control on top of the explicit
 * status filter below, so a logged-out request never reaches this file
 * at all (elgg_gatekeeper() stops it first).
 */

elgg_gatekeeper();

$gauging_interest_listings = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_listing',
	'metadata_name_value_pairs' => [
		['name' => 'status', 'value' => 'gauging_interest'],
	],
	'limit' => 0,
]);

$open_listings = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_listing',
	'metadata_name_value_pairs' => [
		['name' => 'status', 'value' => 'open'],
	],
	'limit' => 0,
]);

$back_link = elgg_view('output/url', [
	'href' => 'tools',
	'text' => '&larr; Back to Tools',
	'is_trusted' => true,
]);

$add_link = elgg_view('output/url', [
	'href' => 'camping-together/add',
	'text' => 'Create a Camping Together listing',
	'is_trusted' => true,
	'class' => 'elgg-button elgg-button-action',
]);

$mine_link = elgg_view('output/url', [
	'href' => 'camping-together/mine',
	'text' => 'My Camping Together',
	'is_trusted' => true,
	'style' => 'text-decoration: underline;',
]);

$content = '<p>' . $back_link . '</p>';
$content .= '<p>' . $add_link . '</p>';
$content .= '<p>' . $mine_link . '</p>';

$content .= '<details open style="margin-bottom:1.5rem;">';
$content .= '<summary><strong>Gauging Interest (' . count($gauging_interest_listings) . ')</strong></summary>';
if (empty($gauging_interest_listings)) {
	$content .= '<p>No one is currently gauging interest in a trip. Be the first!</p>';
} else {
	foreach ($gauging_interest_listings as $listing) {
		if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
			continue;
		}
		$content .= elgg_view_entity($listing, ['full_view' => false]);
	}
}
$content .= '</details>';

$content .= '<details style="margin-bottom:1.5rem;">';
$content .= '<summary><strong>Confirmed Trips (' . count($open_listings) . ')</strong></summary>';
if (empty($open_listings)) {
	$content .= '<p>No confirmed trips posted yet.</p>';
} else {
	foreach ($open_listings as $listing) {
		if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
			continue;
		}
		$content .= elgg_view_entity($listing, ['full_view' => false]);
	}
}
$content .= '</details>';

$body = elgg_view_layout('content', [
	'title' => 'Camping Together',
	'content' => $content,
]);

echo elgg_view_page('Camping Together', $body);
