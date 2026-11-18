<?php

namespace CampingBuddy\CampingTogether;

/**
 * classes/CampingBuddy/CampingTogether/HasCompatibilityPreferences.php
 *
 * The seven compatibility-preference fields (pets, children, smoking,
 * alcohol, noise, experience, atmosphere) plus the 250-character
 * Additional Comments field, per campingbuddy_camping_together_spec.md
 * Sections 3 and 5. Both CampingTogetherListing and CampingTogetherRequest
 * carry the exact same fields with the exact same allowed values, so
 * they're written once here as a trait rather than duplicated in both
 * classes -- if a preference option ever needs to change, it changes in
 * one place.
 *
 * IMPORTANT, per spec Section 6: every field here is optional. A blank
 * value means "no preference," not "not allowed." Nothing in this
 * plugin auto-filters or auto-declines based on these values -- that
 * decision is always left to the organizer reviewing a request, with
 * full context, per the workflow in spec Section 7.
 *
 * Age/age-range is deliberately NOT one of these fields -- see spec
 * Section 6 and the build log's 2026-08-21 Decisions Log entry for the
 * full reasoning (member-commissioned legal research review). This is a
 * deferral, not an oversight -- if it's ever added, it belongs in this
 * same trait, following the same "optional, non-blocking, private only"
 * pattern as everything else here.
 */
trait HasCompatibilityPreferences {

	const PREF_PETS_OPTIONS = [
		'fine_with_pets',
		'prefer_no_pets',
		'have_own_pet',
	];

	const PREF_CHILDREN_OPTIONS = [
		'fine_with_kids',
		'prefer_adult_only',
		'have_kids',
	];

	const PREF_SMOKING_OPTIONS = [
		'smoke_free_preferred',
		'smoking_fine',
		'i_smoke',
	];

	const PREF_ALCOHOL_OPTIONS = [
		'fine_either_way',
		'prefer_alcohol_free',
		'i_drink',
	];

	const PREF_NOISE_OPTIONS = [
		'quiet_after_dark',
		'some_evening_noise_fine',
		'generator_use_expected',
	];

	const PREF_EXPERIENCE_OPTIONS = [
		'new_to_camping',
		'some_experience',
		'very_experienced',
	];

	const PREF_ATMOSPHERE_OPTIONS = [
		'quiet_mellow',
		'easygoing',
		'social_lively',
	];

	const ADDITIONAL_COMMENTS_MAX_LENGTH = 250;

	// ---- pref_pets ----

	public function getPrefPets(): ?string {
		$value = $this->pref_pets;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefPets(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_PETS_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_pets value: {$value}");
		}
		$this->pref_pets = $value;
	}

	// ---- pref_children ----

	public function getPrefChildren(): ?string {
		$value = $this->pref_children;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefChildren(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_CHILDREN_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_children value: {$value}");
		}
		$this->pref_children = $value;
	}

	// ---- pref_smoking ----

	public function getPrefSmoking(): ?string {
		$value = $this->pref_smoking;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefSmoking(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_SMOKING_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_smoking value: {$value}");
		}
		$this->pref_smoking = $value;
	}

	// ---- pref_alcohol ----

	public function getPrefAlcohol(): ?string {
		$value = $this->pref_alcohol;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefAlcohol(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_ALCOHOL_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_alcohol value: {$value}");
		}
		$this->pref_alcohol = $value;
	}

	// ---- pref_noise ----

	public function getPrefNoise(): ?string {
		$value = $this->pref_noise;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefNoise(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_NOISE_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_noise value: {$value}");
		}
		$this->pref_noise = $value;
	}

	// ---- pref_experience ----

	public function getPrefExperience(): ?string {
		$value = $this->pref_experience;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefExperience(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_EXPERIENCE_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_experience value: {$value}");
		}
		$this->pref_experience = $value;
	}

	// ---- pref_atmosphere ----

	public function getPrefAtmosphere(): ?string {
		$value = $this->pref_atmosphere;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setPrefAtmosphere(?string $value): void {
		if ($value !== null && !in_array($value, self::PREF_ATMOSPHERE_OPTIONS, true)) {
			throw new \InvalidArgumentException("Invalid pref_atmosphere value: {$value}");
		}
		$this->pref_atmosphere = $value;
	}

	// ---- additional_comments ----
	// Deliberately capped at 250 characters -- an anti-rant design
	// choice, not a technical limitation. See spec Section 6 for the
	// full reasoning. Enforced here (not just in the form's maxlength
	// attribute) because a client-side limit alone can always be
	// bypassed by submitting the action directly.

	public function getAdditionalComments(): ?string {
		$value = $this->additional_comments;
		return ($value !== null && $value !== '') ? (string) $value : null;
	}

	public function setAdditionalComments(?string $value): void {
		if ($value === null) {
			$this->additional_comments = null;
			return;
		}
		$trimmed = trim($value);
		if ($trimmed === '') {
			$this->additional_comments = null;
			return;
		}
		if (mb_strlen($trimmed) > self::ADDITIONAL_COMMENTS_MAX_LENGTH) {
			throw new \InvalidArgumentException(
				'Additional comments must be ' . self::ADDITIONAL_COMMENTS_MAX_LENGTH . ' characters or fewer.'
			);
		}
		$this->additional_comments = $trimmed;
	}
}
