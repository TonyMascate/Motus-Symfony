<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDTO
{
    #[Assert\NotBlank(message: "Le pseudo ne peut pas être vide")]
    #[Assert\Length(
        min: 4,
        max: 50,
        minMessage: "Le pseudo doit faire au moins {{ limit }} caractères",
        maxMessage: "Le pseudo ne peut pas dépasser {{ limit }} caractères"
    )]
    public string $pseudo;

    #[Assert\NotBlank(message: "L'email ne peut pas être vide")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    public string $email;

    #[Assert\NotBlank(message: "Merci de saisir un mot de passe")]
    #[Assert\Length(
        min: 6,
        max: 4096,
        minMessage: "Votre mot de passe doit contenir au moins {{ limit }} caractères"
    )]
    public string $password;
}
