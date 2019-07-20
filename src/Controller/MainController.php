<?php

declare(strict_types=1);

namespace App\Controller;

use Throwable;
use App\Repository\UserBalanceRepository;
use App\Services\UserService;
use App\Services\WithdrawService;
use App\Exceptions\NotEnoughMoneyException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var UserBalanceRepository
     */
    private $userBalanceRepository;

    /**
     * @param SessionInterface      $session
     * @param UserBalanceRepository $userBalanceRepository
     */
    public function __construct(SessionInterface $session, UserBalanceRepository $userBalanceRepository)
    {
        $this->session               = $session;
        $this->userBalanceRepository = $userBalanceRepository;
    }

    /**
     * @Route("/", name="main")
     *
     * @param UserService $userService
     *
     * @return Response
     */
    public function index(UserService $userService): Response
    {
        if ($userService->isAuthenticated()) {
            return new RedirectResponse('/withdraw');
        }

        $authError = $this->session->get('authenticationError');
        $this->session->remove('authenticationError');

        return $this->render('login.html.twig', ['authError' => $authError]);
    }

    /**
     * @Route("/login-action", name="login_action")
     *
     * @param Request     $request
     * @param UserService $userService
     *
     * @return Response
     */
    public function loginAction(Request $request, UserService $userService): Response
    {
        $username = $request->get('username');
        $password = $request->get('password');

        if (!$userService->authenticate($username, $password)) {
            $this->session->set('authenticationError', 'Неправильный логин или пароль!');
            return new RedirectResponse($this->generateUrl('main'));
        }

        return new RedirectResponse('/withdraw');
    }

    /**
     * @Route("/withdraw", name="withdraw_page")
     *
     * @param UserService $userService
     *
     * @return Response
     */
    public function showWithdrawPage(UserService $userService): Response
    {
        if (empty($user = $userService->getAuthenticatedUser())) {
            return new RedirectResponse('/');
        }

        $balance = $this->userBalanceRepository->findByUserId($user->getId());

        if (empty($balance)) {
            return new RedirectResponse('/');
        }

        $withdrawStatus     = $this->session->get('withdrawStatus');
        $withdrawStatusText = $this->session->get('withdrawStatusText');
        $params             = [
            'username' => $user->getUsername(),
            'balance' => $balance->getBalance(),
        ];

        if ($withdrawStatus === true || $withdrawStatus === false) {
            $params['withdrawStatus']     = $withdrawStatus;
            $params['withdrawStatusText'] = $withdrawStatusText;

            $this->session->remove('withdrawStatus');
            $this->session->remove('withdrawStatusText');
        }

        return $this->render('withdraw.html.twig', $params);
    }

    /**
     * @Route("/withdraw-action", name="withdraw_action")
     *
     * @param Request         $request
     * @param UserService     $userService
     * @param WithdrawService $withdrawService
     *
     * @return Response
     */
    public function withdrawAction(Request $request, UserService $userService, WithdrawService $withdrawService): Response
    {
        if (empty($user = $userService->getAuthenticatedUser())) {
            return new RedirectResponse('/');
        }

        try {
            $withdrawService->withdraw($user->getId(), $request->get('sum'));
        } catch (NotEnoughMoneyException $exception) {
            $this->session->set('withdrawStatus', false);
            $this->session->set('withdrawStatusText', 'Невозможно выполнить списание: недостаточно средств!');
            return new RedirectResponse('/withdraw');
        } catch (Throwable $throwable) {
            $this->session->set('withdrawStatus', false);
            $this->session->set('withdrawStatusText', 'Невозможно выполнить списание!');
            return new RedirectResponse('/withdraw');
        }

        $this->session->set('withdrawStatus', true);
        $this->session->set('withdrawStatusText', 'Списание средств выполнено успешно!');
        return new RedirectResponse('/withdraw');
    }

    /**
     * @Route("/logout", name="logout")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function logoutAction(Request $request): Response
    {
        $this->session->invalidate();
        return new RedirectResponse('/');
    }
}
