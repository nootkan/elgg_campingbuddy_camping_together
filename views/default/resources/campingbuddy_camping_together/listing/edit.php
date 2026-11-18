<?php

/**
 * views/default/resources/campingbuddy_camping_together/listing/edit.php
 * yoursite.com/camping-together/edit/{guid}
 *
 * Organizer-only (or admin, via the shared oversight extension point) --
 * same ownership-check pattern as manage.php, including the
 * ForbiddenException fix confirmed correct via real live testing
 * 2026-08-25 (NOT the invented, nonexistent GatekeeperException class
 * that was wrongly used here originally in manage.php -- see that
 * file's own history in campingbuddy_build_log.md).
 */

elgg_gatekeeper();

$guid = (int) elgg_extract('guid', $vars, 0);
$listing = get_entity($guid);

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException();
}

$user = elgg_get_logged_in_user_entity();
$is_owner = ((int) $listing->owner_guid === (int) $user->guid);
$is_admin_with_oversight = \CampingBuddy\Core\AccessLevel::hasFullContentOversight($user);

if (!$is_owner && !$is_admin_with_oversight) {
	throw new \Elgg\Exceptions\Http\ForbiddenException('You do not have permission to edit this listing.');
}

$back_link = elgg_view('output/url', [
	'href' => $listing->getURL(),
	'text' => '&larr; Back to ' . htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8'),
	'is_trusted' => true,
]);

$content = $back_link;
$content .= '<h2>Edit: ' . htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8') . '</h2>';
$content .= elgg_view_form('campingbuddy_camping_together/listing/edit', [], [
	'listing' => $listing,
]);

$body = elgg_view_layout('content', [
	'title' => 'Edit listing',
	'content' => $content,
]);

echo elgg_view_page('Edit: ' . $listing->title, $body);
