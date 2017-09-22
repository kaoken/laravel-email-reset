<?php

namespace Kaoken\LaravelMailReset;


interface IMailResetBrokerFactory
{
    /**
     * Get a email reset broker instance by name.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function broker($name = null);

}