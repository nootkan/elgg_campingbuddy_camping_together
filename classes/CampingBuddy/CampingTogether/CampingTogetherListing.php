<?php

namespace CampingBuddy\CampingTogether;

/**
 * classes/CampingBuddy/CampingTogether/CampingTogetherListing.php
 *
 * object:campingbuddy_ct_listing -- see
 * campingbuddy_camping_together_spec.md Section 3. The public-facing
 * invitation -- what shows up in the browsable /camping-together list.
 * Deliberately holds only general, non-sensitive information; the real
 * destination, discussion, and other members live in the underlying
 * Trip Room (Section 4), created and referenced via trip_room_guid.
 *
 * Same "light validation here, real trust boundary lives in the
 * not-yet-built save action" pattern used throughout this project for
 * Place, LogEntry, LocalQuestion, TripItem, and TripPlan: this class
 * rejects obviously malformed values (wrong type, an enum value that
 * isn't on the allowed list) but does not itself enforce ownership,
 * access, or cross-entity rules (e.g. that trip_room_guid actually
 * points to a real Trip Room the current member owns) -- those belong
 * to the save action once it exists.
 */
class CampingTogetherListing extends \ElggObject {

	use HasCompatibilityPreferences;

	const SUBTYPE = 'campingbuddy_ct_listing';

	// Matches spec Section 7's two workflow paths -- 'gauging_interest'
	// is the lighter pre-stage, 'open' is a firm, browsable trip.
	const STATUSES = [
		'gauging_interest',
		'open',
		'full',
		'closed',
		'completed',
		'cancelled',
	];

	// Kept identical to campingbuddy_trip_rooms' own camping_style enum
	// (confirmed live in actions/campingbuddy_trip_rooms/save.php) --
	// NOT the same list as Logbook/Trip Planner's camping_setup enum
	// (which includes hammock/backpacking/boat_access instead of
	// 'mixed'). This field describes the *listing's* general camping
	// style, matching Trip Rooms' own field of the same name; the
	// *request*'s own camping_setup field (CampingTogetherRequest) uses
	// the other list -- see spec Section 5's note that it "may differ
	// from the listing's general camping_style."
	const CAMPING_STYLES = [
		'tent',
		'car_camping',
		'pickup_camper',
		'camper_van',
		'travel_trailer',
		'fifth_wheel',
		'motorhome',
		'mixed',
		'other',
	];

	// Kept identical to campingbuddy_trip_rooms' own social_expectation
	// enum -- this is the field that carries the "no obligation to
	// socialize" promise through to the listing itself (spec Section 3).
	const SOCIAL_EXPECTATIONS = [
		'social',
		'independent',
		'flexible',
	];

	public function __construct(?\stdClass $row = null) {
		parent::__construct($row);

		if (!isset($row)) {
			$this->setSubtype(self::SUBTYPE);
		}
	}

	// ---- region / subregion ----
	// Deliberately NOT validated against a fixed list of constants here,
	// unlike the enum fields below. campingbuddy_core's region system is
	// admin-editable (Region entity, added 2026-08-07) -- hardcoding an
	// allowed-region list in this class would go stale the moment an
	// admin adds or removes a region. Same reasoning already applied
	// throughout the rest of this project wherever the shared region
	// selector (input/campingbuddy_region_selector) is used.

