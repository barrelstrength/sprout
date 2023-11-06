<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use craft\base\Model;

class NameFormFieldData extends Model
{
    public ?string $prefix = null;

    public ?string $firstName = null;

    public ?string $middleName = null;

    public ?string $lastName = null;

    public ?string $suffix = null;

    public function __toString()
    {
        $name = '';

        if ($this->getFullName()) {
            $name = $this->getFullName();
        }

        return $name;
    }

    public function getFriendlyName(): string
    {
        return trim($this->firstName);
    }

    public function getFullName(): string
    {
        $firstName = trim($this->firstName);
        $lastName = trim($this->lastName);

        if (!$firstName && !$lastName) {
            return '';
        }

        $name = $firstName;

        if ($firstName && $lastName) {
            $name .= ' ';
        }

        $name .= $lastName;

        return !empty($name) ? $name : '';
    }

    public function getFullNameExtended(): string
    {
        $fullName = '';

        $fullName .= $this->appendName($this->prefix);
        $fullName .= $this->appendName($this->firstName);
        $fullName .= $this->appendName($this->middleName);
        $fullName .= $this->appendName($this->lastName);
        $fullName .= $this->appendName($this->suffix);

        return trim($fullName);
    }

    protected function appendName($name): ?string
    {
        if ($name) {
            return ' ' . $name;
        }

        return null;
    }
}
