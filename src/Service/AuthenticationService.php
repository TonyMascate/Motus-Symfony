<?php

namespace App\Service;

use App\DTO\RegisterDTO;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationService
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    private function createUserEntity(RegisterDTO $registerDTO): User
    {
        $user = new User();
        $user->setPseudo($registerDTO->pseudo);
        $user->setEmail($registerDTO->email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $registerDTO->password);
        $user->setPassword($hashedPassword);

        return $user;
    }

    public function registerUser(RegisterDTO $registerDTO): string
    {
        $user = $this->createUserEntity($registerDTO);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new BadRequestHttpException("L'email est déjà utilisé. Veuillez vous connecter.");
        } catch (\Exception) {
            throw new \RuntimeException("Une erreur est survenue, veuillez réessayer plus tard.");
        }

        return $this->jwtManager->create($user);
    }

}
