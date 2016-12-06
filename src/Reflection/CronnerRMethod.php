<?php

namespace dejvidecz\Cronner\Tasks;

/**
 * Description of CronnerRMethod
 *
 * @author David
 */
class CronnerRMethod extends \ReflectionMethod {

    public function hasAnnotation($name) {
        $annotations = $this->getAllAnnotations();
        foreach ($annotations as $annotation) {
            if (\yii\helpers\BaseStringHelper::startsWith($annotation, $name)) {
                return true;
            }
        }
        return false;
    }

    public function getAnnotation($name) {
        $annotations = $this->getAllAnnotations();
        foreach ($annotations as $annotation) {
            if (\yii\helpers\BaseStringHelper::startsWith($annotation, $name)) {
                return ltrim(substr($annotation, strlen($name)));
            }
        }
        return null;
    }

    private function getAllAnnotations() {
        $doc = $this->getDocComment();
        preg_match_all('#@(.*?)\n#s', $doc, $annotations);
        return $annotations[1];
    }

}
