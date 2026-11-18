<?php

/**
 * actions/campingbuddy_camping_together/listing/save.php
 *
 * Creates a new campingbuddy_ct_listing AND, in the same step, its
 * underlying Trip Room -- per campingbuddy_camping_together_spec.md
 * Section 4. This is the one action in this plugin that touches Trip
 * Room creation, so it's worth being explicit about how:
 *
 * IMPORTANT: this REPLICATES the proven group-creation logic already
 * live in actions/campingbuddy_trip_rooms/save.php (confirmed working,
 * fully audited) rather than calling that file or modifying it. Per
 * this project's standing rule, previously-audited code stays untouched
 * unless there's a specific reason to touch it -- duplicating the small
 * amount of group-setup logic needed here keeps Trip Rooms' own audit
 * status intact and keeps this plugin self-contained. If Trip Rooms'
 * own group-creation logic is ever changed, this duplicate does NOT
 * automatically pick up that change -- worth remembering if that ever
 * happens.
 *
 * The specific access-control sequence below (access_id -> save() ->
 * group_acl -> setContentAccessMode() -> save() again) is copied
 * exactly from Trip Rooms' real, working, audited action, including the
 * comments explaining the real 2026-08-11 bug it fixes (setting
 * ACCESS_PRIVATE directly instead of the group's own dynamic ACL) --
 * that history is repeated here deliberately so this file doesn't need
 * to be cross-referenced against Trip Rooms' file to understand why
 * it's written this way.
 */

if (!elgg_is_logged_in()) {
	return elgg_error_response('You must be logged in to do that.');
}

$user = elgg_get_logged_in_user_entity();

// ---- collect + lightly normalize input ----

$status = trim((string) get_input('status', ''));
$title = trim((string) get_input('title', ''));
$region = trim((string) get_input('region', ''));
$subregion = trim((string) get_input('subregion', ''));
$target_start = (int) get_input('target_start', 0);
$target_end = (int) get_input('target_end', 0);
$date_flexible = (bool) get_input('date_flexible', false);
$camping_style = trim((string) get_input('camping_style', ''));
$social_expectation = trim((string) get_input('social_expectation', ''));
$participants_preset = trim((string) get_input('participants_preset', ''));
$participants_custom = trim((string) get_input('participants_custom', ''));
$pet_info = trim((string) get_input('pet_info', ''));
$accessibility_notes = trim((string) get_input('accessibility_notes', ''));
$description = trim((string) get_input('description', ''));
$application_deadline = (int) get_input('application_deadline', 0);

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

if ($title === '') {
	return elgg_error_response('Please enter a title.');
}

// Only these two are valid at creation time -- spec Section 7. The other
// four lifecycle statuses (full/closed/completed/cancelled) are reached
// later through the manage view, not selectable here. A tampered
// submission claiming one of those is rejected outright, same
// never-trust-client-input discipline used throughout this project.
if (!in_array($status, ['gauging_interest', 'open'], true)) {
	return elgg_error_response('Please choose how firm this trip is.');
}

if (!$agree_to_rules) {
	return elgg_error_response('Please confirm you have read and agree to the guidelines before posting.');
}

// ---- participants preset -> min/max mapping ----
// Never trust the submitted preset key directly -- validated against a
// known allow-list, same discipline as every enum field in this project.
// 'other' is handled separately below since it depends on a second,
// numeric input rather than a fixed pair.
$participants_presets = [
	'2' => [2, 2],
	'3_5' => [3, 5],
	'6_8' => [6, 8],
	'9_11' => [9, 11],
	'12_plus' => [12, null],
];

$min_participants = null;
$max_participants = null;

if ($participants_preset === 'other') {
	if ($participants_custom !== '') {
		if (!is_numeric($participants_custom) || (int) $participants_custom < 1) {
			return elgg_error_response('Please enter a valid custom number of participants (1 or more).');
		}
		$min_participants = (int) $participants_custom;
		$max_participants = (int) $participants_custom;
	}
	// If 'other' was chosen but no custom number was actually entered,
	// this silently falls through to "no preference set" (both null)
	// rather than erroring -- treated the same as leaving this whole
	// optional field blank.
} elseif ($participants_preset !== '' && isset($participants_presets[$participants_preset])) {
	[$min_participants, $max_participants] = $participants_presets[$participants_preset];
} elseif ($participants_preset !== '') {
	// A submitted value that isn't 'other' and isn't a recognized preset
	// key -- reject rather than silently ignore, since this can only
	// happen via a tampered/non-standard submission.
	return elgg_error_response('Invalid participants selection.');
}

