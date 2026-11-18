<?php

/**
 * actions/campingbuddy_camping_together/request/decide.php
 *
 * The organizer's approve/decline/waitlist action for a single request
 * -- per campingbuddy_camping_together_spec.md Section 6 (workflow
 * step 4) and Section 4 (approval adds the member to the Trip Room).
 *
 * FLAGGED AS NEW TERRITORY FOR THIS PROJECT: this is the first use of
 * elgg_call(ELGG_IGNORE_ACCESS, ...) anywhere in CampingBuddy. It's a
 * well-established, documented Elgg mechanism for exactly this
 * situation -- a request's access_id is ACCESS_PRIVATE (author-only,
 * per the save action's own design notes), so the organizer, who is
 * NOT that entity's owner, cannot load it through Elgg's normal access
 * rules at all. The override below is used as narrowly as possible:
 * ownership of the LISTING (a completely separate, normally-readable
 * entity) is verified FIRST, using data the current user can already
 * see without any override, and the access bypass is scoped to only
 * the one get_entity() call that actually needs it -- never applied
 * broadly, never applied before the ownership check. Please give this
 * file a real, deliberate test with a second test account (a genuine
 * organizer/non-organizer pair, not just the admin account) before
 * considering it confirmed -- same standing caution this project
 * already applies to Trip Rooms' own access-control logic.
 */

if (!elgg_is_logged_in()) {
	return elgg_error_response('You must be logged in to do that.');
}

$user = elgg_get_logged_in_user_entity();

$request_guid = (int) get_input('request_guid', 0);
$decision = trim((string) get_input('decision', ''));
$organizer_note = trim((string) get_input('organizer_note', ''));

if (!in_array($decision, ['approved', 'declined', 'waitlisted'], true)) {
	return elgg_error_response('Please choose a valid decision.');
}

// ---- load the request, bypassing normal access rules ----
// This is the ONLY line in this file that uses ELGG_IGNORE_ACCESS, and
// it happens before any ownership check because there's no way to even
// look up which listing this request belongs to otherwise -- the
// ownership check immediately below is what actually authorizes
// everything that follows.
$request = elgg_call(ELGG_IGNORE_ACCESS, function () use ($request_guid) {
	return get_entity($request_guid);
});

if (!$request instanceof \CampingBuddy\CampingTogether\CampingTogetherRequest) {
	return elgg_error_response('That request could not be found.');
}

// The listing itself is ACCESS_LOGGED_IN (spec Section 2/9) -- any
// logged-in member, including this organizer, can load it through
// completely normal Elgg access rules. No override needed here.
$listing_guid = $request->getListingGuid();
$listing = ($listing_guid !== null) ? get_entity($listing_guid) : null;

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	return elgg_error_response('That listing could not be found.');
}

// ---- the real authorization check ----
// Only the listing's actual owner, or an admin via the project's shared
// oversight extension point, may decide on a request against it.
$is_owner = ((int) $listing->owner_guid === (int) $user->guid);
$is_admin_with_oversight = \CampingBuddy\Core\AccessLevel::hasFullContentOversight($user);

if (!$is_owner && !$is_admin_with_oversight) {
	return elgg_error_response('You do not have permission to do that.');
}

try {
	$request->setStatus($decision); // throws if somehow not a recognized status
	if ($organizer_note !== '') {
		$request->setOrganizerNote($organizer_note);
	}

	// Saving also happens inside the same narrow override, for the same
	// reason loading did -- the organizer isn't this entity's owner, so
	// without this, the update could silently fail to persist correctly.
	$saved = elgg_call(ELGG_IGNORE_ACCESS, function () use ($request) {
		return $request->save();
	});

	if (!$saved) {
		return elgg_error_response('The decision could not be saved. Please try again.');
	}

	// ---- on approval, add the requester to the underlying Trip Room ----
	if ($decision === 'approved') {
		$trip_room_guid = $listing->getTripRoomGuid();
		$group = ($trip_room_guid !== null) ? get_entity($trip_room_guid) : null;
		$requester = get_user((int) $request->owner_guid);

		if ($group instanceof \ElggGroup && $requester instanceof \ElggUser) {
			// The organizer already owns this group, so they can see and
			// act on it through completely normal Elgg rules -- no
			// override needed for this part.
			if (!$group->isMember($requester)) {
				$group->join($requester);
			}
		} else {
			// Extremely unlikely (would mean the listing's own
			// trip_room_guid is broken) but worth not silently losing --
			// the request status was already saved as approved above, so
			// this doesn't block that, just flags the mismatch.
			error_log('CampingBuddy Camping Together: approved request ' . $request->guid . ' but could not add requester to Trip Room (listing ' . $listing->guid . ').');
		}
	}

	return elgg_ok_response(elgg_normalize_url('camping-together/manage/' . $listing->guid), 'The request has been updated.');

} catch (\InvalidArgumentException $e) {
	return elgg_error_response($e->getMessage());
} catch (\Throwable $e) {
	error_log('CampingBuddy Camping Together: request decide action failed - ' . $e->getMessage());
	return elgg_error_response('Something went wrong while saving your decision. Please try again.');
}
