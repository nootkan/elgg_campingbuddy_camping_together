<?php

namespace CampingBuddy\CampingTogether;

/**
 * classes/CampingBuddy/CampingTogether/CampingTogetherRequest.php
 *
 * object:campingbuddy_ct_request -- see
 * campingbuddy_camping_together_spec.md Section 5. A join request
 * against a listing.
 *
 * IMPORTANT DESIGN NOTE (corrected 2026-08-22 after a real, live bug):
 * this entity's container_guid is DELIBERATELY LEFT UNSET, so it
 * defaults to its own owner (the requester) -- exactly like every
 * other entity in this project (Place, LogEntry, TripPlan, etc.).
 * Which listing a request belongs to is instead tracked via a plain
 * metadata field, listing_guid (see getListingGuid()/setListingGuid()
 * below), not container_guid.
 *
 * This was NOT the original design -- container_guid was originally
 * set to the listing's own GUID, matching the "list every X belonging
 * to this Y" pattern already proven for campingbuddy_trip_item (Trip
 * Rooms) and campingbuddy_trip_plan_candidate (Trip Planner). That
 * failed in real testing with "not permitted to write to container" --
 * Elgg only allows a container's own OWNER to write into it by
 * default. TripItem and TripPlanCandidate never hit this because their
 * containers are either an ElggGroup (which has its own built-in
 * "any member may write" default) or the same user's own entity. This
 * request's container would have been a DIFFERENT user's plain object
 * (the organizer's listing) -- the first time this project has needed
 * that shape, and Elgg denies it by default. Rather than introduce a
 * brand-new, untested Elgg permissions-hook mechanism to grant that
 * write, this reuses the same "container defaults to owner" pattern
 * already proven everywhere else in the project.
 *
 * Stored as its own entity rather than a plain Elgg relationship
 * specifically because a request needs both independent access control
 * (a message the requester may not want visible to anyone but the
 * organizer) and a real lifecycle (requested -> approved/declined/
 * waitlisted/withdrawn) -- see spec Section 5 for the full reasoning.
 *
 * Same "light validation here, real trust boundary lives in the save
 * action" pattern used throughout this project -- this class rejects
 * obviously malformed values but does not enforce ownership, the
 * behaviour-rules acknowledgment (spec Section 8), or that the parent
 * listing actually exists and is open to requests. Those belong to the
 * save action.
 */
class CampingTogetherRequest extends \ElggObject {

	use HasCompatibilityPreferences;

	const SUBTYPE = 'campingbuddy_ct_request';

	// Kept identical to Logbook/Trip Planner's own camping_setup enum --
	// NOT the same list as CampingTogetherListing::CAMPING_STYLES. This
	// is the requester's own personal setup, which per spec Section 5
	// "may differ from the listing's general camping_style" -- e.g. a
	// listing might say 'mixed' while one requester is 'hammock' and
	// another is 'travel_trailer'.
	const CAMPING_SETUPS = [
		'tent',
		'car_camping',
		'pickup_camper',
		'camper_van',
		'travel_trailer',
		'fifth_wheel',
		'motorhome',
		'hammock',
		'backpacking',
		'boat_access',
		'other',
	];

	const STATUSES = [
		'requested',
		'approved',
		'declined',
		'waitlisted',
		'withdrawn',
	];

	public function __construct(?\stdClass $row = null) {
		parent::__construct($row);

		if (!isset($row)) {
			$this->setSubtype(self::SUBTYPE);
		}
	}

	// ---- which listing this request belongs to ----
	// A plain metadata field, NOT container_guid -- see the class header
	// for why. Whether this actually points to a real, existing
	// campingbuddy_ct_listing is verified by the save action, not here.

	public function getListingGuid(): ?int {
		$value = $this->listing_guid;
		return ($value !== null && $value !== '') ? (int) $value : null;
	}

	public function setListingGuid(int $guid): void {
		if ($guid < 1) {
			throw new \InvalidArgumentException('Invalid listing GUID.');
		}
		$this->listing_guid = $guid;
	}

	// ---- message ----
	// Whether this is non-blank is a requiredness rule, not a format
	// rule -- enforced by the save action, not this class, same
	// division used for LogEntry's title and TripRoom's trip_name.

	public function getMessage(): ?string {
		$value = $this->message;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setMessage(?string $message): void {
		$this->message = $message;
	}

	// ---- camping setup ----

	public function getCampingSetup(): ?string {
		$value = $this->camping_setup;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setCampingSetup(?string $setup): void {
		if ($setup !== null && !in_array($setup, self::CAMPING_SETUPS, true)) {
			throw new \InvalidArgumentException("Invalid camping setup: {$setup}");
		}
		$this->camping_setup = $setup;
	}

	// ---- status ----
	// Getter falls back to 'requested', the only status a brand-new
	// request should ever actually have -- same defensive-default
	// pattern as TripPlan::getStatus() and CampingTogetherListing::getStatus().

	public function getStatus(): string {
		$value = $this->status;
		return ($value !== null && $value !== '') ? (string) $value : 'requested';
	}

	public function setStatus(string $status): void {
		if (!in_array($status, self::STATUSES, true)) {
			throw new \InvalidArgumentException("Invalid request status: {$status}");
		}
		$this->status = $status;
	}

	// ---- organizer note ----

	public function getOrganizerNote(): ?string {
		$value = $this->organizer_note;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setOrganizerNote(?string $note): void {
		$this->organizer_note = $note;
	}
}
