<?php

/**
 * views/default/resources/campingbuddy_camping_together/admin/all.php
 * yoursite.com/camping-together/admin/all
 *
 * Per spec Section 11 -- lists every listing on the site regardless of
 * owner or status, with organizer name, title, region, dates, status,
 * and request count, plus links through to the full listing and its
 * underlying Trip Room.
 *
 * NO ELGG_IGNORE_ACCESS ANYWHERE IN THIS FILE, unlike manage.php --
 * confirmed via this project's own established precedent (Logbook's
 * admin oversight page, 2026-08-02): an admin account already sees
 * ACCESS_PRIVATE content sitewide through completely normal
 * elgg_get_entities() calls, per Elgg's own documented access-control
 * behavior. The override in manage.php exists because an ORGANIZER
 * (an ordinary, non-admin member) needs to see other members' private
 * requests -- that's a real access boundary an admin was never subject
 * to in the first place.
 *
 * DELIBERATE REUSE, not a gap: this page does NOT duplicate request
 * message/preference rendering -- manage.php already displays that in
 * full, and per its own ownership check (Section 9), an admin already
 * passes `hasFullContentOversight()` there as a fallback to the owner
 * check. So this page links out to manage.php for that detail rather
 * than rebuilding it, satisfying spec Section 11's "admin visibility
 * into request messages" requirement without new duplicate code.
 */

elgg_gatekeeper();

$user = elgg_get_logged_in_user_entity();

if (!\CampingBuddy\Core\AccessLevel::hasFullContentOversight($user)) {
	throw new \Elgg\Exceptions\Http\ForbiddenException('You do not have permission to view this page.');
}

// Every listing, site-wide, any owner, any status -- deliberately
// unfiltered, unlike all.php (status-filtered) and mine.php
// (owner-filtered).
$listings = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_listing',
	'limit' => 0,
]);

$status_labels = [
	'gauging_interest' => 'Gauging Interest',
	'open' => 'Open',
	'full' => 'Full',
	'closed' => 'Closed',
	'completed' => 'Completed',
	'cancelled' => 'Cancelled',
];

$content = '<p>' . elgg_view('output/url', [
	'href' => 'campingbuddy_core/moderator-hub',
	'text' => '&larr; Back to Moderator Hub',
	'is_trusted' => true,
]) . '</p>';
$content .= '<p>Every Camping Together listing on the site, regardless of owner or status.</p>';
$content .= '<p><strong>Total listings:</strong> ' . count($listings) . '</p>';

if (empty($listings)) {
	$content .= '<p>No listings exist yet.</p>';
} else {
	$content .= '<table class="elgg-table" style="width:100%; border-collapse: collapse;">';
	$content .= '<thead><tr>';
	foreach (['Organizer', 'Title', 'Region', 'Dates', 'Status', 'Requests', 'Links'] as $header) {
		$content .= '<th style="text-align:left; border-bottom:1px solid #ccc; padding:0.5rem;">' . $header . '</th>';
	}
	$content .= '</tr></thead><tbody>';

	foreach ($listings as $listing) {
		if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
			continue;
		}

		$organizer = get_user((int) $listing->owner_guid);
		$organizer_link = $organizer instanceof \ElggUser
			? elgg_view('output/url', ['href' => $organizer->getURL(), 'text' => $organizer->getDisplayName(), 'style' => 'text-decoration: underline;'])
			: 'Unknown member';

		$title_link = elgg_view('output/url', [
			'href' => $listing->getURL(),
			'text' => htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8'),
			'is_trusted' => true,
			'style' => 'text-decoration: underline;',
		]);

		$region = $listing->getRegion();
		$subregion = $listing->getSubregion();
		$region_text = trim((string) $region . ($subregion ? ' -- ' . $subregion : ''));
		if ($region_text === '') {
			$region_text = '(none)';
		}

		$start = $listing->getTargetStart();
		$end = $listing->getTargetEnd();
		if ($listing->isDateFlexible()) {
			$dates_text = 'Flexible';
		} elseif ($start || $end) {
			$dates_text = ($start ? date('M j, Y', $start) : '?') . ($end ? ' - ' . date('M j, Y', $end) : '');
		} else {
			$dates_text = '(none)';
		}

		$status = $listing->getStatus();
		$status_label = $status_labels[$status] ?? ucfirst($status);

		// Total request count against this listing. Same
		// listing_guid-metadata query pattern as manage.php -- no
		// ELGG_IGNORE_ACCESS needed here, per this file's own header
		// comment (admin already sees ACCESS_PRIVATE requests natively).
		$requests = elgg_get_entities([
			'type' => 'object',
			'subtype' => 'campingbuddy_ct_request',
			'metadata_name_value_pairs' => [
				['name' => 'listing_guid', 'value' => $listing->guid],
			],
			'limit' => 0,
		]);
		$approved_count = 0;
		foreach ($requests as $r) {
			if ($r instanceof \CampingBuddy\CampingTogether\CampingTogetherRequest && $r->getStatus() === 'approved') {
				$approved_count++;
			}
		}
		$request_summary = count($requests) . ' (' . $approved_count . ' approved)';

		$links = elgg_view('output/url', [
			'href' => 'camping-together/manage/' . $listing->guid,
			'text' => 'Manage',
			'is_trusted' => true,
			'style' => 'text-decoration: underline;',
		]);

		$trip_room_guid = $listing->getTripRoomGuid();
		$group = ($trip_room_guid !== null) ? get_entity($trip_room_guid) : null;
		if ($group instanceof \ElggGroup) {
			$links .= ' / ' . elgg_view('output/url', [
				'href' => $group->getURL(),
				'text' => 'Trip Room',
				'is_trusted' => true,
				'style' => 'text-decoration: underline;',
			]);
		}

		$content .= '<tr>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . $organizer_link . '</td>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . $title_link . '</td>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . htmlspecialchars($region_text, ENT_QUOTES, 'UTF-8') . '</td>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . htmlspecialchars($dates_text, ENT_QUOTES, 'UTF-8') . '</td>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</td>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . htmlspecialchars($request_summary, ENT_QUOTES, 'UTF-8') . '</td>';
		$content .= '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' . $links . '</td>';
		$content .= '</tr>';
	}

	$content .= '</tbody></table>';
}

$body = elgg_view_layout('content', [
	'title' => 'Camping Together -- Admin: All Listings',
	'content' => $content,
]);

echo elgg_view_page('Camping Together -- Admin: All Listings', $body);
