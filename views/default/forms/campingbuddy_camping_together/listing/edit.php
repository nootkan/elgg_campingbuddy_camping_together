<?php

/**
 * views/default/forms/campingbuddy_camping_together/listing/edit.php
 *
 * The edit-listing form -- covers everything the create form does
 * (spec Section 3 fields), pre-filled from the existing entity, plus
 * ALL SIX status values rather than just the two available at creation.
 *
 * DESIGN DECISION (2026-08-25, direct user request): this replaces the
 * originally-planned separate "Promote to Open" action entirely. The
 * user's own reasoning: trip plans genuinely change over weeks of
 * discussion among campers, and a listing frozen forever after creation
 * doesn't match that reality -- so rather than build a narrow
 * promote-only action and then rebuild it again shortly after for
 * general editing, this is the real, general Edit Listing feature (spec
 * Section 14, item 6) built now, with status as just one more editable
 * field among the others. Gauging Interest -> Open is therefore not a
 * special action anymore -- it's the same edit form as changing the
 * description or dates.
 *
 * Expects 'listing' (a real CampingTogetherListing entity) in $vars,
 * supplied by the edit resource page after its own ownership check.
 *
 * Deliberately does NOT re-show the behaviour-rules guidelines or the
 * acknowledgment checkbox -- those were already agreed to once, at
 * creation (spec Section 8 only requires it "before a listing can be
 * created, and again before a request can be submitted" -- editing an
 * existing listing isn't either of those).
 */

$listing = elgg_extract('listing', $vars);

if (!$listing instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	return;
}

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'guid',
	'value' => (int) $listing->guid,
]);

// ---- status -- all six values, unlike the two-option create form ----
echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Status',
	'#help' => 'Gauging Interest and Open both still accept requests. Full/Closed/Completed/Cancelled all stop accepting new requests.',
	'name' => 'status',
	'required' => true,
	'value' => $listing->getStatus(),
	'options_values' => [
		'gauging_interest' => 'Gauging Interest -- still testing the waters',
		'open' => 'Open -- a firm trip',
		'full' => 'Full -- enough people, not accepting more requests',
		'closed' => 'Closed -- not accepting requests, trip may still happen',
		'completed' => 'Completed -- the trip already happened',
		'cancelled' => 'Cancelled',
	],
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Title',
	'name' => 'title',
	'required' => true,
	'value' => (string) $listing->title,
]);

// ---- region / subregion (shared selector, spec Section 3) ----
// The shared region selector doesn't currently accept a pre-filled
// value (it's always been used on a blank create form up to now) --
// flagged here as a real, known limitation rather than silently
// leaving it unfilled: the organizer will need to re-select their
// region/subregion here even though it's already saved on the
// listing. Worth revisiting if this proves annoying in practice.
echo elgg_view('input/campingbuddy_region_selector', []);
echo '<p><em>Current region on file: ' . htmlspecialchars((string) ($listing->getRegion() ?? '(none)'), ENT_QUOTES, 'UTF-8')
	. (($listing->getSubregion() !== null) ? ' -- ' . htmlspecialchars((string) $listing->getSubregion(), ENT_QUOTES, 'UTF-8') : '')
	. ' -- re-select above only if it needs to change.</em></p>';

echo elgg_view_field([
	'#type' => 'date',
	'#label' => 'Target start date (optional)',
	'name' => 'target_start',
	'timestamp' => true,
	'value' => $listing->getTargetStart(),
]);

echo elgg_view_field([
	'#type' => 'date',
	'#label' => 'Target end date (optional)',
	'name' => 'target_end',
	'timestamp' => true,
	'value' => $listing->getTargetEnd(),
]);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => 'Dates are flexible',
	'name' => 'date_flexible',
	'value' => 1,
	'checked' => $listing->isDateFlexible(),
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Camping style (optional)',
	'name' => 'camping_style',
	'value' => (string) ($listing->getCampingStyle() ?? ''),
	'options_values' => [
		'' => '-- Select (optional) --',
		'tent' => 'Tent',
		'car_camping' => 'Car camping',
		'pickup_camper' => 'Pickup camper',
		'camper_van' => 'Camper van',
		'travel_trailer' => 'Travel trailer',
		'fifth_wheel' => 'Fifth wheel',
		'motorhome' => 'Motorhome',
		'mixed' => 'Mixed / varies by member',
		'other' => 'Other',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Social expectation (optional)',
	'#help' => 'Sets expectations up front for anyone who requests to join. This is the "no obligation to socialize" promise -- please be honest here.',
	'name' => 'social_expectation',
	'value' => (string) ($listing->getSocialExpectation() ?? ''),
	'options_values' => [
		'' => '-- Select (optional) --',
		'social' => 'Social -- shared meals/activities expected',
		'independent' => 'Independent -- own schedule, no obligation to socialize',
		'flexible' => 'Flexible -- mix of both',
	],
]);

// ---- participants preset, reconstructed from the existing min/max ----
// The stored values are always plain min/max numbers (see
// CampingTogetherListing's own field comments) -- this figures out
// which preset (if any) they match, so the right radio button shows as
// already selected rather than the organizer having to re-guess it.
$current_min = $listing->getMinParticipants();
$current_max = $listing->getMaxParticipants();

