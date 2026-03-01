<?php

declare(strict_types=1);

namespace App\Traits;

use function is_int;
use function is_string;
use function strlen;

/**
 * Assorted phone validation and formatting methods.
 */
trait PhoneValidation
{
    /**
     * Formats a phone number to E.164 format and validates it.
     *
     * @param mixed $number
     * @return false|string
     */
    private function formatPhoneNumberE164AndValidate(mixed $number): false|string
    {
        $number = $this->formatPhoneNumberTenDigit($number);

        if (!$this->isValidUsPhoneTenDigit($number)) {
            return false;
        }

        return $this->formatPhoneNumberE164($number);
    }

    /**
     * Formats a phone number to 10 digit format.
     *
     * @param mixed $phoneNumber
     * @return string
     */
    private function formatPhoneNumberTenDigit(mixed $phoneNumber): string
    {
        $phoneNumber = $this->stringStripNonDigit($phoneNumber);

        if (strlen($phoneNumber) === 10) {
            return $phoneNumber;
        }

        if ((strlen($phoneNumber) === 11) && $phoneNumber[0] === '1') {
            return substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }

    /**
     * Strip all non-digit characters from a string.
     *
     * @param mixed $input
     * @return string
     */
    private function stringStripNonDigit(mixed $input): string
    {
        if (!is_string($input) && !is_int($input)) {
            return '';
        }

        return preg_replace('/\D+/', '', $input);
    }

    /**
     * Returns true if the provided phone number is valid in US format.
     *
     * @param mixed $phoneNumber
     * @return bool
     */
    private function isValidUsPhoneTenDigit(mixed $phoneNumber): bool
    {
        // Must be a string or integer
        if (!is_string($phoneNumber) && !is_int($phoneNumber)) {
            return false;
        }

        // Convert to string
        $phoneNumber = (string)$phoneNumber;

        // Must be 10 digits
        if (strlen($phoneNumber) !== 10) {
            return false;
        }

        $npa = substr($phoneNumber, 0, 3);
        $nxx = substr($phoneNumber, 3, 3);

        // NPA cannot start with 0 or 1
        if ($npa[0] < '2') {
            return false;
        }

        // NPA cannot be N11 (211, 311, ... 911)
        if (preg_match('/^[2-9]11$/', $npa)) {
            return false;
        }

        // NXX cannot start with 0 or 1
        if ($nxx[0] < '2') {
            return false;
        }

        // NXX cannot be 555
        if ($nxx === '555') {
            return false;
        }

        // NXX cannot be N11 (211, 311, ... 911)
        if (preg_match('/^[2-9]11$/', $nxx)) {
            return false;
        }

        return true;
    }

    /**
     * Formats a phone number to E.164 format.
     *
     * @param mixed $number
     * @return string
     */
    private function formatPhoneNumberE164(mixed $number): string
    {
        $number = $this->stringStripNonDigit($number);

        if (strlen($number) === 10) {
            return '+1' . $number;
        }

        if ((strlen($number) === 11) && ($number[0] === '1')) {
            return '+' . $number;
        }

        return $number;
    }
}
