<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use craft\elements\User;

class PersonSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Person';
    }

    public function getType(): string
    {
        return 'Person';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }

    public function addProperties(): void
    {
        if (($this->element !== null) && $this->element instanceof User) {
            $this->addUserElementProperties();
        } else {
            parent::addProperties();
        }
    }

    public function addUserElementProperties(): void
    {
        /**
         * @var User $element
         */
        $element = $this->element;

        $name = null;

        if (method_exists($element, 'getFullName')) {
            $name = $element->fullName;
        }

        if ($element->firstName !== null && $element->lastName !== null) {
            $this->addText('givenName', $element->firstName);
            $this->addText('familyName', $element->lastName);

            $name = $element->firstName . ' ' . $element->lastName;
        }

        $this->addText('name', $name);
        $this->addEmail('email', $element->email);
    }
}
