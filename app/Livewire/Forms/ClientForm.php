<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Enums\Sunat\DocIdentityType;

class ClientForm extends Form
{
    #[Validate('nullable|string|max:255')]
    public $name = '';

    #[Validate('nullable|string|max:255')]
    public $lastName = '';

    #[Validate('nullable|string|max:255')]
    public $tradeName = '';

    #[Validate('nullable|string|max:255')]
    public $address = '';

    #[Validate('nullable|email|max:255')]
    public $email = '';

    #[Validate('nullable|string|max:20')]
    public $telephone = '';

    #[Validate('required')]
    public $docIdentityType = DocIdentityType::DNI;

    #[Validate('required|string|max:20')]
    public $documentNumber = '';

    #[Validate('boolean')]
    public $isActive = true;

    #[Validate('required')]
    public $departmentId = '';

    #[Validate('required')]
    public $provinceId = '';

    #[Validate('required')]
    public $districtId = '';
}