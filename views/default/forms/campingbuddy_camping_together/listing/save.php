<?php

/**
 * views/default/forms/campingbuddy_camping_together/listing/save.php
 *
 * The add-listing form, per campingbuddy_camping_together_spec.md
 * Section 3 (fields), Section 7 (the two starting paths), and Section 8
 * (the required behaviour-rules acknowledgment). Create-only in this
 * step -- editing an existing listing is a later build step (spec
 * Section 14, item 7), same "create first, edit later" precedent
 * already used for Logbook and Trip Rooms.
 *
 * Field naming/type choices below deliberately match conventions already
 * proven working elsewhere in this project:
 *   - '#type' => 'longtext' for multi-line fields ('description',
 *     'additional_comments') -- NOT '#type' => 'textarea', which is not
 *     a real Elgg field type and silently renders nothing (see
 *     campingbuddy_coding_conventions.md's Trip Planner lessons).
 *   - '#type' => 'date' with 'timestamp' => true for date fields, same
 *     as every other plugin's date fields.
 *   - Checkboxes are only present in submitted form data when checked --
 *     the save action must check for absence, not assume a false value
 *     will be submitted.
 * FLAGGED FOR VERIFICATION: '#type' => 'radio' (used below for the
 * participants preset picker) has not been used anywhere else in this
 * project yet -- every prior select-one-of-a-few-options field in
 * CampingBuddy has used '#type' => 'select' instead. It's a standard
 * Elgg core field type in principle, but per this project's standing
 * "verify before depending on it" rule (campingbuddy_coding_conventions.md
 * Section 0), this is genuinely new territory here and needs a real
 * live check that it renders correctly (actual radio buttons, not
 * blank/broken output) before being trusted the way 'select' and
 * 'checkbox' already are. If it doesn't render correctly, the safe
 * fallback is swapping this one field back to '#type' => 'select' with
 * the same six options_values -- everything else about how this field
 * works (the preset-to-min/max mapping in the save action) stays
 * identical either way.
 */

// ---- starting path (spec Section 7) ----
// Only these two statuses are choosable at creation. The other four
// (full/closed/completed/cancelled) are lifecycle states reached later
// through the not-yet-built manage view (spec Section 14, item 7), never
// selectable when first creating a listing.
echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'How firm is this trip?',
	'#help' => 'Gauging Interest: a loose "is anyone interested?" post -- low commitment, starts a private chat with anyone who asks to join. Open: a firm trip other members can browse and request to join right away. You can promote a Gauging Interest listing to Open later once there\'s real interest.',
	'name' => 'status',
	'required' => true,
	'value' => 'gauging_interest',
	'options_values' => [
		'gauging_interest' => 'Gauging Interest -- just testing the waters',
		'open' => 'Open -- this is a firm trip',
	],
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Title',
	'#help' => 'e.g. "Quiet long weekend near Campbell River"',
	'name' => 'title',
	'required' => true,
]);

// ---- region / subregion (shared selector, spec Section 3) ----
echo elgg_view('input/campingbuddy_region_selector', []);

echo elgg_view_field([
	'#type' => 'date',
	'#label' => 'Target start date (optional)',
	'name' => 'target_start',
	'timestamp' => true,
]);

echo elgg_view_field([
	'#type' => 'date',
	'#label' => 'Target end date (optional)',
	'name' => 'target_end',
	'timestamp' => true,
]);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => 'Dates are flexible',
	'name' => 'date_flexible',
	'value' => 1,
]);

echo elgg_view_field([
	'#type' => 'select',
	'#label' => 'Camping style (optional)',
	'name' => 'camping_style',
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
	'options_values' => [
		'' => '-- Select (optional) --',
		'social' => 'Social -- shared meals/activities expected',
		'independent' => 'Independent -- own schedule, no obligation to socialize',
		'flexible' => 'Flexible -- mix of both',
	],
]);

// ---- participants (spec Section 3, revised 2026-08-21 per user feedback) ----
// Presets map to a min/max pair under the hood (see
// CampingTogetherListing::setMinParticipants()/setMaxParticipants()) --
// this radio group is just a friendlier way to fill those two numbers
// in. "Other" uses the plain number field below it instead of a preset;
// that field is always visible (no JS show/hide, same no-JS approach
// as the region selector) with a help note clarifying it's only used
// when "Other" is selected.
echo elgg_view_field([
	'#type' => 'radio',
	'#label' => 'How many people (including you)? (optional)',
	'name' => 'participants_preset',
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
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Pet information (optional)',
	'name' => 'pet_info',
]);

echo elgg_view_field([
	'#type' => 'text',
	'#label' => 'Accessibility notes (optional)',
	'name' => 'accessibility_notes',
]);

echo elgg_view_field([
	'#type' => 'longtext',
	'#label' => 'Description (optional)',
	'#help' => 'General description -- please don\'t include exact coordinates or gate codes here. That level of detail belongs inside the private trip space once someone\'s approved.',
	'name' => 'description',
]);

echo elgg_view_field([
	'#type' => 'date',
	'#label' => 'Application deadline (optional)',
	'name' => 'application_deadline',
	'timestamp' => true,
]);

// ---- compatibility preferences (spec Sections 3 & 6) ----
// Every field here is optional. Leaving one blank means "no preference,"
// not "not allowed" -- nothing about a mismatch here blocks a request.
echo '<h3>Compatibility preferences (all optional)</h3>';
echo '<p>These help a requester know what to expect -- they never block anyone from requesting.</p>';

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
	'#help' => 'A short note -- not the place for a long explanation. Please keep this on-topic and factual.',
	'name' => 'additional_comments',
	'maxlength' => 250,
]);

// ---- behaviour-rules acknowledgment (spec Section 8) ----
echo '<h3>Before you post</h3>';
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
// -- reads naturally as part of what's being agreed to, rather than
// sitting disconnected at the top of the block. text-decoration:underline
// is set inline (not left to the theme's default hover-only underline)
// specifically so this reads as clickable at a glance -- a small,
// self-contained fix scoped to just this one link, not a site-wide
// styling change (that broader question belongs to the separate
// ct_theme_custom project, not this plugin).
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
	'value' => 'Create listing',
]);
elgg_set_form_footer($submit);
