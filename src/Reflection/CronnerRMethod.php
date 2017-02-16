<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace dejvidecz\Cronner\Reflection;

/**
 * Description of CronnerRMethod
 *
 * @author David
 */
class CronnerRMethod extends \ReflectionMethod {

    /**
     * @param $name
     * @return bool
     */
    public function hasAnnotation($name) {
        $annotations = $this->getAllAnnotations();
        foreach ($annotations as $annotation) {
            if (\yii\helpers\BaseStringHelper::startsWith($annotation, $name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $name
     * @return null|string
     */
    public function getAnnotation($name) {
        $annotations = $this->getAllAnnotations();
        foreach ($annotations as $annotation) {
            if (\yii\helpers\BaseStringHelper::startsWith($annotation, $name)) {
                return ltrim(substr($annotation, strlen($name)));
            }
        }
        return null;
    }

    /**
     * @return mixed
     */
    private function getAllAnnotations() {
        $doc = $this->getDocComment();
        preg_match_all('#@(.*?)\n#s', $doc, $annotations);
        return $annotations[1];
    }

}
