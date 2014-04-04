<?php


class CalendarWeeklyRecurrence extends CalendarRecurrence
{

    public $sunday;
    public $monday;
    public $tuesday;
    public $wednesday;
    public $thursday;
    public $friday;
    public $saturday;
    
    public $days;
    
    
    //Provide an array of days
    public function __construct($days, $rangeStart, $rangeEnd, $interval = false)
    {
        $this->recurType = self::RECUR_TYPE_WEEKLY;
        
        $allDays = array('sunday', 'monday','tuesday','wednesday','thursday','friday','saturday');
        
        if($days)
        {
            foreach($days as $dayNum)
            {
                $dayName = $allDays[$dayNum];
                $this->$dayName = 1;
            }
            
            $this->days = $days;
        }
        else
        {
            $this->days=array();            
        }
                
        parent::__construct($rangeStart, $rangeEnd, $interval);
    }
    
    public function occursOnDate($date)
    {
        
        $day = date('w',$date);

        $match = in_array($day, $this->days);
        
        $match = $match && ($this->rangeStart <= $date);
        
        if($this->interval && $this->interval > 1)
        {
            //How many weeks has it been since the start date?
            $week = self::DAY * 7;

            if ( eZINI::instance()->hasVariable('BlendCalendarSettings', 'ForceStartDayOfWeekForIntervaledRecurrence') )
            {
                $firstDayOfWeek =  eZINI::instance()->variable('BlendCalendarSettings', 'ForceStartDayOfWeekForIntervaledRecurrence');
            }
            else
            {
                $firstDayOfWeek = date('w', $this->rangeStart);
            }

            //Make the first day of week to be the first day of recurrence :
            //TODO I can't actually explain why the "+ 1" do the trick, but it does for me... may be some timezone issue to fix ?
            $dayWeekOffset = (date('w', 0) - $firstDayOfWeek + 1) * self::DAY;
            $startWeek = floor(($this->rangeStart + $dayWeekOffset) / $week);
            $thisWeek = floor(($date + $dayWeekOffset) / $week);
            
            $intervalWeek = ($thisWeek - $startWeek) % $this->interval;
            
            $match = $match && ($intervalWeek == 0);
        }
        
        
        if($this->rangeEnd)
        {
            $match = $match && ($this->rangeEnd >= $date);
        }
        
        return $match;
    
    }
    


}


?>