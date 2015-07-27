<?php
namespace kyeates\CronUtil;

/**
 * Created by JetBrains PhpStorm.
 * User: Kieran
 * Date: 22/01/13
 * Time: 7:52 AM
 * To change this template use File | Settings | File Templates.
 */
class Cron
{
    private $expression;
    private $minuteExpression;
    private $hourExpression;
    private $dayOfMonthExpression;
    private $monthExpression;
    private $dayOfWeekExpression;
    private $yearExpression;

    private $minutes = array();
    private $hours = array();
    private $daysOfMonth = array();
    private $months = array();
    private $daysOfWeek = array();
    private $years = array();

    public static $CRON_PART_SEPERATOR = ' ';
    public static $ALL = '*';
    public static $SEPERATOR = ',';
    public static $INCREMENT = '/';
    public static $RANGE = '-';
    public static $ANY = '?';
    public static $WEEKDAY = 'W';
    public static $LAST_DAY = 'L';
    public static $WEEK_NUMBER = '#';
    public static $WEEK_INCREMENT = '|';

    private $validCron = array();

    private $errors = array();

    public function getErrors() { return $this->errors; }
    public function isValid() {
        $errors = $this->getErrors();
        return empty($errors);
    }

    public function getMinutes() { return $this->minutes; }
    public function getHours() { return $this->hours; }
    public function getDaysOfMonth() { return $this->daysOfMonth; }
    public function getMonths() { return $this->months; }
    public function getDaysOfWeek() { return $this->daysOfWeek; }
    public function getYears() { return $this->years; }

    public function getMinuteExpression() { return $this->minuteExpression; }
    public function getHourExpression() { return $this->hourExpression; }
    public function getDayOfMonthExpression() { return $this->dayOfMonthExpression; }
    public function getMonthExpression() { return $this->monthExpression; }
    public function getDayOfWeekExpression() { return $this->dayOfWeekExpression; }
    public function getYearExpression() { return $this->yearExpression; }

    public function getValidCron() { return $this->validCron; }

    public function __construct($exp)
    {
        //CronUtil construct - exp: ' . $exp);
        $this->validCron = array
        (
            "minute" => array
            (
                "id" => 0,
                "allowedSpecialCharacters" => array(Cron::$ALL, Cron::$SEPERATOR, Cron::$INCREMENT, Cron::$RANGE),
                "min" => 0,
                "max" => 59
            ),
            "hour" => array
            (
                "id" => 1,
                "allowedSpecialCharacters" => array(Cron::$ALL, Cron::$SEPERATOR, Cron::$INCREMENT, Cron::$RANGE),
                "min" => 0,
                "max" => 23
            ),
            "dayOfMonth" => array
            (
                "id" => 2,
                "allowedSpecialCharacters" => array(Cron::$ALL, Cron::$SEPERATOR, Cron::$INCREMENT, Cron::$RANGE, Cron::$ANY, Cron::$LAST_DAY, Cron::$WEEKDAY),
                "min" => 1,
                "max" => 31
            ),
            "month" => array
            (
                "id" => 3,
                "allowedSpecialCharacters" => array(Cron::$ALL, Cron::$SEPERATOR, Cron::$INCREMENT, Cron::$RANGE),
                "named" => array('JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'),
                "min" => 1,
                "max" => 12
            ),
            "dayOfWeek" => array
            (
                "id" => 4,
                "allowedSpecialCharacters" => array(Cron::$ALL, Cron::$SEPERATOR, Cron::$INCREMENT, Cron::$RANGE, Cron::$ANY, Cron::$LAST_DAY, Cron::$WEEK_NUMBER, Cron::$WEEK_INCREMENT),
                "named" => array('SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'),
                "min" => 0,
                "max" => 6
            ),
            "year" => array
            (
                "id" => 5,
                "allowedSpecialCharacters" => array(Cron::$ALL, Cron::$SEPERATOR, Cron::$INCREMENT, Cron::$RANGE),
                "min" => 1970,
                "max" => 2099
            )
        );

        $this->expression = $exp;

        $valid = $this->validateAndSeperateCron();
        if (!$valid)
            return;

        $this->minutes = $this->getValuesFromExpression($this->minuteExpression,  $this->validCron['minute']);
        $this->hours = $this->getValuesFromExpression($this->hourExpression,  $this->validCron['hour']);
        $this->daysOfMonth = $this->getValuesFromExpression($this->dayOfMonthExpression,  $this->validCron['dayOfMonth']);
        $this->months = $this->getValuesFromExpression($this->monthExpression,  $this->validCron['month']);
        $this->daysOfWeek = $this->getValuesFromExpression($this->dayOfWeekExpression,  $this->validCron['dayOfWeek']);
        $this->years = $this->getValuesFromExpression($this->yearExpression,  $this->validCron['year']);
    }

