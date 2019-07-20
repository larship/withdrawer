<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param UserRepository               $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param SessionInterface             $session
     */
    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder, SessionInterface $session)
    {
        $this->userRepository  = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->session         = $session;
    }

    /**
     * Метод выполняет попытку аутентификации пользователя
     *
     * @param string $username Имя пользователя
     * @param string $password Пароль пользователя
     *
     * @return bool
     */
    public function authenticate(string $username, string $password): bool
    {
        $user = $this->userRepository->findByUsername($username);

        if (empty($user)) {
            return false;
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
            return false;
        }

        $this->session->set('username', $username);
        $this->session->set('isAuthenticated', true);

        return true;
    }

    /**
     * Метод возвращает значение, которое показывает, аутентифицирован ли текущий пользователь
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->session->get('isAuthenticated', false);
    }

    /**
     * Получить аутентицифированного пользователя
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        $username = $this->session->get('username');

        if (empty($username)) {
            return null;
        }

        return $this->userRepository->findByUsername($username);
    }
}
