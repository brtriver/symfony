<?php

namespace Symfony\Bundle\FrameworkBundle\Debug;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * ExceptionManager.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class ExceptionManager
{
    protected $exception;
    protected $logger;

    public function __construct(\Exception $exception, DebugLoggerInterface $logger = null)
    {
        $this->exception = $exception;
        $this->logger = $logger;
    }

    public function getLinkedManagers()
    {
        $managers = array();
        $e = $this->exception;
        while ($e = $e->getPrevious()) {
            $managers[] = new $this($e);
        }

        return $managers;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getLogs()
    {
        return null === $this->logger ? array() : $this->logger->getLogs();
    }

    public function countErrors()
    {
        if (null === $this->logger) {
            return 0;
        }

        $errors = 0;
        foreach ($this->logger->getLogs() as $log) {
            if ('ERR' === $log['priorityName']) {
                ++$errors;
            }
        }

        return $errors;
    }

    public function getStatusCode()
    {
        return $this->exception instanceof HttpException ? $this->exception->getCode() : 500;
    }

    public function getStatusText()
    {
        return Response::$statusTexts[$this->getStatusCode()];
    }

    public function getMessage()
    {
        return null === $this->exception->getMessage() ? 'n/a' : $this->exception->getMessage();
    }

    public function getName()
    {
        return get_class($this->exception);
    }

    /**
     * Returns an array of exception traces.
     *
     * @return array An array of traces
     */
    public function getTraces()
    {
        $traces = array();
        $traces[] = array(
            'class'    => '',
            'type'     => '',
            'function' => '',
            'file'     => $this->exception->getFile(),
            'line'     => $this->exception->getLine(),
            'args'     => array(),
        );
        foreach ($this->exception->getTrace() as $entry) {
            $class = '';
            $namespace = '';
            if (isset($entry['class'])) {
                $parts = explode('\\', $entry['class']);
                $class = array_pop($parts);
                $namespace = implode('\\', $parts);
            }

            $traces[] = array(
                'namespace'   => $namespace,
                'short_class' => $class,
                'class'       => isset($entry['class']) ? $entry['class'] : '',
                'type'        => isset($entry['type']) ? $entry['type'] : '',
                'function'    => $entry['function'],
                'file'        => isset($entry['file']) ? $entry['file'] : null,
                'line'        => isset($entry['line']) ? $entry['line'] : null,
                'args'        => isset($entry['args']) ? $entry['args'] : array(),
            );
        }

        return $traces;
    }
}