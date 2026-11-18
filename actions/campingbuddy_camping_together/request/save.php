<?php

/**
 * actions/campingbuddy_camping_together/request/save.php
 *
 * Creates a campingbuddy_ct_request against a listing -- per
 * campingbuddy_camping_together_spec.md Section 5.
 *
 * PRIVACY DESIGN, worth being explicit about: access_id is set to
 * ACCESS_PRIVATE, which per this project's own established
 * understanding (documented repeatedly since the 2026-08-11 Trip Rooms
 * bug) means "visible only to this entity's owner" under Elgg's normal
 * access rules -- i.e. only the requester themselves, browsing
 * normally, can see their own request. The listing's ORGANIZER is
 * deliberately NOT given access through this mechanism at all --
 * they see every request against their own listing through the manage
 * view's own narrowly-scoped ELGG_IGNORE_ACCESS override instead (see
 * resources/campingbuddy_camping_together/listing/manage.php), which
 * verifies real ownership of the listing before bypassing anything.
 * This keeps the trust boundary in exactly one place (the manage view's
 * ownership check) rather than spread across an access collection that
 * would risk other requesters seeing each other's requests too -- which
 * spec Section 12 explicitly requires NOT happen.
 */

if (!elgg_is_logged_in()) {
	return elgg_error_response('You must be logged in to do that.');
}

$user = elgg_get_logged_in_user_entity();

$listing_guid = (int) get_input('listing_guid', 0);
$message = trim((string) get_input('message', ''));
$camping_setup = trim((string) get_input('camping_setup', ''));

$pref_pets = trim((string) get_input('pref_pets', ''));
$pref_children = trim((string) get_input('pref_children', ''));
$pref_smoking = trim((string) get_input('pref_smoking', ''));
$pref_alcohol = trim((string) get_input('pref_alcohol', ''));
$pref_noise = trim((string) get_input('pref_noise', ''));
$pref_experience = trim((string) get_input('pref_experience', ''));
$pref_atmosphere = trim((string) get_input('pref_atmosphere', ''));
$additional_comments = trim((string) get_input('additional_comments', ''));

$agree_to_rules = (bool) get_input('agree_to_rules', false);

// ---- required-field checks ----

if ($message === '') {
	return elgg_error_response('Please enter a message to the organizer.');
}

if (!$agree_to_rules) {
	return elgg_error_response('Please confirm you have read and agree to the guidelines before requesting to join.');
}

$listing = get_entity($listing_guid);

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	return elgg_error_response('That listing could not be found.');
}

// A listing only accepts requests while it's Gauging Interest or Open --
// the other four lifecycle statuses (full/closed/completed/cancelled)
// are not accepting new requests. Checked server-side regardless of
// whether the "Request to join" button was even shown on the page,
// since the page-level check alone can't be trusted.
if (!in_array($listing->getStatus(), ['gauging_interest', 'open'], true)) {
	return elgg_error_response('This listing is not currently accepting requests.');
}

// An organizer requesting to join their own listing doesn't make sense
// -- they're already the Trip Room's owner.
if ((int) $listing->owner_guid === (int) $user->guid) {
	return elgg_error_response('You can\'t request to join your own listing.');
}

// ---- duplicate-request prevention ----
// A member should only ever have one active request per listing. This
// query runs as the current user (no ELGG_IGNORE_ACCESS needed) --
// under normal Elgg access rules a member can already see their own
// ACCESS_PRIVATE entities, which is exactly the check needed here.
$existing = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'campingbuddy_ct_request',
	'owner_guid' => $user->guid,
	'metadata_name_value_pairs' => [
		['name' => 'listing_guid', 'value' => $listing->guid],
		['name' => 'status', 'value' => ['requested', 'approved', 'waitlisted'], 'operand' => 'IN'],
	],
	'limit' => 1,
]);

if (!empty($existing)) {
	return elgg_error_response('You already have an active request for this listing.');
}

try {
	$request = new \CampingBuddy\CampingTogether\CampingTogetherRequest();
	$request->owner_guid = $user->guid;
	// container_guid deliberately left unset -- defaults to owner_guid
	// (the requester), same as every other entity in this project. See
	// the entity class header for why this replaced the original
	// container_guid = listing approach. Which listing this belongs to
	// is tracked via setListingGuid() below instead.
	$request->title = 'Request to join: ' . $listing->title; // ElggObject requires a title; not shown anywhere in the UI, just internal record-keeping.
	$request->access_id = ACCESS_PRIVATE; // see file header -- author-only by design; organizer sees it via the manage view's own override.

	$request->setListingGuid((int) $listing->guid);
	$request->setMessage($message);

	if ($camping_setup !== '') {
		$request->setCampingSetup($camping_setup); // throws if not a recognized setup
	}

	$request->setStatus('requested');

	if ($pref_pets !== '') {
		$request->setPrefPets($pref_pets);
	}
	if ($pref_children !== '') {
		$request->setPrefChildren($pref_children);
	}
	if ($pref_smoking !== '') {
		$request->setPrefSmoking($pref_smoking);
	}
	if ($pref_alcohol !== '') {
		$request->setPrefAlcohol($pref_alcohol);
	}
	if ($pref_noise !== '') {
		$request->setPrefNoise($pref_noise);
	}
	if ($pref_experience !== '') {
		$request->setPrefExperience($pref_experience);
	}
	if ($pref_atmosphere !== '') {
		$request->setPrefAtmosphere($pref_atmosphere);
	}
	if ($additional_comments !== '') {
		$request->setAdditionalComments($additional_comments); // throws if over 250 characters
	}

	$request_saved = $request->save();

	if (!$request_saved || (int) $request->guid <= 0) {
		return elgg_error_response('Your request could not be saved. Please try again.');
	}

	return elgg_ok_response($listing->getURL(), 'Your request has been sent to the organizer.');

} catch (\InvalidArgumentException $e) {
	return elgg_error_response($e->getMessage());
} catch (\Throwable $e) {
	error_log('CampingBuddy Camping Together: request save action failed - ' . $e->getMessage());
	return elgg_error_response('Something went wrong while saving your request. Please try again.');
}
