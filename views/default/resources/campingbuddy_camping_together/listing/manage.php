<?php

/**
 * views/default/resources/campingbuddy_camping_together/listing/manage.php
 * yoursite.com/camping-together/manage/{guid}
 *
 * The organizer's request-review page -- per
 * campingbuddy_camping_together_spec.md Section 9/10. Only the
 * listing's actual owner (or an admin) may view this page at all --
 * checked here, independently, BEFORE the same ownership check is
 * relied on again inside decide.php's action. Both checks matter: this
 * one gates the page itself, decide.php's own check gates the action
 * that actually changes anything (never trust that a page-level check
 * alone is enough, same discipline used everywhere else in this
 * project).
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
	// FIXED 2026-08-25 (real bug found via live non-organizer test):
	// \Elgg\Exceptions\Http\Gatekeeper\GatekeeperException does not exist
	// on this Elgg version -- that class name was invented, not verified,
	// and threw a raw PHP fatal error instead of a clean permission
	// denial. \Elgg\Exceptions\Http\ForbiddenException is the correct,
	// already-confirmed-real class -- this exact same class was already
	// established and proven working for this exact scenario (a custom
	// non-admin permission check, not plain elgg_admin_gatekeeper())
	// during campingbuddy_ask_local's own forward() bug fix, 2026-08-04.
	// Should have been reused from the start rather than guessed anew.
	throw new \Elgg\Exceptions\Http\ForbiddenException('You do not have permission to view this page.');
}

// Same access override used in decide.php, for the same reason -- these
// requests are ACCESS_PRIVATE to their own authors, so the organizer
// cannot see them through Elgg's normal rules at all. Scoped narrowly:
// only this one query runs inside the override, and it only runs after
// the ownership check above has already passed. Filtered by the
// listing_guid metadata field, not container_guid -- see
// CampingTogetherRequest's class header for why requests no longer use
// container_guid to track their listing.
$requests = elgg_call(ELGG_IGNORE_ACCESS, function () use ($listing) {
	return elgg_get_entities([
		'type' => 'object',
		'subtype' => 'campingbuddy_ct_request',
		'metadata_name_value_pairs' => [
			['name' => 'listing_guid', 'value' => $listing->guid],
		],
		'limit' => 0,
	]);
});

$pref_labels = [
	'pref_pets' => [
		'label' => 'Pets',
		'fine_with_pets' => 'Fine with pets', 'prefer_no_pets' => 'Prefer no pets', 'have_own_pet' => 'Has own pet(s)',
	],
	'pref_children' => [
		'label' => 'Children',
		'fine_with_kids' => 'Fine with kids around', 'prefer_adult_only' => 'Prefer adult-only', 'have_kids' => 'Has kids along',
	],
	'pref_smoking' => [
		'label' => 'Smoking',
		'smoke_free_preferred' => 'Smoke-free preferred', 'smoking_fine' => 'Smoking fine', 'i_smoke' => 'Smokes',
	],
	'pref_alcohol' => [
		'label' => 'Alcohol',
		'fine_either_way' => 'Fine either way', 'prefer_alcohol_free' => 'Prefer alcohol-free', 'i_drink' => 'Drinks',
	],
	'pref_noise' => [
		'label' => 'Noise / quiet hours',
		'quiet_after_dark' => 'Quiet after dark', 'some_evening_noise_fine' => 'Some evening noise fine', 'generator_use_expected' => 'Generator use expected',
	],
	'pref_experience' => [
		'label' => 'Experience level',
		'new_to_camping' => 'New to camping', 'some_experience' => 'Some experience', 'very_experienced' => 'Very experienced',
	],
	'pref_atmosphere' => [
		'label' => 'Atmosphere',
		'quiet_mellow' => 'Quiet / mellow', 'easygoing' => 'Easygoing', 'social_lively' => 'Social / lively',
	],
];

$status_labels = [
	'requested' => 'Pending',
	'approved' => 'Approved',
	'declined' => 'Declined',
	'waitlisted' => 'Waitlisted',
	'withdrawn' => 'Withdrawn',
];

/**
 * Renders one preference block (used for both the listing's own stated
 * preferences and each request's) so the two can be visually compared.
 * A closure, not a named global function -- a named function declared
 * inside a view file risks a fatal "cannot redeclare" error if Elgg
 * ever renders this view more than once in the same request; a closure
 * assigned to a local variable has no such risk. Type-hinted against
 * \ElggObject rather than the HasCompatibilityPreferences trait --
 * PHP does not allow type-hinting a parameter against a trait, only
 * against a class or interface; both CampingTogetherListing and
 * CampingTogetherRequest extend \ElggObject, so that's the accurate,
 * valid type-hint for both.
 */