$participants_presets = [
	'2' => [2, 2],
	'3_5' => [3, 5],
	'6_8' => [6, 8],
	'9_11' => [9, 11],
	'12_plus' => [12, null],
];

$selected_preset = '';
$custom_value = '';

if ($current_min !== null) {
	foreach ($participants_presets as $key => $pair) {
		if ($pair[0] === $current_min && $pair[1] === $current_max) {
			$selected_preset = $key;
			break;
		}
	}
	if ($selected_preset === '' && $current_min === $current_max) {
		// Doesn't match a known preset pair, but min==max means it was
		// originally entered as a single custom number via "Other" --
		// same shape the create form always produces, so this covers
		// every listing actually created through this plugin.
		$selected_preset = 'other';
		$custom_value = (string) $current_min;
	}
}

echo elgg_view_field([
	'#type' => 'radio',
	'#label' => 'How many people (including you)? (optional)',
	'name' => 'participants_preset',
	'value' => $selected_preset,
	'options_values' => [
		'2' => '2 people',
		'3_5' => '3-5 people',
		'6_8' => '6-8 people',
		'9_11' => '9-11 people',
		'12_plus' => '12+ people',
		'other' => 'Other (enter a number below)',
	],
]);

echo elgg_view_field([
	'#type' => 'number',
	'#label' => 'Custom number (only used if "Other" is selected above)',
	'name' => 'participants_custom',
	'min' => 1,
	'value' => $custom_value,
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Pet information (optional)',
	'name' => 'pet_info',
	'value' => (string) ($listing->getPetInfo() ?? ''),
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Accessibility notes (optional)',
	'name' => 'accessibility_notes',
	'value' => (string) ($listing->getAccessibilityNotes() ?? ''),
]);

echo elgg_view_field([
	'#type' => 'longtext',
	'#label' => 'Description (optional)',
	'#help' => 'General description -- please don\'t include exact coordinates or gate codes here. That level of detail belongs inside the private trip space once someone\'s approved.',
	'name' => 'description',
	'value' => (string) ($listing->getDescription() ?? ''),
]);

echo elgg_view_field([
	'#type' => 'date',
	'#label' => 'Application deadline (optional)',
	'name' => 'application_deadline',
	'timestamp' => true,
	'value' => $listing->getApplicationDeadline(),
]);

// ---- compatibility preferences (spec Sections 3 & 6) ----
echo '<h3>Compatibility preferences (all optional)</h3>';
echo '<p>These help a requester know what to expect -- they never block anyone from requesting.</p>';

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Pets',
	'name' => 'pref_pets',
	'value' => (string) ($listing->getPrefPets() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'fine_with_pets' => 'Fine with pets',
		'prefer_no_pets' => 'Prefer no pets',
		'have_own_pet' => 'I\'ll have my own pet(s)',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Children',
	'name' => 'pref_children',
	'value' => (string) ($listing->getPrefChildren() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'fine_with_kids' => 'Fine with kids around',
		'prefer_adult_only' => 'Prefer adult-only',
		'have_kids' => 'I\'ll have kids with me',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Smoking',
	'name' => 'pref_smoking',
	'value' => (string) ($listing->getPrefSmoking() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'smoke_free_preferred' => 'Smoke-free preferred',
		'smoking_fine' => 'Smoking fine',
		'i_smoke' => 'I smoke',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Alcohol',
	'name' => 'pref_alcohol',
	'value' => (string) ($listing->getPrefAlcohol() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'fine_either_way' => 'Fine either way',
		'prefer_alcohol_free' => 'Prefer alcohol-free',
		'i_drink' => 'I drink',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Noise / quiet hours',
	'name' => 'pref_noise',
	'value' => (string) ($listing->getPrefNoise() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'quiet_after_dark' => 'Quiet after dark',
		'some_evening_noise_fine' => 'Some evening noise fine',
		'generator_use_expected' => 'Generator use expected',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Experience level',
	'name' => 'pref_experience',
	'value' => (string) ($listing->getPrefExperience() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'new_to_camping' => 'New to camping',
		'some_experience' => 'Some experience',
		'very_experienced' => 'Very experienced',
	],
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Atmosphere',
	'name' => 'pref_atmosphere',
	'value' => (string) ($listing->getPrefAtmosphere() ?? ''),
	'options_values' => [
		'' => '-- No preference --',
		'quiet_mellow' => 'Quiet / mellow',
		'easygoing' => 'Easygoing',
		'social_lively' => 'Social / lively',
	],
]);

echo elgg_view_field([
	'#type' => 'longtext',
	'#label' => 'Additional comments (optional, 250 characters max)',
	'#help' => 'A short note -- not the place for a long explanation. Please keep this on-topic and factual.',
	'name' => 'additional_comments',
	'maxlength' => 250,
	'value' => (string) ($listing->getAdditionalComments() ?? ''),
]);

$submit = elgg_view_field([
	'#type' => 'submit',
	'#class' => 'elgg-foot',
	'value' => 'Save changes',
]);
elgg_set_form_footer($submit);
