<?php

namespace dejvidecz\Cronner\Tasks;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @edited for Yii by David Sindelar
 */
class Parser {

    /**
     * Parses name of cron task.
     *
     * @param string $annotation
     * @return string|null
     */
    public static function parseName($annotation) {
        $name = NULL;
        if (is_string($annotation) && strlen($annotation)) {
            $name = trim($annotation);
            $name = strlen($name) ? $name : NULL;
        }
        return $name;
    }

    /**
     * Parses period of cron task. If annotation is invalid throws exception.
     *
     * @param string $annotation
     * @return string|null
     * @throws \Exception
     */
    public static function parsePeriod($annotation) {
        $period = NULL;
        static::checkAnnotation($annotation);
        $annotation = trim($annotation);
        if (strlen($annotation)) {
            if (strtotime('+ ' . $annotation) === FALSE) {
                throw new \Exception("Given period parameter '" . $annotation . "' must be valid for strtotime() with '+' sign as its prefix (added by Cronner automatically).");
            }
            $period = $annotation;
        }

        return $period ?: NULL;
    }

    /**
     * Parses allowed days for cron task. If annotation is invalid
     * throws exception.
     *
     * @param string $annotation
     * @return string[]|null
     * @throws \Exception
     */
    public static function parseDays($annotation) {
        static $validValues = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',);

        $days = NULL;
        static::checkAnnotation($annotation);
        $annotation = trim($annotation);
        if (strlen($annotation)) {
            $days = static::translateToDayNames($annotation);
            $days = static::expandDaysRange($days);
            foreach ($days as $day) {
                if (!in_array($day, $validValues)) {
                    throw new \Exception(
                    "Given day parameter '" . $day . "' must be one from " . implode(', ', $validValues) . "."
                    );
                }
            }
            $days = array_values(array_intersect($validValues, $days));
        }

        return $days ?: NULL;
    }

    /**
     * Parses allowed time ranges for cron task. If annotation is invalid
     * throws exception.
     *
     * @param string $annotation
     * @return string[][]|null
     */
    public static function parseTimes($annotation) {
        $times = NULL;
        static::checkAnnotation($annotation);
        $annotation = trim($annotation);
        if (strlen($annotation)) {
            if ($values = static::splitMultipleValues($annotation)) {
                $times = array();
                foreach ($values as $time) {
                    $times = array_merge($times, static::parseOneTime($time));
                }
                usort($times, function ($a, $b) {
                    return $a < $b ? -1 : ($a > $b ? 1 : 0);
                });
            }
        }

        return $times ?: NULL;
    }

    /**
     * Translates given annotation to day names.
     *
     * @param string $annotation
     * @return string[]
     */
    private static function translateToDayNames($annotation) {
        static $workingDays = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri',);
        static $weekend = array('Sat', 'Sun',);

        $days = array();
        foreach (static::splitMultipleValues($annotation) as $value) {
            switch ($value) {
                case 'working days':
                    $days = array_merge($days, $workingDays);
                    break;
                case 'weekend':
                    $days = array_merge($days, $weekend);
                    break;
                default:
                    $days[] = $value;
                    break;
            }
        }

        return array_unique($days);
    }

    /**
     * Expands given day names and day ranges to day names only. The day range must be
     * in "Mon-Fri" format.
     *
     * @param string[] $days
     * @return string[]
     */
    private static function expandDaysRange(array $days) {
        static $dayNames = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',);
        $expandedValues = array();

        foreach ($days as $day) {
            if (preg_match('~^\w{3}\s*-\s*\w{3}$~u', $day)) {
                list($begin, $end) = preg_split('~\s*-\s*~', $day);
                $started = FALSE;
                foreach ($dayNames as $dayName) {
                    if ($dayName === $begin) {
                        $started = TRUE;
                    }
                    if ($started) {
                        $expandedValues[] = $dayName;
                    }
                    if ($dayName === $end) {
                        $started = FALSE;
                    }
                }
            } else {
                $expandedValues[] = $day;
            }
        }

        return array_unique($expandedValues);
    }

    /**
     * Splits given annotation by comma into array.
     *
     * @param $annotation
     * @return string[]
     */
    private static function splitMultipleValues($annotation) {
        return preg_split('/\s*,\s*/', $annotation);
    }

    /**
     * Returns True if time in valid format is given, False otherwise.
     *
     * @param string $time
     * @return bool
     */
    private static function isValidTime($time) {
        return (bool) preg_match('/^\d{2}:\d{2}$/u', $time);
    }

    /**
     * Parses one time annotation. If it is invalid throws exception.
     *
     * @param string $time
     * @return string[][]
     * @throws \Exception
     */
    private static function parseOneTime($time) {
        $time = static::translateToTimes($time);
        $parts = preg_split('/\s*-\s*/', $time);
        if (!static::isValidTime($parts[0]) || (isset($parts[1]) && !static::isValidTime($parts[1]))) {
            throw new \Exception(
            "Times annotation is not in valid format. It must looks like 'hh:mm[ - hh:mm]' but '" . $time . "' was given."
            );
        }
        $times = array();
        if (static::isTimeOverMidnight($parts[0], isset($parts[1]) ? $parts[1] : NULL)) {
            $times[] = static::timePartsToArray('00:00', $parts[1]);
            $times[] = static::timePartsToArray($parts[0], '23:59');
        } else {
            $times[] = static::timePartsToArray($parts[0], isset($parts[1]) ? $parts[1] : NULL);
        }

        return $times;
    }

    /**
     * Translates given annotation to day names.
     *
     * @param string $time
     * @return string[]
     */
    private static function translateToTimes($time) {
        static $translationMap = array(
            'morning' => '06:00 - 11:59',
            'noon' => '12:00 - 12:29',
            'afternoon' => '12:30 - 16:59',
            'evening' => '17:00 - 21:59',
            'night' => '22:00 - 05:59',
            'midnight' => '00:00 - 00:29',
        );

        return array_key_exists($time, $translationMap) ? $translationMap[$time] : $time;
    }

    /**
     * Returns True if given times includes midnight, False otherwise.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    private static function isTimeOverMidnight($from, $to) {
        return $to !== NULL && $to < $from;
    }

    /**
     * Returns array structure with given times.
     *
     * @param string $from
     * @param string $to
     * @return array
     */
    private static function timePartsToArray($from, $to) {
        return array(
            'from' => $from,
            'to' => $to,
        );
    }

    /**
     * Checks if given annotation is valid. Throws exception if not.
     *
     * @param string $annotation
     * @throws \Exception
     */
    private static function checkAnnotation($annotation) {
        if (!is_string($annotation)) {
            throw new \Exception(
            "Cron task annotation must be string but '" .
            !is_bool($annotation) && is_object($annotation) ? get_class($annotation) : gettype($annotation) . "' given."
            );
        }
    }

}
