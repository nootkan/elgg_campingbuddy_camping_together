<?php

namespace CampingBuddy\CampingTogether;

/**
 * classes/CampingBuddy/CampingTogether/Bootstrap.php
 *
 * Registers this plugin's entries on the shared member Tools hub page
 * (/tools) and the Moderator Hub, via the campingbuddy:member_hub and
 * campingbuddy:moderator_hub events -- the same "let every active
 * plugin contribute to a shared collection" pattern already used by
 * every other CampingBuddy plugin (campingbuddy_trip_planner's own
 * Bootstrap.php used directly as the template here).
 *
 * MISSING UNTIL 2026-08-28: same real oversight already hit once before
 * by Trip Planner (2026-08-14) -- this plugin had every one of its
 * pages built and fully tested via direct URL only, with no Tools-menu
 * entry at all. Flagged by the user once building settled down, same
 * as it was for Trip Planner.
 */
class Bootstrap extends \Elgg\DefaultPluginBootstrap {

	public static function addToMemberHub(\Elgg\Event $event) {
		$return = $event->getValue();

		$return[] = [
			'label' => 'Camping Together',
			'href' => 'camping-together',
			'description' => 'Camp independently near other members, with no obligation to socialize -- browse open trips or start your own.',
		];

		$return[] = [
			'label' => 'My Camping Together',
			'href' => 'camping-together/mine',
			'description' => 'Every trip you organize and every request you\'ve made, regardless of status.',
		];

		return $return;
	}

	public static function addToModeratorHub(\Elgg\Event $event) {
		$return = $event->getValue();

		$return[] = [
			'label' => 'Camping Together (All Listings)',
			'href' => 'camping-together/admin/all',
			'description' => 'Every Camping Together listing site-wide, regardless of owner or status.',
		];

		return $return;
	}
}