	public function getRegion(): ?string {
		$value = $this->region;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setRegion(?string $region): void {
		$this->region = ($region !== null) ? trim($region) : null;
	}

	public function getSubregion(): ?string {
		$value = $this->subregion;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setSubregion(?string $subregion): void {
		$this->subregion = ($subregion !== null) ? trim($subregion) : null;
	}

	// ---- target dates (stored as unix timestamps, same convention as
	// TripPlan's target_start/target_end) ----

	public function getTargetStart(): ?int {
		$value = $this->target_start;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setTargetStart(?int $timestamp): void {
		$this->target_start = $timestamp;
	}

	public function getTargetEnd(): ?int {
		$value = $this->target_end;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setTargetEnd(?int $timestamp): void {
		$this->target_end = $timestamp;
	}

	// ---- date flexibility ----

	public function isDateFlexible(): bool {
		return (bool) $this->date_flexible;
	}

	public function setDateFlexible(bool $flexible): void {
		$this->date_flexible = $flexible ? 1 : 0;
	}

	// ---- camping style ----

	public function getCampingStyle(): ?string {
		$value = $this->camping_style;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setCampingStyle(?string $style): void {
		if ($style !== null && !in_array($style, self::CAMPING_STYLES, true)) {
			throw new \InvalidArgumentException("Invalid camping style: {$style}");
		}
		$this->camping_style = $style;
	}

	// ---- social expectation ----

	public function getSocialExpectation(): ?string {
		$value = $this->social_expectation;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setSocialExpectation(?string $expectation): void {
		if ($expectation !== null && !in_array($expectation, self::SOCIAL_EXPECTATIONS, true)) {
			throw new \InvalidArgumentException("Invalid social expectation: {$expectation}");
		}
		$this->social_expectation = $expectation;
	}

	// ---- max participants ----
	// spaces_remaining (spec Section 3) is deliberately NOT a field on
	// this class -- it's computed display-only from max_participants
	// minus the current approved-request count, which requires querying
	// this listing's campingbuddy_ct_request children. That belongs to
	// the resource view/vertical slice built in Step 2, not this entity
	// class.

	// ---- participants (min/max pair) ----
	// Changed from a single fixed number to a min/max pair, per real
	// user feedback (2026-08-21): a hard single number doesn't fit how
	// these trips actually form -- an organizer is often fine with a
	// range (e.g. "3-5 people"), not one exact headcount. The form
	// presents this as friendly preset radio buttons (2 / 3-5 / 6-8 /
	// 9-11 / 12+ / Other), but the underlying storage is always these
	// two plain numbers -- kept this way (rather than storing a text
	// bucket like "3-5") specifically so the future spaces_remaining
	// display (spec Section 3) can still do real arithmetic against it
	// once join requests exist. min_participants and max_participants
	// are independently nullable: min alone with no max means
	// open-ended ("12+ people"); both set means a bounded range or a
	// single fixed number (min == max).

	public function getMinParticipants(): ?int {
		$value = $this->min_participants;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setMinParticipants(?int $min): void {
		if ($min !== null && $min < 1) {
			throw new \InvalidArgumentException('Minimum participants must be at least 1.');
		}
		$this->min_participants = $min;
	}

	public function getMaxParticipants(): ?int {
		$value = $this->max_participants;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setMaxParticipants(?int $max): void {
		if ($max !== null && $max < 1) {
			throw new \InvalidArgumentException('Maximum participants must be at least 1.');
		}
		$this->max_participants = $max;
	}

	// ---- pet info / accessibility notes ----

	public function getPetInfo(): ?string {
		$value = $this->pet_info;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPetInfo(?string $info): void {
		$this->pet_info = $info;
	}

	public function getAccessibilityNotes(): ?string {
		$value = $this->accessibility_notes;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setAccessibilityNotes(?string $notes): void {
		$this->accessibility_notes = $notes;
	}

	// ---- description ----
	// Note: deliberately named 'description' to match ElggObject's own
	// built-in longtext property (same convention Place and LogEntry
	// already use), rather than a separate custom metadata field.

	public function getDescription(): ?string {
		$value = $this->description;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setDescription(?string $description): void {
		$this->description = $description;
	}

	// ---- application deadline ----

	public function getApplicationDeadline(): ?int {
		$value = $this->application_deadline;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setApplicationDeadline(?int $timestamp): void {
		$this->application_deadline = $timestamp;
	}

	// ---- status ----
	// Getter falls back to 'gauging_interest' if unset, matching the
	// spec's framing of that stage as the common, lower-commitment entry
	// point (Section 7, Path A). In practice the save action will always
	// set this explicitly, since spec Section 10's /camping-together/add
	// page requires the organizer to choose one of the two paths up
	// front -- this fallback exists purely as a safe default, same
	// pattern as TripPlan::getStatus().

	public function getStatus(): string {
		$value = $this->status;
		return ($value !== null && $value !== '') ? (string) $value : 'gauging_interest';
	}

	public function setStatus(string $status): void {
		if (!in_array($status, self::STATUSES, true)) {
			throw new \InvalidArgumentException("Invalid listing status: {$status}");
		}
		$this->status = $status;
	}

	// ---- underlying Trip Room reference ----
	// Whether a submitted trip_room_guid actually points to a real Trip
	// Room this member owns is verified by the save action, not here --
	// same division of responsibility used throughout this project (e.g.
	// LogEntry::setPlaceGuid()).

	public function getTripRoomGuid(): ?int {
		$value = $this->trip_room_guid;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setTripRoomGuid(?int $guid): void {
		$this->trip_room_guid = $guid;
	}
}
