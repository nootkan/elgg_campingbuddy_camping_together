<?php

/**
 * actions/campingbuddy_camping_together/listing/edit.php
 *
 * Updates an existing campingbuddy_ct_listing AND syncs the matching
 * fields on its underlying Trip Room -- per the 2026-08-25 design
 * decision to build general editing (all six statuses, any time)
 * rather than a narrow "Promote to Open" action. See
 * campingbuddy_build_log.md for the full reasoning.
 *
 * Field collection/validation below deliberately mirrors
 * actions/campingbuddy_camping_together/listing/save.php as closely as
 * possible -- same input names, same participants-preset mapping, same
 * validation discipline -- specifically so the two files stay easy to
 * compare and keep in sync if either needs a future field added.
 *
 * TRIP ROOM SYNC: unlike the create action, this one does NOT create a
 * new group -- it loads the listing's EXISTING trip_room_guid and
 * updates that group's matching fields. The organizer already owns that
 * group (they created it via the create action), so this is a normal,
 * permitted write under Elgg's default rules -- no access override of
 * any kind needed here, unlike the request/decide.php action's
 * ELGG_IGNORE_ACCESS use.
 */

if (!elgg_is_logged_in()) {
	return elgg_error_response('You must be logged in to do that.');
}

$user = elgg_get_logged_in_user_entity();

$guid = (int) get_input('guid', 0);
$listing = get_entity($guid);

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	return elgg_error_response('That listing could not be found.');
}

$is_owner = ((int) $listing->owner_guid === (int) $user->guid);
$is_admin_with_oversight = \CampingBuddy\Core\AccessLevel::hasFullContentOversight($user);

if (!$is_owner && !$is_admin_with_oversight) {
	return elgg_error_response('You do not have permission to edit this listing.');
}

// ---- collect + lightly normalize input ----
// Same field set and same normalization as the create action.

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

// ---- required-field checks ----

if ($title === '') {
	return elgg_error_response('Please enter a title.');
}

// ALL SIX statuses are valid here, unlike the create action's two-value
// restriction -- this is the whole point of building general editing.
$valid_statuses = ['gauging_interest', 'open', 'full', 'closed', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses, true)) {
	return elgg_error_response('Please choose a valid status.');
}

// ---- participants preset -> min/max mapping ----
// Identical logic to the create action -- see that file's own comments
// for the full reasoning.
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
} elseif ($participants_preset !== '' && isset($participants_presets[$participants_preset])) {
	[$min_participants, $max_participants] = $participants_presets[$participants_preset];
} elseif ($participants_preset !== '') {
	return elgg_error_response('Invalid participants selection.');
}

try {
	// ============================================================
	// STEP 1: update the listing itself.
	// ============================================================

	$listing->title = $title;

	// Every field is reset to reflect exactly what was submitted --
	// including clearing it back to null if the organizer blanked out a
	// field that previously had a value. This matches normal "edit form"
	// expectations (an emptied field should actually clear, not silently
	// keep its old value) -- different from the create action, where an
	// empty submitted value simply means "never set this at all."
	$listing->setRegion($region !== '' ? $region : null);
	$listing->setSubregion($subregion !== '' ? $subregion : null);
	$listing->setTargetStart($target_start > 0 ? $target_start : null);
	$listing->setTargetEnd($target_end > 0 ? $target_end : null);
	$listing->setDateFlexible($date_flexible);
	$listing->setCampingStyle($camping_style !== '' ? $camping_style : null); // throws if not a recognized style
	$listing->setSocialExpectation($social_expectation !== '' ? $social_expectation : null); // throws if not a recognized value
	$listing->setMinParticipants($min_participants); // throws if less than 1
	$listing->setMaxParticipants($max_participants); // throws if less than 1
	$listing->setPetInfo($pet_info !== '' ? $pet_info : null);
	$listing->setAccessibilityNotes($accessibility_notes !== '' ? $accessibility_notes : null);
	$listing->setDescription($description !== '' ? $description : null);
	$listing->setApplicationDeadline($application_deadline > 0 ? $application_deadline : null);
	$listing->setStatus($status); // throws if not a recognized status
	$listing->setPrefPets($pref_pets !== '' ? $pref_pets : null);
	$listing->setPrefChildren($pref_children !== '' ? $pref_children : null);
	$listing->setPrefSmoking($pref_smoking !== '' ? $pref_smoking : null);
	$listing->setPrefAlcohol($pref_alcohol !== '' ? $pref_alcohol : null);
	$listing->setPrefNoise($pref_noise !== '' ? $pref_noise : null);
	$listing->setPrefExperience($pref_experience !== '' ? $pref_experience : null);
	$listing->setPrefAtmosphere($pref_atmosphere !== '' ? $pref_atmosphere : null);
	$listing->setAdditionalComments($additional_comments !== '' ? $additional_comments : null); // throws if over 250 characters

	$listing_saved = $listing->save();

	if (!$listing_saved) {
		return elgg_error_response('The listing could not be updated. Please try again.');
	}

	// ============================================================
	// STEP 2: sync the matching fields on the underlying Trip Room,
	// so it doesn't go stale relative to the now-updated listing.
	// The organizer already owns this group -- no access override
	// needed, unlike request/decide.php's use of ELGG_IGNORE_ACCESS.
	// ============================================================

	$trip_room_guid = $listing->getTripRoomGuid();
	$group = ($trip_room_guid !== null) ? get_entity($trip_room_guid) : null;

	if ($group instanceof \ElggGroup) {
		$group->name = $title;
		if ($description !== '') {
			$group->description = $description;
		}
		$group->campingbuddy_general_destination = ($region !== '')
			? trim($region . ' ' . $subregion)
			: '';
		$group->campingbuddy_arrival_date = $target_start;
		$group->campingbuddy_departure_date = $target_end;
		$group->campingbuddy_min_participants = ($min_participants !== null) ? $min_participants : 0;
		$group->campingbuddy_max_participants = ($max_participants !== null) ? $max_participants : 0;
		$group->campingbuddy_camping_style = $camping_style;
		$group->campingbuddy_social_expectation = $social_expectation;
		$group->campingbuddy_pet_info = $pet_info;
		$group->campingbuddy_accessibility_notes = $accessibility_notes;
		$group->save();
	} else {
		// Same "flag, don't block" approach as request/decide.php's own
		// equivalent mismatch case -- the listing itself already saved
		// successfully above, so this doesn't fail the whole action.
		error_log('CampingBuddy Camping Together: edited listing ' . $listing->guid . ' but could not sync its Trip Room (trip_room_guid missing or invalid).');
	}

	return elgg_ok_response($listing->getURL(), 'Your listing has been updated.');

} catch (\InvalidArgumentException $e) {
	return elgg_error_response($e->getMessage());
} catch (\Throwable $e) {
	error_log('CampingBuddy Camping Together: listing edit action failed - ' . $e->getMessage());
	return elgg_error_response('Something went wrong while saving. Please try again.');
}
