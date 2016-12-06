<?php

namespace dejvidecz\Cronner\Tasks;

use DateTime;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @edited for Yii by David Sindelar
 */
final class Task {

    /**
     * @var object
     */
    private $object;

    /**
     * @var \dejvidecz\Cronner\Reflection\CronnerRMethod
     */
    private $method;

    /**
     * @var \dejvidecz\Cronner\TimestampStorage\ITimestampStorage
     */
    private $timestampStorage;

    /**
     * @var Parameters|null
     */
    private $parameters = NULL;

    /**
     * 
     * @param type $object
     * @param \dejvidecz\Cronner\Tasks\CronnerRMethod $method
     * @param \dejvidecz\Cronner\ITimestampStorage $timestampStorage
     */
    public function __construct($object, CronnerRMethod $method, \dejvidecz\Cronner\ITimestampStorage $timestampStorage) {
        $this->method = $method;
        $this->object = $object;
        $this->timestampStorage = $timestampStorage;
    }

    /**
     * Returns True if given parameters should be run.
     *
     * @param \DateTime $now
     * @return bool
     */
    public function shouldBeRun(DateTime $now = NULL) {
        if ($now === NULL) {
            $now = new DateTime;
        }


        $parameters = $this->getParameters();

        if (!$parameters->isTask()) {
            return FALSE;
        }
        $this->timestampStorage->setTaskName($parameters->getName());

        return $parameters->isInDay($now) && $parameters->isInTime($now) && $parameters->isNextPeriod($now, $this->timestampStorage->loadLastRunTime());
    }

    /**
     * Returns task name.
     *
     * @return string
     */
    public function getName() {
        return $this->getParameters()->getName();
    }

    public function __invoke(\DateTime $now) {
        $this->method->invoke($this->object);
        $this->timestampStorage->setTaskName($this->getName());
        $this->timestampStorage->saveRunTime($now);
        $this->timestampStorage->setTaskName();
    }

    /**
     * Returns instance of parsed parameters.
     *
     * @return Parameters
     */
    private function getParameters() {

        if ($this->parameters === NULL) {
            $this->parameters = new Parameters(Parameters::parseParameters($this->method));
        }

        return $this->parameters;
    }

}
