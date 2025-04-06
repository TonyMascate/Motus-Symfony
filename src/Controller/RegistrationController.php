<?php

namespace App\Controller;

use App\DTO\RegisterDTO;
use App\Entity\User;
use App\Exception\ErrorResponseFactory;
use App\Service\AuthenticationService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_registration', methods: ['POST'])]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        AuthenticationService $authenticationService
    ): JsonResponse {
        $jsonData = $request->getContent();

        try {
            $registerDTO = $serializer->deserialize($jsonData, RegisterDTO::class, 'json');
        } catch (\Exception $e) {
            return ErrorResponseFactory::create('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($registerDTO);
        if (count($errors) > 0) {
            return ErrorResponseFactory::create(
                'Erreur de validation',
                Response::HTTP_BAD_REQUEST,
                ErrorResponseFactory::formatValidationErrors($errors)
            );
        }

        try {
            $token = $authenticationService->registerUser($registerDTO);
        } catch (HttpExceptionInterface $e) {
            return ErrorResponseFactory::create($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ErrorResponseFactory::create("Une erreur interne est survenue.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = new JsonResponse(['message' => 'Inscription réussie'], Response::HTTP_CREATED);
        $response->headers->setCookie(
            Cookie::create('BEARER', $token)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('None')
        );

        return $response;
    }

    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new JsonResponse([
                'error' => [
                    'message' => 'Email et mot de passe requis',
                    'code' => Response::HTTP_BAD_REQUEST
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'error' => [
                    'message' => 'Identifiants invalides',
                    'code' => Response::HTTP_UNAUTHORIZED
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }


        // Création du token JWT
        $token = $jwtManager->create($user);

        $response = new JsonResponse([
            'message' => 'Connexion réussie'
        ]);
        $response->headers->setCookie(
            Cookie::create('BEARER', $token)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('None') // ou 'Lax' si même domaine
        );

        return $response;
    }

    #[Route('/api/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        try {
            $response = new JsonResponse(['message' => 'Déconnexion réussie.']);
            $response->headers->setCookie(
                Cookie::create('BEARER', '')
                    ->withSecure(true)
                    ->withHttpOnly(true)
                    ->withSameSite('None')
                    ->withExpires(0) // Supprime le cookie
            );

            return $response;
        } catch (\Throwable $e) {
            return ErrorResponseFactory::create(
                "Une erreur est survenue lors de la déconnexion.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('/api/auth/check', name: 'auth_check', methods: ['GET'])]
    public function checkAuth(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['authenticated' => false], 401);
        }

        return new JsonResponse(['authenticated' => true]);
    }

}
