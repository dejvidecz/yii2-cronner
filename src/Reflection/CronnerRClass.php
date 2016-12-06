<?php

namespace dejvidecz\Cronner\Tasks;

/**
 * Description of CronnerRMethod
 *
 * @author David
 */
class CronnerRClass extends \ReflectionClass {

    public function getMethod($name) {
        return new CronnerRMethod($this->getName(), parent::getMethod($name)->getName());
    }

    public function getMethods($filter = \ReflectionMethod::IS_PUBLIC) {
        $methods = parent::getMethods($filter);
        $ret = [];
        foreach ($methods as $method) {
            $ret[] = new CronnerRMethod($this->getName(), $method->getName());
        }
        return $ret;
    }

}
