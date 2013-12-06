<?php
namespace Fphone\Provider;
use FOS\FacebookBundle\Security\User\UserManagerInterface as FacebookUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Silex\Application;
use RedBean_Facade;

class FacebookUserProvider implements FacebookUserProviderInterface
{
private $app;

public function __construct($app)
{
    $this->app = $app;

}

public function loadUserByUsername($uid)
{
$user = $this->app['db']->findOne("user","fbid=?",array($uid));
if (!$user) {
    throw new UsernameNotFoundException(sprintf('Facebook UID "%s" does not exist.', $uid));
}
return new User($user->fbid, null, explode(',', $user['roles']), true, true, true, true);
}

public function refreshUser(UserInterface $user)
{
if (!$user instanceof User) {
throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
}

return $this->loadUserByUsername($user->getUsername());
}

public function createUserFromUid($uid)
{
    $user = $this->app['db']->dispense("user");
    $user->fbid = $uid;
    $this->app['db']->store($user);
    return $this->loadUserByUsername($uid);
}

public function supportsClass($class)
{
return $class === 'Symfony\Component\Security\Core\User\User';
}
}