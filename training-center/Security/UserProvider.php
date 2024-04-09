<?php

namespace AppBundle\Security;

use AppBundle\Model\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        $User = User::where('email', $username)->first();

        if (!$User) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        return $User;
    }

    public function refreshUser(UserInterface $User)
    {
        if (!$User instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($User))
            );
        }

        return $this->loadUserByUsername($User->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'AppBundle\Model\User';
    }
}