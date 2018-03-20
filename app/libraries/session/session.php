<?php

namespace App\libraries\session;

use App\helper\Functions;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



/**
 * Class Session
 *
 * This class is meant to provide a easy way to manage sessions.
 *
 * @property Manager $db
 *
 * @package App\libraries\session
 */
class Session
{
    /**
     * @var array
     */
    protected $settings;


    /**
     * Session constructor.
     * @param array $settings
     */
    public function __construct($settings = []){
        set_exception_handler(array($this, 'exception_handler'));

        $defaults = [
            'lifetime'    => '20 minutes',
            'path'        => '/',
            'domain'      => null,
            'secure'      => true,
            'httponly'    => true,
            'name'        => 'api_session',
            'autorefresh' => false,
        ];
        $settings = array_merge($defaults, $settings);

       /* if(!empty($this->db)){
            // Set handler to overide SESSION
            session_set_save_handler(
                array($this, "_open"),
                array($this, "_close"),
                array($this, "_read"),
                array($this, "_write"),
                array($this, "_destroy"),
                array($this, "_gc")
            );
        }*/

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }

        $this->settings = $settings;
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 1);
        ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);

        $this->startSession();
    }


    /**
     * Called when middleware needs to be executed.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next){
        return $next($request, $response);
    }


    /**
     * Start session
     */
    protected function startSession(){
        $settings = $this->settings;
        $name = $settings['name'];

        session_set_cookie_params(
            $settings['lifetime'],
            $settings['path'],
            $settings['domain'],
            $settings['secure'],
            $settings['httponly']
        );

        $active = session_status() === PHP_SESSION_ACTIVE;

        if ($active) {
            if ($settings['autorefresh'] && isset($_COOKIE[$name])) {
                setcookie(
                    $name,
                    $_COOKIE[$name],
                    time() + $settings['lifetime'],
                    $settings['path'],
                    $settings['domain'],
                    $settings['secure'],
                    $settings['httponly']
                );
            }
        }

        session_name($name);
        session_cache_limiter(false);
        if (!$active) {
            session_start();
        }
    }


    /**
     * @param $exception
     */
    public function exception_handler($exception) {
        Functions::logException($exception, 'Session');
        ob_end_clean(); # try to purge content sent so far
        header('HTTP/1.1 '.HTTP_BACKEND_ERROR.' 	'.BACKEND_ERROR_MESSAGE);
        header('Content-type: application/json');
        echo json_encode(array('status' => 'Error', 'message' => BACKEND_ERROR_MESSAGE));
    }
}