    private function validateAndSeperateCron()
    {
        if ($this->expression == '' || $this->expression == null)
        {
            $this->errors[] = 'No cron expression found';
            return;
        }
        $parts = explode(Cron::$CRON_PART_SEPERATOR, $this->expression);
        if (sizeOf($parts) < 5 || sizeOf($parts) > 6)
        {
            $this->errors[] = 'a cron expression should be made up of 5 or 6 parts seperated by a space';
            return false;
        }

        $this->minuteExpression = $this->validateExpressionPart($parts[0], $this->validCron['minute']);
        $this->hourExpression = $this->validateExpressionPart($parts[1], $this->validCron['hour']);
        $this->dayOfMonthExpression = $this->validateExpressionPart($parts[2], $this->validCron['dayOfMonth']);
        $this->monthExpression = $this->validateExpressionPart($parts[3], $this->validCron['month']);
        $this->dayOfWeekExpression = $this->validateExpressionPart($parts[4], $this->validCron['dayOfWeek']);

        if (isset($parts[5]))
            $this->yearExpression = $this->validateExpressionPart($parts[5], $this->validCron['year']);
        else
            $this->yearExpression = Cron::$ALL;

        if (sizeOf($this->errors) > 0)
            return false;

        return true;
    }

    private function validateExpressionPart($exp, $details)
    {
        foreach($details["allowedSpecialCharacters"] as $character)
        {

        }

        return $exp;
    }

    private function getValuesFromExpression($expression, $details)
    {
        ////getValuesFromExpression, exp: ' . $expression .  ' - details:' . $details);
        $result = array();

        if (is_numeric($expression))
            return array($expression);

        if (isset($details['named']))
        {
            foreach($details['named'] as $key => $value)
            {
                if ($expression == $value)
                    return array($key);
            }

        }
        foreach($details["allowedSpecialCharacters"] as $character)
        {
            $parts = explode($character, $expression);
            if (sizeOf($parts) == 1) //character not in expression
                continue;

            switch ($character)
            {
                case Cron::$ALL:
                case Cron::$ANY:
                    for($i = $details['min']; $i <= $details['max']; $i++)
                    {
                        $result[] = $i;
                    }
                    break;
                case Cron::$SEPERATOR:
                    //get value from exp: seperator found');
                    foreach($parts as $part)
                    {
                        $result = array_merge($result, $this->getValuesFromExpression($part, $details));
                    }
                    break;
                case Cron::$RANGE:
                    //get value from exp: range found - start: ' . $parts[0] . ' - max: '  .$details['max']);
                    for($i = $parts[0]; $i <= $parts[1]; $i++)
                    {
                        $result[] = $i;
                    }
                    break;
                case Cron::$INCREMENT:
                    //get value from exp: increment found - start: ' . $parts[0] . ' - max: '  .$details['max']);
                    for($i = $parts[0]; $i < $details['max']; $i+=$parts[1])
                    {
                        $result[] = $i;
                    }
                    break;
                case Cron::$WEEK_INCREMENT: //this mostly needs context to be evaluated, so handeld in CronUtils when we have a start/end date
                    $result[] = $parts[0]; //just get the specific day
                    break;
            }

            //seperate expression, found ['  . count($result) . ']');

            if (count($result) > 0)
                return $result;
        }


        return $result;
    }
}
