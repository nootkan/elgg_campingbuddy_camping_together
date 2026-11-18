<?php

/**
 * views/default/forms/campingbuddy_camping_together/request/save.php
 *
 * The "Request to join" form, per campingbuddy_camping_together_spec.md
 * Section 5 (fields) and Section 8 (the behaviour-rules acknowledgment,
 * required again here -- the spec is explicit this applies "before a
 * listing can be created, and again before a request can be
 * submitted").
 *
 * Expects 'listing_guid' in $vars, supplied by whatever page renders
 * this form (the listing view page).
 */

$listing_guid = (int) elgg_extract('listing_guid', $vars, 0);

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'listing_guid',
	'value' => $listing_guid,
]);

echo elgg_view_field([
	'#type' => 'longtext',
	'#label' => 'Message to the organizer',
	'#help' => 'Why this trip interests you, or anything you\'d like them to know.',
	'name' => 'message',
	'required' => true,
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Your camping setup (optional)',
	'#help' => 'This is about your own setup -- it may differ from the listing\'s general camping style.',
	'name' => 'camping_setup',
	'options_values' => [
		'' => '-- Select (optional) --',
		'tent' => 'Tent',
		'car_camping' => 'Car camping',
		'pickup_camper' => 'Pickup camper',
		'camper_van' => 'Camper van',
		'travel_trailer' => 'Travel trailer',
		'fifth_wheel' => 'Fifth wheel',
		'motorhome' => 'Motorhome',
		'hammock' => 'Hammock',
		'backpacking' => 'Backpacking',
		'boat_access' => 'Boat access',
		'other' => 'Other',
	],
]);

// ---- compatibility preferences (spec Sections 5 & 6) ----
// Same option sets as the listing's own preference fields -- shown side
// by side with the listing's stated preferences on the organizer's
// manage view. Every field here is optional and never blocks a
// request -- a blank value means "no preference," not "not allowed."
echo '<h3>Your compatibility preferences (all optional)</h3>';
echo '<p>These let the organizer know what to expect from you -- they never block your request.</p>';

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Pets',
	'name' => 'pref_pets',
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
	'#help' => 'A short note -- please keep this on-topic and factual.',
	'name' => 'additional_comments',
	'maxlength' => 250,
]);

// ---- behaviour-rules acknowledgment (spec Section 8) ----
echo '<h3>Before you request to join</h3>';
echo '<div style="margin-bottom:1rem;">';
echo '<ul>';
echo '<li>Respect personal boundaries -- no member is required to socialize.</li>';
echo '<li>Do not enter another member\'s campsite without invitation.</li>';
echo '<li>Do not publish this trip\'s location outside the private trip space.</li>';
echo '<li>Do not invite anyone who has not been approved through this listing.</li>';
echo '<li>Follow campground and land-use rules.</li>';
echo '<li>Harassment and territorial gatekeeping are not permitted.</li>';
echo '<li>The organizer is not an emergency service or professional guide.</li>';
echo '</ul>';
echo '</div>';

// Moved here, directly above the checkbox, per user feedback (2026-08-23)
// -- see the listing form's own save.php for the full reasoning, applied
// identically here.
echo '<p>By checking the box below, you also agree to our <a href="https://campingbuddy.ca/terms" target="_blank" rel="noopener noreferrer" style="text-decoration: underline;">Terms &amp; Conditions</a>.</p>';

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => 'I have read and agree to follow these guidelines',
	'name' => 'agree_to_rules',
	'value' => 1,
	'required' => true,
]);

$submit = elgg_view_field([
	'#type' => 'submit',
	'#class' => 'elgg-foot',
	'value' => 'Send request',
]);
elgg_set_form_footer($submit);
