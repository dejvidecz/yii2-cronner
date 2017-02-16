<?php

namespace dejvidecz\Cronner;

/**
 * Description of Cronner
 *
 * @author David
 */
class Cronner {

    /**
     * @var int Max execution time of PHP script in seconds
     */
    private $maxExecutionTime;
    
    private $tasks;

    public function __construct() {
        if(!isset(\Yii::$app->params['cronner']['database'])){
            throw new \Exception("Database table is not set in config params file");
        }
        if(!isset(\Yii::$app->params['cronner']['tasks'])){
            throw new \Exception("No task is defined");
        }        
        $tasks = \Yii::$app->params['cronner']['tasks'];
        
        $fileStorage = new \stekycz\Cronner\TimestampStorage\DatabaseStorage();
        foreach ($tasks as $task) {
            $reflection = new Tasks\CronnerRClass($task);
            foreach ($reflection->getMethods() as $method) {
                $this->tasks[] = new Tasks\Task(new $task, $method, $fileStorage);
            }
        }
    }

    public function run() {
        $now = new \DateTime();
        if ($this->maxExecutionTime !== NULL) {
            set_time_limit((int) $this->maxExecutionTime);
        }
        foreach ($this->tasks as $task) {
            try {
                $name = $task->getName();
                if ($task->shouldBeRun($now)) {
                    $task($now);
                }
            } catch (\Exception $ex) {
                try {
                    $filePath = \Yii::$app->getRuntimePath() . '/cronner/log/error.log';
                    if (!is_dir(dirname($filePath))) {
                        mkdir(dirname($filePath));
                    }
                    $file = fopen($filePath, 'a');
                    $now = new \DateTime;
                    $txt = $now->format('Y-m-d H:i:s') . " - Error message: " . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString() . PHP_EOL;
                    fwrite($file, $txt);
                    fclose($file);
                } catch (\Exception $ex) {
                    //bad day :(
                }
            }
        }
    }

}
