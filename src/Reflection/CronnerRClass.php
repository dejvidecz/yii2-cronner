<?php

namespace dejvidecz\Cronner\Reflection;

/**
 * Description of CronnerRMethod
 *
 * @author David
 */
class CronnerRClass extends \ReflectionClass {

    /**
     * @param string $name
     * @return CronnerRMethod
     */
    public function getMethod($name) {
        return new CronnerRMethod($this->getName(), parent::getMethod($name)->getName());
    }

    /**
     * @param int|null|string $filter
     * @return array
     */
    public function getMethods($filter = \ReflectionMethod::IS_PUBLIC) {
        $methods = parent::getMethods($filter);
        $ret = [];
        foreach ($methods as $method) {
            $ret[] = new CronnerRMethod($this->getName(), $method->getName());
        }
        return $ret;
    }

}
