<?php

/**
 * views/default/resources/campingbuddy_camping_together/mine.php
 * yoursite.com/camping-together/mine
 *
 * Per spec Section 14, item 5 -- but moved up ahead of schedule
 * (2026-08-25), after a real, live problem: the main browse page
 * (all.php) only ever shows Gauging Interest and Open listings by
 * design (spec Section 10) -- a listing set to Full/Closed/Completed/
 * Cancelled correctly disappears from there, but with no other page
 * listing a member's own content, an organizer who changed their own
 * listing's status had no way back to it at all except site-wide
 * search. This page exists specifically so that never happens again --
 * it shows every listing a member organizes AND every request they've
 * made, regardless of status, with no filtering at all.
 *
 * Two sections, same collapsible <details>/<summary> pattern already
 * used on all.php and proven working on mobile for Trip Planner.
 */

elgg_gatekeeper();

$user = elgg_get_logged_in_user_entity();

// ---- my listings (as organizer) ----
// Normal query, no access override needed -- these are the member's own
// content, fully visible to them under Elgg's ordinary rules regardless
// of status.
$my_listings = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_listing',
	'owner_guid' => $user->guid,
	'limit' => 0,
]);

// ---- my requests (as requester) ----
// Same reasoning -- a member can always see their own ACCESS_PRIVATE
// request entities under Elgg's normal owner-visibility rules, no
// override needed (unlike the organizer's manage view, which needs
// ELGG_IGNORE_ACCESS specifically because it's looking at OTHER
// members' requests, not its own).
$my_requests = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_request',
	'owner_guid' => $user->guid,
	'limit' => 0,
]);

$listing_status_labels = [
	'gauging_interest' => 'Gauging Interest',
	'open' => 'Open',
	'full' => 'Full',
	'closed' => 'Closed',
	'completed' => 'Completed',
	'cancelled' => 'Cancelled',
];

$request_status_labels = [
	'requested' => 'Pending',
	'approved' => 'Approved',
	'declined' => 'Declined',
	'waitlisted' => 'Waitlisted',
	'withdrawn' => 'Withdrawn',
];

$add_link = elgg_view('output/url', [
	'href' => 'camping-together/add',
	'text' => 'Create a Camping Together listing',
	'is_trusted' => true,
	'class' => 'elgg-button elgg-button-action',
]);

$browse_link = elgg_view('output/url', [
	'href' => 'camping-together',
	'text' => '&larr; Back to Camping Together',
	'is_trusted' => true,
]);

$content = '<p>' . $browse_link . '</p>';
$content .= '<p>' . $add_link . '</p>';

// ---- My Listings section ----
$content .= '<details open style="margin-bottom:1.5rem;">';
$content .= '<summary><strong>My Listings (' . count($my_listings) . ')</strong></summary>';

if (empty($my_listings)) {
	$content .= '<p>You haven\'t created any Camping Together listings yet.</p>';
} else {
	$content .= '<ul>';
	foreach ($my_listings as $listing) {
		if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
			continue;
		}
		$status = $listing->getStatus();
		$status_label = $listing_status_labels[$status] ?? ucfirst($status);
		$title_link = elgg_view('output/url', [
			'href' => $listing->getURL(),
			'text' => htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8'),
			'is_trusted' => true,
			'style' => 'text-decoration: underline;',
		]);
		$content .= '<li>' . $title_link . ' -- <em>' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</em></li>';
	}
	$content .= '</ul>';
}
$content .= '</details>';

// ---- My Requests section ----
$content .= '<details style="margin-bottom:1.5rem;">';
$content .= '<summary><strong>My Requests (' . count($my_requests) . ')</strong></summary>';

if (empty($my_requests)) {
	$content .= '<p>You haven\'t requested to join any trips yet.</p>';
} else {
	$content .= '<ul>';
	foreach ($my_requests as $request) {
		if (!$request instanceof \CampingBuddy\CampingTogether\CampingTogetherRequest) {
			continue;
		}
		$listing_guid = $request->getListingGuid();
		$listing = ($listing_guid !== null) ? get_entity($listing_guid) : null;
		$status = $request->getStatus();
		$status_label = $request_status_labels[$status] ?? ucfirst($status);

		if ($listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
			$title_link = elgg_view('output/url', [
				'href' => $listing->getURL(),
				'text' => htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8'),
				'is_trusted' => true,
				'style' => 'text-decoration: underline;',
			]);
		} else {
			// The listing this request was against no longer exists or
			// isn't accessible -- shown plainly rather than a broken link.
			$title_link = '<em>(listing no longer available)</em>';
		}

		$content .= '<li>' . $title_link . ' -- <em>' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</em></li>';
	}
	$content .= '</ul>';
}
$content .= '</details>';

$body = elgg_view_layout('content', [
	'title' => 'My Camping Together',
	'content' => $content,
]);

echo elgg_view_page('My Camping Together', $body);
