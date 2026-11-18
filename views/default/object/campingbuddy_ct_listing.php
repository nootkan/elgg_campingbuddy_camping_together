<?php

/**
 * views/default/object/campingbuddy_ct_listing.php
 *
 * Renders a CampingTogetherListing two ways, controlled by 'full_view':
 *   - full_view=false: a summary card, used on the /camping-together
 *     browse list
 *   - full_view=true: the full single-listing page content
 *
 * Deliberately does NOT call elgg_view_title() in the full-view branch
 * -- elgg_view_page() already renders the page's own heading from the
 * title passed to it, same fix already applied to Place, LogEntry, and
 * TripPlan's object views after it was first found as a real bug (see
 * campingbuddy_build_log.md, 2026-08-02).
 *
 * The short-view heading is built as a raw '<h3><a>...</a></h3>' rather
 * than passed through elgg_view_title(), for the same reason documented
 * in that same build-log entry: elgg_view_title() strips HTML tags from
 * its input, silently discarding a pre-built link.
 */

$entity = elgg_extract('entity', $vars);
$full_view = (bool) elgg_extract('full_view', $vars, false);

if (!$entity instanceof \CampingBuddy\CampingTogether\CampingTogetherListing) {
	return;
}

$status_labels = [
	'gauging_interest' => 'Gauging Interest',
	'open' => 'Open',
	'full' => 'Full',
	'closed' => 'Closed',
	'completed' => 'Completed',
	'cancelled' => 'Cancelled',
];

$camping_style_labels = [
	'tent' => 'Tent',
	'car_camping' => 'Car camping',
	'pickup_camper' => 'Pickup camper',
	'camper_van' => 'Camper van',
	'travel_trailer' => 'Travel trailer',
	'fifth_wheel' => 'Fifth wheel',
	'motorhome' => 'Motorhome',
	'mixed' => 'Mixed / varies by member',
	'other' => 'Other',
];

$social_expectation_labels = [
	'social' => 'Social -- shared meals/activities expected',
	'independent' => 'Independent -- own schedule, no obligation to socialize',
	'flexible' => 'Flexible -- mix of both',
];

$pref_labels = [
	'pref_pets' => [
		'fine_with_pets' => 'Fine with pets',
		'prefer_no_pets' => 'Prefer no pets',
		'have_own_pet' => 'Will have own pet(s)',
	],
	'pref_children' => [
		'fine_with_kids' => 'Fine with kids around',
		'prefer_adult_only' => 'Prefer adult-only',
		'have_kids' => 'Will have kids along',
	],
	'pref_smoking' => [
		'smoke_free_preferred' => 'Smoke-free preferred',
		'smoking_fine' => 'Smoking fine',
		'i_smoke' => 'Organizer smokes',
	],
	'pref_alcohol' => [
		'fine_either_way' => 'Fine either way',
		'prefer_alcohol_free' => 'Prefer alcohol-free',
		'i_drink' => 'Organizer drinks',
	],
	'pref_noise' => [
		'quiet_after_dark' => 'Quiet after dark',
		'some_evening_noise_fine' => 'Some evening noise fine',
		'generator_use_expected' => 'Generator use expected',
	],
	'pref_experience' => [
		'new_to_camping' => 'New to camping',
		'some_experience' => 'Some experience',
		'very_experienced' => 'Very experienced',
	],
	'pref_atmosphere' => [
		'quiet_mellow' => 'Quiet / mellow',
		'easygoing' => 'Easygoing',
		'social_lively' => 'Social / lively',
	],
];

$status = $entity->getStatus();
$status_label = $status_labels[$status] ?? ucfirst($status);

$style = $entity->getCampingStyle();
$style_label = $style !== null ? ($camping_style_labels[$style] ?? ucfirst($style)) : null;

$social = $entity->getSocialExpectation();
$social_label = $social !== null ? ($social_expectation_labels[$social] ?? ucfirst($social)) : null;

$title_escaped = htmlspecialchars((string) $entity->title, ENT_QUOTES, 'UTF-8');
$url_escaped = htmlspecialchars((string) $entity->getURL(), ENT_QUOTES, 'UTF-8');

$region = $entity->getRegion();
$subregion = $entity->getSubregion();
$region_text = trim((string) $region . ($subregion ? ' -- ' . $subregion : ''));

$start = $entity->getTargetStart();
$end = $entity->getTargetEnd();
$flexible = $entity->isDateFlexible();

// spaces_remaining (spec Section 3) -- computed here, not stored, since
// it depends on the current count of approved requests. The
// campingbuddy_ct_request entity/save action doesn't exist yet (Step 3),
// so this always shows "no cap" or the raw max for now; it will reflect
// real approved counts once requests exist.
// Participants display -- min/max pair (changed 2026-08-21 from a single
// fixed number, see CampingTogetherListing::setMinParticipants()). Four
// cases: neither set (no preference stated), a single number
// (min == max), a bounded range (min < max), or open-ended (min set,
// max null -- the "12+" preset).
$min_participants = $entity->getMinParticipants();
$max_participants = $entity->getMaxParticipants();

$participants_text = null;
if ($min_participants !== null && $max_participants !== null) {
	$participants_text = ($min_participants === $max_participants)
		? $min_participants . ' people (including you)'
		: $min_participants . '-' . $max_participants . ' people (including you)';
} elseif ($min_participants !== null) {
	$participants_text = $min_participants . '+ people (including you)';
}

