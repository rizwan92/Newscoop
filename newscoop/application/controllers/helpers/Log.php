<?php

/**
 * Log action helper
 */
class Action_Helper_Log extends Zend_Controller_Action_Helper_Abstract
{
    /** @var Zend_Log */
    private $logger = NULL;

    /**
     * Init Logger
     *
     * @return Zend_Log
     */
    public function init()
    {
        return $this->getLogger();
    }

    /**
     * Set Logger
     *
     * @param Zend_Log
     * @return Action_Helper_Log
     */
    public function setLogger(Zend_Log $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get Logger
     *
     * @return Zend_Log
     */
    public function getLogger()
    {
        if ($this->logger === NULL) {
            $controller = $this->getActionController();
            $bootstrap = $controller->getInvokeArg('bootstrap');
            $this->setLogger($bootstrap->getResource('Log'));
        }

        return $this->logger;
    }

    /**
     * Direct strategy
     *
     * @params string $message
     * @params int $severity
     * @return void
     */
    public function direct($message, $severity = Zend_Log::INFO, $extra = NULL)
    {
        $this->getLogger()->log((string) $message, (int) $severity, $extra);
    }
}