<?php

namespace Kaoken\LaravelMailReset;

use Illuminate\Support\Str;
use InvalidArgumentException;

class MailResetBrokerManager implements IMailResetBrokerFactory
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $brokers = [];

    /**
     * Create a new PasswordBroker manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the broker from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return isset($this->brokers[$name])
            ? $this->brokers[$name]
            : $this->brokers[$name] = $this->resolve($name);
    }

    /**
    /**
     * Resolve the given broker.
     *
     * @param  string  $name
     * @return MailResetBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Mail address reset [{$name}] is not defined.");
        }

        return new MailResetBroker(
            $this->createTokenRepository($config),
            $config['path'],
            $config['model'],
            $this->app['mailer'],
            $config['email_reset']
        );
    }


    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param  array  $config
     * @return MailResetDB
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $connection = isset($config['connection']) ? $config['connection'] : null;

        return new MailResetDB(
            $this->app['db']->connection($connection),
            $config['table'],
            $config['model'],
            $key,
            $config['expire']
        );
    }

    /**
     * Get the password broker configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.mail_reset.{$name}"];
    }

    /**
     * Get the default password broker name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.mail_reset'];
    }


    /**
     * Set the default password broker name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.mail_reset'] = $name;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->broker()->{$method}(...$parameters);
    }
}