try {
	// ============================================================
	// STEP 1: create the underlying Trip Room first, so the listing
	// can reference its real GUID once saved.
	// ============================================================

	$group = new \ElggGroup();
	$group->owner_guid = $user->guid;
	$group->name = $title;
	$group->description = ($description !== '')
		? $description
		: 'Created automatically from a Camping Together listing.';

	// Closed, invite-only -- membership controls who can JOIN without
	// being invited, separate from who can VIEW the group once created
	// (that's access_id, set correctly further below after the first
	// save). Identical to Trip Rooms' own real, audited logic.
	$group->membership = ACCESS_PRIVATE;

	// access_id is set to ACCESS_PUBLIC here temporarily -- matches what
	// Elgg's own real group-create action does for every new group --
	// and gets corrected to the group's real member-only ACL a few
	// lines down, immediately after the first save (before this request
	// ends, so no window where the wrong access level is actually live
	// to anyone).
	$group->access_id = ACCESS_PUBLIC;

	$group->campingbuddy_group_type = 'trip_room';

	// Extra flag beyond plain Trip Rooms (spec Section 4) -- lets the
	// room recognize, for display purposes only, that it originated
	// from a Camping Together listing. Does not change how Trip Rooms
	// itself treats this group in any way.
	$group->campingbuddy_trip_type = 'camping_together';

	$group->campingbuddy_general_destination = ($region !== '')
		? trim($region . ' ' . $subregion)
		: '';
	$group->campingbuddy_exact_destination = ''; // filled in later, inside the room itself
	$group->campingbuddy_arrival_date = $target_start;
	$group->campingbuddy_departure_date = $target_end;
	// UPDATED 2026-08-22 (maintenance pass, Item 3): now passes both ends
	// of the participants range through to Trip Rooms' two fields
	// (campingbuddy_min_participants / campingbuddy_max_participants)
	// instead of only the max -- this is the actual fix for the listing
	// showing a misleading flat "Maximum participants: 5" on the Trip
	// Room's own profile page when the real listing was posted as a
	// "3-5 people" range.
	$group->campingbuddy_min_participants = ($min_participants !== null) ? $min_participants : 0;
	$group->campingbuddy_max_participants = ($max_participants !== null) ? $max_participants : 0;
	$group->campingbuddy_camping_style = $camping_style;
	$group->campingbuddy_social_expectation = $social_expectation;
	$group->campingbuddy_pet_info = $pet_info;
	$group->campingbuddy_accessibility_notes = $accessibility_notes;

	$group_saved = $group->save();

	if (!$group_saved) {
		return elgg_error_response('Something went wrong creating the underlying trip space. Please try again.');
	}

	// REAL BUG FIX, originally found 2026-08-11 in campingbuddy_trip_rooms,
	// reproduced here since this action creates a group the same way:
	// setting access_id directly to the ACCESS_PRIVATE constant actually
	// means "visible only to this entity's OWNER," not "visible only to
	// group members." The correct mechanism is the group's own dynamic
	// "group_acl" access collection, which Elgg automatically keeps in
	// sync with real membership as people join/leave. This collection
	// does not exist until the group has been saved once, which is why
	// this happens after $group->save() above, not before.
	$acl = $group->getOwnedAccessCollection('group_acl');
	if ($acl instanceof \ElggAccessCollection) {
		$group->access_id = $acl->id;
	} else {
		// Should not normally happen given the save above just
		// succeeded, but fail toward the stricter, already-proven-safe
		// option rather than leaving the group public if it somehow does.
		$group->access_id = ACCESS_PRIVATE;
	}

	// setContentAccessMode() needs the group to already have a real GUID
	// (and therefore a real group ACL) -- must happen after the initial
	// save, not before.
	$group->setContentAccessMode(\ElggGroup::CONTENT_ACCESS_MODE_MEMBERS_ONLY);

	// Second save to persist the corrected access_id and content access mode.
	$group->save();

	// Explicitly ensure the creator is a member -- belt-and-suspenders,
	// same as Trip Rooms' own action.
	if (!$group->isMember($user)) {
		$group->join($user);
	}

	// Same tools Trip Rooms enables by default for every new room.
	$group->enableTool('trip_details');
	$group->enableTool('forum');

	// ============================================================
	// STEP 2: create the listing itself, referencing the room.
	// ============================================================

	$listing = new \CampingBuddy\CampingTogether\CampingTogetherListing();
	$listing->owner_guid = $user->guid;
	$listing->title = $title;

	// The listing is the browsable, public-facing piece of this plugin
	// (spec Section 2/9/10) -- always visible to every logged-in
	// member, by design, not a member choice. ACCESS_LOGGED_IN is a
	// confirmed-safe raw constant (campingbuddy_coding_conventions.md
	// Section 8), unlike ACCESS_FRIENDS, so it's safe to set directly
	// with no access-selector/resolver needed.
	$listing->access_id = ACCESS_LOGGED_IN;

	if ($region !== '') {
		$listing->setRegion($region);
	}
	if ($subregion !== '') {
		$listing->setSubregion($subregion);
	}
	if ($target_start > 0) {
		$listing->setTargetStart($target_start);
	}
	if ($target_end > 0) {
		$listing->setTargetEnd($target_end);
	}
	$listing->setDateFlexible($date_flexible);

	if ($camping_style !== '') {
		$listing->setCampingStyle($camping_style); // throws if not a recognized style
	}
	if ($social_expectation !== '') {
		$listing->setSocialExpectation($social_expectation); // throws if not a recognized value
	}
	if ($min_participants !== null) {
		$listing->setMinParticipants($min_participants); // throws if less than 1
	}
	if ($max_participants !== null) {
		$listing->setMaxParticipants($max_participants); // throws if less than 1
	}
	if ($pet_info !== '') {
		$listing->setPetInfo($pet_info);
	}
	if ($accessibility_notes !== '') {
		$listing->setAccessibilityNotes($accessibility_notes);
	}
	if ($description !== '') {
		$listing->setDescription($description);
	}
	if ($application_deadline > 0) {
		$listing->setApplicationDeadline($application_deadline);
	}

	$listing->setStatus($status); // throws if not a recognized status

	if ($pref_pets !== '') {
		$listing->setPrefPets($pref_pets);
	}
	if ($pref_children !== '') {
		$listing->setPrefChildren($pref_children);
	}
	if ($pref_smoking !== '') {
		$listing->setPrefSmoking($pref_smoking);
	}
	if ($pref_alcohol !== '') {
		$listing->setPrefAlcohol($pref_alcohol);
	}
	if ($pref_noise !== '') {
		$listing->setPrefNoise($pref_noise);
	}
	if ($pref_experience !== '') {
		$listing->setPrefExperience($pref_experience);
	}
	if ($pref_atmosphere !== '') {
		$listing->setPrefAtmosphere($pref_atmosphere);
	}
	if ($additional_comments !== '') {
		$listing->setAdditionalComments($additional_comments); // throws if over 250 characters
	}

	$listing->setTripRoomGuid((int) $group->guid);

	$listing_saved = $listing->save();

	if (!$listing_saved || (int) $listing->guid <= 0) {
		return elgg_error_response('The listing could not be saved. Please try again.');
	}

	// ============================================================
	// STEP 3: reverse-link the room back to the listing, so a future
	// "view the originating listing" link from inside the room is
	// possible. Not itself required by the spec's field list, but a
	// small, harmless addition -- flagged here explicitly for the
	// record rather than added silently.
	// ============================================================

	$group->campingbuddy_ct_listing_guid = (int) $listing->guid;
	$group->save();

	return elgg_ok_response($listing->getURL(), 'Your Camping Together listing has been created.');

} catch (\InvalidArgumentException $e) {
	// Thrown by the entity setters above for any invalid/untrustworthy
	// value -- safe to show directly, these messages never echo back
	// raw user input.
	return elgg_error_response($e->getMessage());
} catch (\Throwable $e) {
	error_log('CampingBuddy Camping Together: listing save action failed - ' . $e->getMessage());
	return elgg_error_response('Something went wrong while saving. Please try again.');
}
