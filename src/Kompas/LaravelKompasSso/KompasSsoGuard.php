<?php 
namespace Kompas\LaravelKompasSso;

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Session;

class KompasUserProvider implements UserProviderInterface {

    public function __construct()
    {

        App::make('accounts-session');
        $this->sharedSession = null;
        $sessionId = null;

        if (isset($_GET[$this->getTokenName()])) {
            $sessionId = $_GET[$this->getTokenName()];
        } else if (Session::has($this->getTokenName())) {
            $sessionId = Session::get($this->getTokenName());

            if (isset($_COOKIE[$this->getTokenName()])) {
                if ($_COOKIE[$this->getTokenName()]) {
                    if ($sessionId != $_COOKIE[$this->getTokenName()]) {
                        $sessionId = $_COOKIE[$this->getTokenName()];
                    }
                }
            }
        }

        session_name(strtoupper($this->getTokenName()));
        session_id($sessionId);
        session_start();

        $userToken = isset($_SESSION['userToken']) ? json_decode($_SESSION['userToken'], true) : false;
        $currentUser = isset($_SESSION['currentUser']) ? json_decode($_SESSION['currentUser'], true) : false;

        if ($userToken !== false && $currentUser !== false) {

            Session::put($this->getTokenName(), $sessionId);

            $this->sharedSession = [
                'userToken' => $userToken,
                'currentUser' => $currentUser
            ];

        }

    }

    public function retrieveByToken( $identifier, $token ){}
    public function updateRememberToken( \Illuminate\Auth\UserInterface $user, $token ){}

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveById($identifier)
    {
        $user = $this->retrieveByCredentials(array());
        if ($user && $user->getAuthIdentifier() == $identifier) {
            return $user;
        }
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if ($this->sharedSession) {

            $userinfo = $this->sharedSession['currentUser'];
            $userinfo['accessToken'] = $this->sharedSession['userToken'];

            return new GenericUser($userinfo);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Auth\UserInterface $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(UserInterface $user, array $credentials)
    {
        // this method doesn't make sense for Google auth
        return false;
    }

    public function getLoginUrl() {
        return "http://accounts.kompas.com/service_signin";
    }

    public function destroy()
    {
        session_destroy();
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * @return string
     */
    public function getTokenName()
    {
        return 'kmpsid';
    }

}