if (!$full_view) {
	// ---- Short/list view -- browse cards ----
	echo '<div class="campingbuddy-ct-listing-summary" style="margin-bottom:1.5rem;">';
	echo '<h3><a href="' . $url_escaped . '" style="text-decoration: underline;">' . $title_escaped . '</a></h3>';
	echo '<p><strong>Status:</strong> ' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</p>';

	if ($region_text !== '') {
		echo '<p><strong>Region:</strong> ' . htmlspecialchars($region_text, ENT_QUOTES, 'UTF-8') . '</p>';
	}

	if ($flexible) {
		echo '<p><strong>Dates:</strong> Flexible</p>';
	} elseif ($start || $end) {
		$start_str = $start ? date('F j, Y', $start) : '?';
		$end_str = $end ? date('F j, Y', $end) : '';
		echo '<p><strong>Dates:</strong> ' . htmlspecialchars($start_str, ENT_QUOTES, 'UTF-8')
			. ($end_str !== '' ? ' &ndash; ' . htmlspecialchars($end_str, ENT_QUOTES, 'UTF-8') : '')
			. '</p>';
	}

	if ($style_label !== null) {
		echo '<p><strong>Camping style:</strong> ' . htmlspecialchars($style_label, ENT_QUOTES, 'UTF-8') . '</p>';
	}

	if ($participants_text !== null) {
		echo '<p><strong>Group size:</strong> ' . htmlspecialchars($participants_text, ENT_QUOTES, 'UTF-8') . '</p>';
	}

	echo '</div>';
	return;
}

// ---- Full view -- single-listing page ----
echo '<div class="campingbuddy-ct-listing-full">';

echo '<p><strong>Status:</strong> ' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</p>';

if ($region_text !== '') {
	echo '<p><strong>Region:</strong> ' . htmlspecialchars($region_text, ENT_QUOTES, 'UTF-8') . '</p>';
}

if ($flexible) {
	echo '<p><strong>Dates:</strong> Flexible</p>';
} elseif ($start || $end) {
	$start_str = $start ? date('F j, Y', $start) : '?';
	$end_str = $end ? date('F j, Y', $end) : '';
	echo '<p><strong>Dates:</strong> ' . htmlspecialchars($start_str, ENT_QUOTES, 'UTF-8')
		. ($end_str !== '' ? ' &ndash; ' . htmlspecialchars($end_str, ENT_QUOTES, 'UTF-8') : '')
		. '</p>';
}

if ($style_label !== null) {
	echo '<p><strong>Camping style:</strong> ' . htmlspecialchars($style_label, ENT_QUOTES, 'UTF-8') . '</p>';
}

if ($social_label !== null) {
	echo '<p><strong>Social expectation:</strong> ' . htmlspecialchars($social_label, ENT_QUOTES, 'UTF-8') . '</p>';
}

if ($participants_text !== null) {
	echo '<p><strong>Group size:</strong> ' . htmlspecialchars($participants_text, ENT_QUOTES, 'UTF-8') . '</p>';
}

$pet_info = $entity->getPetInfo();
if ($pet_info !== null) {
	echo '<p><strong>Pet information:</strong> ' . nl2br(htmlspecialchars($pet_info, ENT_QUOTES, 'UTF-8')) . '</p>';
}

$accessibility_notes = $entity->getAccessibilityNotes();
if ($accessibility_notes !== null) {
	echo '<p><strong>Accessibility notes:</strong> ' . nl2br(htmlspecialchars($accessibility_notes, ENT_QUOTES, 'UTF-8')) . '</p>';
}

$description = $entity->getDescription();
if ($description !== null) {
	echo '<p><strong>Description:</strong></p>';
	echo elgg_view('output/longtext', ['value' => $description]);
}

$deadline = $entity->getApplicationDeadline();
if ($deadline !== null) {
	echo '<p><strong>Application deadline:</strong> ' . htmlspecialchars(date('F j, Y', $deadline), ENT_QUOTES, 'UTF-8') . '</p>';
}

// ---- compatibility preferences ----
$pref_fields = [
	'pref_pets' => ['label' => 'Pets', 'getter' => 'getPrefPets'],
	'pref_children' => ['label' => 'Children', 'getter' => 'getPrefChildren'],
	'pref_smoking' => ['label' => 'Smoking', 'getter' => 'getPrefSmoking'],
	'pref_alcohol' => ['label' => 'Alcohol', 'getter' => 'getPrefAlcohol'],
	'pref_noise' => ['label' => 'Noise / quiet hours', 'getter' => 'getPrefNoise'],
	'pref_experience' => ['label' => 'Experience level', 'getter' => 'getPrefExperience'],
	'pref_atmosphere' => ['label' => 'Atmosphere', 'getter' => 'getPrefAtmosphere'],
];

$has_any_pref = false;
foreach ($pref_fields as $key => $info) {
	$value = $entity->{$info['getter']}();
	if ($value !== null) {
		$has_any_pref = true;
		break;
	}
}

if ($has_any_pref) {
	echo '<h4>Compatibility preferences</h4><ul>';
	foreach ($pref_fields as $key => $info) {
		$value = $entity->{$info['getter']}();
		if ($value === null) {
			continue;
		}
		$label = $pref_labels[$key][$value] ?? ucfirst(str_replace('_', ' ', $value));
		echo '<li><strong>' . htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') . ':</strong> '
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</li>';
	}
	echo '</ul>';
}

$additional_comments = $entity->getAdditionalComments();
if ($additional_comments !== null) {
	echo '<p><strong>Additional comments:</strong></p>';
	echo elgg_view('output/longtext', ['value' => $additional_comments]);
}

echo '</div>';
