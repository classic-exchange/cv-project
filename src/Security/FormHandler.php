<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use HWI\Bundle\OAuthBundle\Form\RegistrationFormHandlerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class FormHandler implements RegistrationFormHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function process(Request $request, FormInterface $form, UserResponseInterface $userInformation): bool
    {
        $user = new User();
        $email = $userInformation->getEmail();
        if ($this->userRepository->findOneBy(['email' => $email])) {
            return false;
        }
        $user->setEmail($email);
        $form->setData($user);
        return true;
    }
}
