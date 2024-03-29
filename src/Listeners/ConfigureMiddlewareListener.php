<?php
    /**
     * Created by PhpStorm.
     * User: noahkovacs
     * Date: 3/13/18
     * Time: 16:00
     * @soundtrack Last Young Renegade - All Time Low
     */

    namespace augustineinstitute\jwt\Listeners;

    use augustineinstitute\jwt\Middleware\AuthenticateWithJWT;
    use augustineinstitute\jwt\Util\Logger;
    use Flarum\Event\ConfigureMiddleware;
    use Illuminate\Contracts\Events\Dispatcher;
    use function getenv;

    class ConfigureMiddlewareListener
    {
        /**
         * @var Dispatcher $events
         */
        protected $events;
        protected $logger;
        protected $prefix = "Bearer ";


        public function __construct()
        {
            $this->logger = new Logger("flarum_jwt_ext");
        }

        /**
         * @param Dispatcher $events
         */
        function listen(Dispatcher $events)
        {
            $this->events = $events;
            $events->listen(ConfigureMiddleware::class, [$this, 'handler']);
        }

        /**
         * @var $apiOnly bool
         */
        private $apiOnly;
        private $forumOnly;
        private $applyToAll;
        private $env;
        private $enforce;
        private $checkCookies;
        private $token;

        /**
         * @param ConfigureMiddleware $event
         * @soundtrack In Loving Memory - Alter Bridge
         */
        public function handler(ConfigureMiddleware $event)
        {
            $this->apiOnly = getenv("JWT_API_ONLY") ?: false;
            $this->forumOnly = getenv("JWT_FORUM_ONLY") ?: false;
            $this->applyToAll = !$this->apiOnly && !$this->forumOnly;
            $this->checkCookies = getenv("JWT_CHECK_COOKIE") ?: true;
            $this->env = getenv("ENVIRONMENT") ?: "production";
            $this->enforce = getenv("JWT_ENFORCE") ?: true;

            if (($this->applyToAll || ($this->apiOnly && $event->isApi()) || ($this->forumOnly && $event->isForum()))) {
                if ($this->checkCookies) {
                    if ($this->env !== "production" && isset($_COOKIE[$this->env . '_formed_org-jwt'])) {
                        $this->token = $_COOKIE[$this->env . '_formed_org-jwt'];
                    } else if (isset($_COOKIE['formed_org-jwt'])) {
                        $this->token = $_COOKIE['formed_org-jwt'];
                    } else {
                        $this->token = null;
                    }
                }

                $isApi = $event->isApi();

                $authWithJwt = new AuthenticateWithJWT($this->token, $this->enforce, $isApi);

                $event->pipe->pipe('/', $authWithJwt);
            }

        }

    }