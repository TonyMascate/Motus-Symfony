<?php

namespace App\Controller;

use App\DTO\RegisterDTO;
use App\Entity\User;
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
            // Désérialiser le JSON directement en objet RegisterDTO
            $registerDTO = $serializer->deserialize($jsonData, RegisterDTO::class, 'json');
        } catch (\Exception $e) {
            // Ne pas retourner le message d'exception pour des raisons de sécurité
            return new JsonResponse([
                'error' => [
                    'message' => 'JSON invalide',
                    'code' => Response::HTTP_BAD_REQUEST
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le DTO
        $errors = $validator->validate($registerDTO);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => [
                    'message' => 'Erreur de validation',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'details' => $errorMessages
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = $authenticationService->registerUser($registerDTO);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getStatusCode()
                ]
            ], $e->getStatusCode());
        } catch (\Throwable) { // `Throwable` capture aussi bien `Exception` que `Error`
            return new JsonResponse([
                'error' => [
                    'message' => "Une erreur interne est survenue.",
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        // Création de la réponse en cas de succès
        $response = new JsonResponse([
            'message' => 'Inscription réussie'
        ], Response::HTTP_CREATED);

        try {
            // Création du cookie sécurisé avec le token JWT
            $cookie = Cookie::create(
                'BEARER',
                $token,
                time() + 3600,  // 1 heure d'expiration
                '/',
                null, // Défini dans le fichier .env en prod
                true,  // Secure
                true,  // HttpOnly
                false,
                Cookie::SAMESITE_NONE // Si frontend séparé
            );

            // Ajouter le cookie à la réponse
            $response->headers->setCookie($cookie);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => [
                    'message' => 'Erreur lors de la création du cookie',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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

        // Création d'un cookie HTTP Only contenant le token
        $cookie = Cookie::create(
            'BEARER',
            $token,
            time() + 3600,  // 1 heure d'expiration
            '/',
            null, // Défini dans le fichier .env en prod
            false,  // Secure
            true,  // HttpOnly
            false,
            Cookie::SAMESITE_LAX // Si frontend séparé
        );


        $response = new JsonResponse([
            'message' => 'Connexion réussie'
        ]);
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/api/verify', name: 'api_verify', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function verify(Request $request, Security $security): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté

        if (!$user) {
            return new JsonResponse([
                'error' => [
                    'message' => 'Non authentifié',
                    'code' => Response::HTTP_UNAUTHORIZED
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'message' => 'Utilisateur authentifié',
            'user' => $user->getPseudo()
        ]);
    }
}
