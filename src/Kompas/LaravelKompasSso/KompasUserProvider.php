<?php 
namespace Kompas\LaravelKompasSso;

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redis;

class KompasUserProvider implements UserProviderInterface {

    public function __construct()
    {
        $this->sharedSession = null;

        if (Session::has($this->getTokenName())) {
            $this->sharedSession['userToken'] = Session::get('userToken');
            $this->sharedSession['currentUser'] = Session::get('currentUser');
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
        return "http://accounts.kompas.com/service_signin?continue=" . urlencode("http://beta.kompasiana.com") . "&client_id=1332276278";
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

    /**
     * If this request is the redirect from a successful authorization grant, store the access token in the session
     * and return a Laravel redirect Response to send the user to their requested page. Otherwise returns null
     * @return Response or null
     */
    public function finishAuthenticationIfRequired()
    {
        if (isset($_GET[$this->getTokenName()])) {

            // register handler redis
            ini_set('session.gc_maxlifetime', 25200); // 1 week
            ini_set('session.cookie_httponly', true); // enable httponly
            ini_set('session.cookie_domain', '.kompasiana.com'); // enable all sub domain

            $redis = Redis::connection('accounts-session');
            $handler = new \Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler($redis, [], 'accounts_session');
            session_set_save_handler(
                array($handler, 'open'),
                array($handler, 'close'),
                array($handler, 'read'),
                array($handler, 'write'),
                array($handler, 'destroy'),
                array($handler, 'gc')
            );
            register_shutdown_function('session_write_close');
            session_name(strtoupper($this->getTokenName()));
            session_id($_GET[$this->getTokenName()]);
            session_start();

            $userToken = isset($_SESSION['userToken']) ? json_decode($_SESSION['userToken'], true) : false;
            $currentUser = isset($_SESSION['currentUser']) ? json_decode($_SESSION['currentUser'], true) : false;

            if ($userToken !== false && $currentUser !== false) {

                Session::put('userToken', $userToken);
                Session::put('currentUser', $currentUser);
                Session::put($this->getTokenName(), $_GET[$this->getTokenName()]);

            }

            // strip the querystring from the current URL
            $url = rtrim(URL::to('/'));

            return Redirect::to(filter_var($url, FILTER_SANITIZE_URL));
        }

        return null;
    }

}