$render_prefs = function (\ElggObject $entity) use ($pref_labels): string {
	$getters = [
		'pref_pets' => 'getPrefPets', 'pref_children' => 'getPrefChildren', 'pref_smoking' => 'getPrefSmoking',
		'pref_alcohol' => 'getPrefAlcohol', 'pref_noise' => 'getPrefNoise', 'pref_experience' => 'getPrefExperience',
		'pref_atmosphere' => 'getPrefAtmosphere',
	];
	$lines = [];
	foreach ($getters as $key => $getter) {
		$value = $entity->{$getter}();
		if ($value === null) {
			continue;
		}
		$label = $pref_labels[$key]['label'];
		$text = $pref_labels[$key][$value] ?? ucfirst(str_replace('_', ' ', $value));
		$lines[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>';
	}
	if (empty($lines)) {
		return '<p><em>No preferences stated.</em></p>';
	}
	return '<ul>' . implode('', $lines) . '</ul>';
};

$back_link = elgg_view('output/url', [
	'href' => $listing->getURL(),
	'text' => '&larr; Back to ' . htmlspecialchars((string) $listing->title, ENT_QUOTES, 'UTF-8'),
	'is_trusted' => true,
]);

$content = $back_link;
$content .= '<h2>Manage requests</h2>';

$content .= '<h3>Your listing\'s stated preferences</h3>';
$content .= $render_prefs($listing);

// Small, organizer-facing count -- not a change to the listing's public
// display, just useful context while reviewing (flagged here since it's
// a small addition beyond the spec's literal field list, same as the
// Trip Room reverse-link added in Step 2).
$approved_count = 0;
foreach ($requests as $r) {
	if ($r instanceof \CampingBuddy\CampingTogether\CampingTogetherRequest && $r->getStatus() === 'approved') {
		$approved_count++;
	}
}
$content .= '<p><strong>Approved so far:</strong> ' . (int) $approved_count . '</p>';

if (empty($requests)) {
	$content .= '<p>No one has requested to join yet.</p>';
} else {
	foreach ($requests as $request) {
		if (!$request instanceof \CampingBuddy\CampingTogether\CampingTogetherRequest) {
			continue;
		}

		$requester = get_user((int) $request->owner_guid);
		$requester_name = $requester instanceof \ElggUser ? $requester->getDisplayName() : 'Unknown member';
		$requester_link = $requester instanceof \ElggUser
			? elgg_view('output/url', ['href' => $requester->getURL(), 'text' => $requester_name, 'style' => 'text-decoration: underline;'])
			: htmlspecialchars($requester_name, ENT_QUOTES, 'UTF-8');

		$status = $request->getStatus();
		$status_label = $status_labels[$status] ?? ucfirst($status);

		$content .= '<div style="border:1px solid #ccc; padding:1rem; margin-bottom:1rem;">';
		$content .= '<h4>' . $requester_link . ' -- <em>' . htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') . '</em></h4>';

		$message = $request->getMessage();
		if ($message !== null) {
			$content .= '<p><strong>Message:</strong></p>';
			$content .= elgg_view('output/longtext', ['value' => $message]);
		}

		$camping_setup = $request->getCampingSetup();
		if ($camping_setup !== null) {
			$content .= '<p><strong>Their camping setup:</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $camping_setup)), ENT_QUOTES, 'UTF-8') . '</p>';
		}

		$content .= '<p><strong>Their preferences:</strong></p>';
		$content .= $render_prefs($request);

		$additional_comments = $request->getAdditionalComments();
		if ($additional_comments !== null) {
			$content .= '<p><strong>Additional comments:</strong></p>';
			$content .= elgg_view('output/longtext', ['value' => $additional_comments]);
		}

		$organizer_note = $request->getOrganizerNote();
		if ($organizer_note !== null) {
			$content .= '<p><strong>Your note:</strong> ' . htmlspecialchars($organizer_note, ENT_QUOTES, 'UTF-8') . '</p>';
		}

		// Only pending or waitlisted requests get the decide form -- an
		// already-approved/declined request, or one the requester
		// themselves withdrew, is treated as settled for this first
		// version rather than endlessly re-editable. Revisit if that
		// turns out to be too limiting in practice.
		if (in_array($status, ['requested', 'waitlisted'], true)) {
			$content .= elgg_view_form('campingbuddy_camping_together/request/decide', [], [
				'request_guid' => (int) $request->guid,
			]);
		}

		$content .= '</div>';
	}
}

$body = elgg_view_layout('content', [
	'title' => 'Manage requests',
	'content' => $content,
]);

echo elgg_view_page('Manage requests: ' . $listing->title, $body);
