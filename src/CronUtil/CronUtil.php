<?php
namespace kyeates\CronUtil;

class CronUtil
{
    static $MAX_ITERATIONS = 50;

    public function humanReadable($cron)
    {
        return "not implemented";
    }

    /**
     * Given a cron expression find all the times it should fire. 
     * 
     * @param $cron
     *  cron object
     * @param $start 
     *  unix time stamp to start looking at
     * @param int $end
     *  unix timestamp to stop looking at
     * @param int $max
     *  max number of results we will return (for performance)
     * @return array
     *  array onf timestamps
     */
    public static function findAllMatching($cron, $start, $end = PHP_INT_MAX, $max = 50)
    {
        if ($max > self::$MAX_ITERATIONS) { $max = self::$MAX_ITERATIONS; }

        $result = array();

        //umm lets start one minute before start so we can include start?? right?
        $found = self::findNext($cron, $start - 60);
        $i = 0;
        while($found <= $end && $i < $max)
        {
            $i++;
            
            if (!$found) {
                break;
            }

            if (in_array(date('N', $found), $cron->getDaysOfWeek())) {
                $result[] = $found;
            }

            $found = self::findNext($cron, $found);
        }

        return $result;
    }

    /**
     * given a cron expression find the time it will fire closest to start
     * depricated?
     * @param $cron 
     *  Cron object
     * @param $start
     *  unix timestamp
     * @return int
     *  unix timestamp
     */
    function findFirst($cron, $start)
    {
        $minute = $this->findNextValueFromRange($cron->getMinutes(), date('i', $start) - 1);
        $hour = $this->findNextValueFromRange($cron->getHours(), date('H', $start) - 1);
        $dayOfMonth = $this->findNextValueFromRange($cron->getDaysOfMonth(), date('j', $start) - 1);
        $month = $this->findNextValueFromRange($cron->getMonths(), date('n', $start) - 1);
        $year = $this->findNextValueFromRange($cron->getYears(), date('Y', $start) - 1);


        return mktime($hour, $minute, 0, $month, $dayOfMonth, $year);
    }

    /**
     * Given a cron object find the time it will fire cloest to start.
     * 
     * this function will only increment one section at a time
     * @param $cron
     *  cron object
     * @param $start
     *  unix timestamp
     * @return int
     *  unix timestamp
     */
    public static function findNext($cron, $start)
    {
        //find all parts of the current time;
        $minute = date('i', $start);
        $minutes = $cron->getMinutes();
        $hour = date('H', $start);
        $hours = $cron->getHours();
        $dayOfMonth = date('j', $start);
        $daysOfMonth = $cron->getDaysOfMonth();
        $month = date('n', $start);
        $months = $cron->getMonths();
        $year = date('Y', $start);
        $years = $cron->getYears();

        //"find next =  $year/$month/$dayOfMonth $hour:$minute ($start)" );

        $nextMinute = self::findNextValueFromRange($minutes, $minute);
        //"next m: $nextMinute");
        if (!$nextMinute)
        {
            $minute = $minutes[0];
            $nextHour = self::findNextValueFromRange($hours, $hour);
            //"next h: $nextHour");
            if(!$nextHour)
            {
                $hour = $hours[0];
                $nextDayOfMonth = self::findNextValueFromRange($daysOfMonth,  $dayOfMonth);
                //"next d: $nextDayOfMonth");
                if (!$nextDayOfMonth)
                {
                    $dayOfMonth = $daysOfMonth[0];
                    $nextMonth = self::findNextValueFromRange($months, $month);
                    //"next mo: $nextMonth");
                    if (!$nextMonth)
                    {
                        $month = $months[0];
                        $nextYear = self::findNextValueFromRange($years, $year);
                        //"next y: $nextYear");
                        if (!$nextYear)
                        {
                            //'no next year found, THE END!!!');
                            return false;
                        }
                        else
                        {
                            $year = $nextYear;
                        }
                    }
                    else
                    {
                        $month = $nextMonth;
                    }
                }
                else
                {
                    $dayOfMonth = $nextDayOfMonth;
                }
            }
            else
            {
                $hour = $nextHour;
            }
        }
        else
        {
            $minute = $nextMinute;
        }

        $time = mktime($hour, $minute, 0, $month, $dayOfMonth, $year);
        //"found next =  $year/$month/$dayOfMonth $hour:$minute ($time)" );
        return $time;
    }

    public static function findNextValueFromRange($haystack, $needle)
    {
        //"find next value - needle: $needle haystack: " . sizeof($haystack) . " [" . implode(",", $haystack) . "]");
        for ($i = 0; $i < sizeof($haystack); $i++)
        {
            if ($haystack[$i] > $needle)
            {
                //"found next value: " . $haystack[$i]);
                return $haystack[$i];
            }
        }

        //"NOT found");
        return false;
    }
}
