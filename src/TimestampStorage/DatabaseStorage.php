<?php

namespace stekycz\Cronner\TimestampStorage;

use DateTime;
use dejvidecz\Cronner\ITimestampStorage;

/**
 * @author David Sindelar 
 */
class DatabaseStorage implements ITimestampStorage {

    /**
     * @var string|NULL
     */
    private $taskName = NULL;

    /**
     * Sets name of current task.
     *
     * @param string|null $taskName
     */
    public function setTaskName($taskName = NULL) {
        if ($taskName !== NULL && (!$taskName || !is_string($taskName) || strlen($taskName) <= 0)) {
            throw new \Exception('Given task name is not valid.');
        }
        $this->taskName = $taskName;
    }

    /**
     * Saves current date and time as last invocation time.
     *
     * @param \DateTime $now
     */
    public function saveRunTime(DateTime $now) {
        $taskName = sha1($this->taskName);
        $row = DatabaseModel::find()->where(['method' => $taskName])->exists();

        if ($row) {
            $dataRow = DatabaseModel::find()->where(['method' => $taskName])->one();
        } else {
            $dataRow = new DatabaseModel();
            $dataRow->method = $taskName;
        }
        $dataRow->timestamp = $now->getTimestamp();
        $dataRow->save();
    }

    /**
     * Returns date and time of last cron task invocation.
     *
     * @return \DateTime|null
     */
    public function loadLastRunTime() {
        $date = NULL;

        $taskName = sha1($this->taskName);
        $row = DatabaseModel::find()->where(['method' => $taskName])->exists();
        if ($row) {
            $dataRow = DatabaseModel::find()->where(['method' => $taskName])->one();
        } else {
            return null;
        }
        $date = new \DateTime;
        $date->setTimestamp($dataRow->timestamp);
        return $date ? $date : NULL;
    }

}

class DatabaseModel extends \dlds\giixer\components\GxActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return \Yii::$app->params['cronner']['database'];
    }

}
