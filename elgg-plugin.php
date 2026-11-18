<?php

/**
 * campingbuddy_camping_together — static plugin configuration.
 *
 * STEP 1 (entities only) confirmed live 2026-08-21. STEP 2 (add-listing
 * vertical slice) confirmed live 2026-08-21. STEP 3+4 (request-to-join
 * flow + organizer manage/approve view) confirmed live and fully tested
 * 2026-08-25. THIS UPDATE: general Edit Listing (spec Section 14, item
 * 6, built to cover ALL SIX status values rather than a narrower
 * "Promote to Open" action -- direct user decision 2026-08-25, since a
 * listing frozen after creation didn't match how trip plans actually
 * evolve). See campingbuddy_build_log.md for full history.
 *
 * This file is read by Elgg to configure the plugin. It must only ever
 * return an array — no logic, no side effects, nothing that changes
 * between requests.
 */

return [

	'plugin' => [
		'name'                => 'CampingBuddy Camping Together',
		'version'             => '0.4.0',
		'activate_on_install' => false,
	],

	// Registers this plugin's entries on the shared member Tools hub
	// page (/tools) and the Moderator Hub -- see
	// classes/CampingBuddy/CampingTogether/Bootstrap.php. Added
	// 2026-08-28 after discovering this plugin had never been wired
	// into either hub at all since it was first built -- same real
	// oversight already hit once before by Trip Planner (2026-08-14).
	'events' => [
		'register' => [
			'campingbuddy:member_hub' => [
				'CampingBuddy\CampingTogether\Bootstrap::addToMemberHub' => [],
			],
			'campingbuddy:moderator_hub' => [
				'CampingBuddy\CampingTogether\Bootstrap::addToModeratorHub' => [],
			],
		],
	],

	// No 'access' key on any of these four defaults to 'logged_in'
	// (confirmed against Elgg's official Forms + Actions docs, same as
	// every prior CampingBuddy plugin's create/update actions) -- correct
	// here, since every one of these requires being logged in, with the
	// real, more specific authorization checks (listing/request ownership,
	// duplicate-request prevention, etc.) done inside each action itself.
	'actions' => [
		'campingbuddy_camping_together/listing/edit' => [],
		'campingbuddy_camping_together/listing/save' => [],
		'campingbuddy_camping_together/request/save' => [],
		'campingbuddy_camping_together/request/decide' => [],
	],

	'routes' => [
		// yoursite.com/camping-together -- the main browse page (spec
		// Section 10), split into Gauging Interest / Confirmed Trips.
		'campingbuddy_camping_together:all' => [
			'path' => '/camping-together',
			'resource' => 'campingbuddy_camping_together/all',
		],

		// yoursite.com/camping-together/admin/all -- admin oversight
		// (spec Section 11). Gated inside admin/all.php itself via
		// hasFullContentOversight(), not at the route level -- same
		// pattern as manage.php and edit.php.
		'campingbuddy_camping_together:admin:all' => [
			'path' => '/camping-together/admin/all',
			'resource' => 'campingbuddy_camping_together/admin/all',
		],

		// yoursite.com/camping-together/mine -- every listing a member
		// organizes plus every request they've made, regardless of
		// status. Moved up ahead of the original build order 2026-08-25
		// after a real live problem: a listing set to Full/Closed/etc
		// disappears from the status-filtered browse page (all.php) by
		// design, and without this page there was no way back to it
		// except site-wide search.
		'campingbuddy_camping_together:mine' => [
			'path' => '/camping-together/mine',
			'resource' => 'campingbuddy_camping_together/mine',
		],

		// yoursite.com/camping-together/add -- the add-listing form.
		'campingbuddy_camping_together:listing:add' => [
			'path' => '/camping-together/add',
			'resource' => 'campingbuddy_camping_together/listing/add',
		],

		// yoursite.com/camping-together/edit/{guid} -- edit an existing
		// listing (all six statuses, any time -- see header comment).
		// Access is NOT restricted at the route level -- elgg_gatekeeper()
		// plus the real ownership check happen inside edit.php itself,
		// same pattern as manage.php.
		'campingbuddy_camping_together:listing:edit' => [
			'path' => '/camping-together/edit/{guid}',
			'resource' => 'campingbuddy_camping_together/listing/edit',
		],

		// yoursite.com/camping-together/manage/{guid} -- the organizer's
		// request-review page (Step 4). Access is NOT restricted at the
		// route level -- elgg_gatekeeper() plus the real ownership check
		// happen inside manage.php itself, same pattern as every other
		// owner-only page in this project.
		'campingbuddy_camping_together:listing:manage' => [
			'path' => '/camping-together/manage/{guid}',
			'resource' => 'campingbuddy_camping_together/listing/manage',
		],

		// yoursite.com/camping-together/request/add/{guid} -- the
		// dedicated "Request to join" page (moved off the listing view
		// page 2026-08-22 per user feedback). {guid} here is the
		// LISTING's guid, not a request guid -- there is no request yet
		// at this point, this page is what creates one.
		'campingbuddy_camping_together:request:add' => [
			'path' => '/camping-together/request/add/{guid}',
			'resource' => 'campingbuddy_camping_together/request/add',
		],

		// yoursite.com/camping-together/view/{guid}/{title?} -- a single
		// listing. The 'view:object:campingbuddy_ct_listing' NAME (not
		// the path) is what Elgg's default ElggEntity::getURL() looks
		// for to auto-generate a listing's URL -- same confirmed pattern
		// as every prior plugin's own entity view route.
		'view:object:campingbuddy_ct_listing' => [
			'path' => '/camping-together/view/{guid}/{title?}',
			'resource' => 'campingbuddy_camping_together/listing/view',
		],
	],

	// Two entities from the spec (Sections 3 and 5), registered together
	// in one pass, same as every prior plugin's Step 1.
	//
	// 'subtype' values below are plain string literals, NOT references
	// to each class's SUBTYPE constant, per this project's established
	// safe pattern (ClassName::class is a name reference and doesn't
	// require the class to be loaded yet; ClassName::SUBTYPE is an
	// actual class constant and would need the class already autoloaded
	// at the exact moment this file is parsed -- untested territory on
	// this server). If either class's SUBTYPE constant is ever changed,
	// the string below must be updated to match by hand.
	'entities' => [
		[
			'type' => 'object',
			'subtype' => 'campingbuddy_ct_listing',
			'class' => \CampingBuddy\CampingTogether\CampingTogetherListing::class,
			'capabilities' => [
				// The listing is the browsable, public-facing piece of
				// this plugin (spec Section 2/9/10) -- it needs to be
				// findable, unlike Trip Planner's always-private
				// entities. Searchable => true here mirrors Place,
				// LogEntry, and LocalQuestion.
				'searchable' => true,
			],
		],
		[
			'type' => 'object',
			'subtype' => 'campingbuddy_ct_request',
			'class' => \CampingBuddy\CampingTogether\CampingTogetherRequest::class,
			'capabilities' => [
				// A join request is only ever meant to be seen by its
				// own author and the listing's organizer (spec Section
				// 9) -- it should never surface in site-wide search
				// results, same reasoning as Trip Planner's entities.
				'searchable' => false,
			],
		],
	],

];
