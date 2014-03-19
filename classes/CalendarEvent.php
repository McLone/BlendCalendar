<?php

class CalendarEvent
{
    public $contentClassAttributeId;

    public $contentObjectId;
    
    public $version;

    public $languageCode;
    
    public $contentObject;
    
    public $startTime; //Seconds since midnight
    
    public $duration; //Seconds

    public $recurrence;
    
    
    const FETCH_EVENT = 1;
    const FETCH_LINEAR = 2;
    const FETCH_TIMEARRAY = 3;
    const FETCH_DAYS = 4;
    
    //Return a populated CalendarEvent object based on an oa id.
    public static function load($contentObjectId, $contentClassAttributeId, $version, $languageCode, $addObject = true, $strict = false)
    {
        $db = eZDB::instance();
        
        $sql = "SELECT * FROM BlendEvent WHERE contentobject_id=" . intval($contentObjectId) . " AND contentclassattribute_id=" . intval($contentClassAttributeId) . " AND version=" . intval($version) . " AND language_code='" . $db->escapeString( $languageCode ) . "'";
        //echo "[[$sql]]";
        $rows = $db->arrayQuery($sql);

        if(!$rows && !$strict)
        {
            $sql = "SELECT * FROM BlendEvent WHERE contentobject_id=" . intval($contentObjectId) . " AND contentclassattribute_id=" . intval($contentClassAttributeId) . " AND language_code='" . $db->escapeString( $languageCode ) . "' order by version DESC";
            //echo "[[$sql]]";

            $rows = $db->arrayQuery($sql);
            if ( !$rows )
            {
                $sql = "SELECT * FROM BlendEvent WHERE contentobject_id=" . intval($contentObjectId) . " AND contentclassattribute_id=" . intval($contentClassAttributeId) . " order by version DESC";
                //echo "[[$sql]]";

                $rows = $db->arrayQuery($sql);
            }
        }
        
        if(!$rows)
        {
            return false;
        }
        
        return self::createFromRow($rows[0], $addObject);

    }
       
    public static function destroy($contentObjectId, $contentClassAttributeId, $version = null)
    {
        $db = eZDB::instance();

        if ( !$version )
        {
            $sql = "DELETE FROM BlendEvent WHERE contentobject_id=" . intval($contentObjectId) . " AND contentclassattribute_id=" . intval($contentClassAttributeId);
        }
        else
        {
            $sql = "DELETE FROM BlendEvent WHERE contentobject_id=" . intval($contentObjectId) . " AND contentclassattribute_id=" . intval($contentClassAttributeId) . " AND version = " . intval($version);
        }

        $db->query($sql);

    }
    
    //Return an array of CalendarEvent objects that occur within a given range.
    //$startDate and $endDate are unix timestamps
    public static function getEventsInRange($contentClassAttributeIds, $startDate, $endDate, $filters = false, $parentNodeId=false, $subTree = false, $fetchType = self::FETCH_DAYS, $languageCode = false)
    {
    
        //echo "<pre>SD:". $startDate . '=' . date('Y-m-d H:i', $startDate); echo "</pre>";
        //echo "REM: " . ($startDate % CalendarRecurrence::DAY);
        //Sanitize the inputs to inclusive days
        //$startDate = $startDate - ($startDate % CalendarRecurrence::DAY);

        $endDate = $endDate - ($endDate % CalendarRecurrence::DAY);

        if ( !$languageCode )
        {
            $languageCode = eZINI::instance()->variable( 'RegionalSettings', 'ContentObjectLocale' );
        }

        //Get all events in the range
        $events = self::getUnfilteredRange($contentClassAttributeIds, $startDate, $endDate, $parentNodeId, $subTree, $languageCode);
        $days = array();

        //Check each day in the range
        for($day = $startDate; $day < $endDate; $day+=CalendarRecurrence::DAY)
        {
            if(date('H', $day)=='23') //Hack for November 1 DST conversion
            {
                $day += 3600;
            }
            
            if(date('H', $day)=='1')//Hack for Spring DST conversion
            {
                $day -= 3600;
            }
            
            foreach ($events as $event)
            {

                if($event->occursOnDate($day) && (!$filters || ($filters && $event->matchesFilters($filters))) )
                {
                    $eventInfo = $event->toArray();
                    
                    $eventInfo['occur_day']=$day;
                    $eventInfo['start_time']=$day + $event->startTime;
                    //echo "<pre>EV: "; var_dump($eventInfo); echo "</pre>";
                    switch($fetchType)
                    {   
                        case self::FETCH_LINEAR:
                        
                            $days[]=$eventInfo;
                        
                        break;
                    
                        default: //FETCH_DAYS
                        	if (!isset($days[$day])) {
	                            $days[$day]=array();
                        	}
                            $days[$day][$event->contentObjectId]=$eventInfo;
                    }
                }
            
            }
        }
        
//            echo "<hr /><pre>"; var_dump($contentObjects); echo "</pre>";
//            echo "<hr /><pre>"; var_dump($days); echo "</pre>";
        
        return $days;
        
    }

    //Return a list of CalendarEvent objects within a range, but return them all instead of examining calendar rules.
    protected static function getUnfilteredRange($contentClassAttributeIds, $startDate, $endDate, $parentNodeId = false, $subTree = false, $languageCode = false)
    {
        $db = eZDB::instance();
        
        $startDate = intval($startDate);
        
        $endDate = intval($endDate);
        
        if (!is_array($contentClassAttributeIds)) {
        	$contentClassAttributeIds = array($contentClassAttributeIds);
        }
        
        $cleanIds = array();
        
        foreach($contentClassAttributeIds as $id) {
        	$cleanIds[] = intval($id);
        }

        if ( !$languageCode )
        {
            $languageCode = eZINI::instance()->variable( 'RegionalSettings', 'ContentObjectLocale' );
        }

        $languageCodeCleaned = $db->escapeString( $languageCode );
        
        
        $fields = "*";
        $from = "BlendEvent e";
        $where ="1=1";
        $order = "ORDER BY e.start_time";

        //Date Range
        $where .= " AND (";
        
        $where .= "(e.range_start >= $startDate AND e.range_start <= $endDate)"; //Start dates within the range
        $where .= " OR ((e.range_end >= $startDate AND e.range_end <= $endDate ) OR e.range_end IS NULL)"; //End dates within the range
        $where .= " OR (e.range_start <= $startDate AND (e.range_end >= $endDate OR e.range_end IS NULL))"; //Events that encompass the whole range
        
        $where .= ")";        
        
        $from .= " INNER JOIN ezcontentobject o on (e.contentobject_id = o.id AND e.version = o.current_version)";
        $from .= " INNER JOIN ezcontentobject_tree n on n.contentobject_id = o.id";
        $where .= " AND e.contentclassattribute_id IN (" . implode(',',$cleanIds) . ") AND e.language_code = '$languageCodeCleaned'";

        if($parentNodeId)
        {
            $where .= " AND n.parent_node_id = " . intval($parentNodeId);
        }

        if($subTree)
        {
            $node = eZContentObjectTreeNode::fetch( $subTree, false, false );
            if ( is_array( $node ) )
            {
                $nodePath   = $node['path_string'];            
                $where .= " AND n.path_string LIKE '$nodePath%'";
            }
        }
        
        
        $objs = array();

        $sql = "SELECT $fields FROM $from WHERE $where $order";
//echo "[$sql]";
        $rows = $db->arrayQuery($sql);

        foreach($rows as $row)
        {
        
            $obj = self::createFromRow($row);
            
            if($obj)
            {
                $objs[]=$obj;
            }
        }
        
        return $objs;
    
    }
    
    //Return a populated CalendarEvent object from a db row
    public static function createFromRow($row, $addObject = true)
    {
        $recur = CalendarRecurrence::createFromRow($row);
        $ev = new CalendarEvent($row['start_time'], $row['duration'], $recur);
        
        $ev->contentObjectId = $row['contentobject_id'];
        $ev->contentClassAttributeId = $row['contentclassattribute_id'];
        $ev->version = $row['version'];
        $ev->languageCode = $row['language_code'];

        if($addObject && array_key_exists('contentobject_id', $row))
        {
            $ev->contentObjectId = $row['contentobject_id'];
            $ev->contentObject = new eZContentObject($row);
        }
        
        return $ev;
    }


    public function __construct($startTime, $duration, $recurrence)
    {
        $this->startTime = $startTime;
        $this->duration = $duration;
        $this->recurrence = $recurrence;
    }


    /** 
     * Checks this event's contentobject against provided filters, and returns true if it's a match
     * Filters are in the form of: 
     * array( '[AND]', array('[ATTRIBUTE]','[OPERATOR]','[VALUE]'), array(), array(), ...)
     * Valid entries for '[AND]': AND,OR
     * Valid entries for '[ATTRIBUTE]': any attribute from the object (comparison is on the attribute's 'content' property)
     * Valid entries for '[OPERATOR]': '=', 'LIKE'
     */
    public function matchesFilters($filters)
    {
    //echo "<pre>"; var_dump($filters); echo "</pre>";
        $andOr = strtoupper(array_shift($filters));
        
        $match = false;
        
        if($andOr == 'AND' || !$andOr)
        {
            $match = true;
        }

        $dataMap = array();

        if($filters)
        {
            $dataMap = $this->contentObject->dataMap();
        }

        foreach($filters as $filter)
        {
            $attribName = $filter[0];
            $operator = strtoupper($filter[1]);
            $value = $filter[2];
            
            $attrib = $dataMap[$attribName];

            if($attrib)
            {
                $content = $attrib->attribute('content');
                
                if(is_array($content))
                {
                    $content = $content[0];
                }

                $compare = false;

                switch($operator)
                {
                    case '=':
                        $compare = $content == $value;
                    break;
                    case 'LIKE':
                        $compare = stripos(' ' . $content, $value);
                    break;                
                }
                
                if($andOr == 'OR' && $compare)
                {
                    $match = true;
                    break; //We can stop after the first positive match
                }
                
                if($andOr == 'AND' && !$compare )
                {
                    $match = false;
                    break; //We can stop on the first negative match
                
                }
                
            }
        
        }
        
        return $match;
    }

    

    
    //Return a boolean based on either a unix timestamp or m/d/y
    public function occursOnDate($monthOrTime, $day = 0, $year = 0)
    {
        $date = intval($monthOrTime);
        
        if($monthOrTime <= 12)
        {
            $date = mktime(0,0,0,$monthOrTime, $day, $year);
        }
        
        return $this->recurrence->occursOnDate($date);
    }
    
    //Persists changes to the object
    public function save()
    {
    
        $db = eZDB::instance();
        
        $fields = $this->toArray(false);

        $languageCode = $fields['language_code'];
        unset( $fields['language_code'] );

        $sql = "REPLACE INTO BlendEvent (`language_code`,`" . implode('`,`',array_keys($fields)) . "`) VALUES ('" . $languageCode . "'," . implode(', ', array_values($fields)) . ")";
        
        //echo "[[$sql]]";
        $db->query($sql);
        
    }
    
    public function toArray($display = true)
    {
        $fields = array(
            'contentobject_id'=>intval($this->contentObjectId),
            'contentclassattribute_id'=>intval($this->contentClassAttributeId),
            'version'=>intval($this->version),
            'language_code'=>$this->languageCode,
            'start_time'=>intval($this->startTime),
            'duration'=>intval($this->duration)
            );
            
        $fields = array_merge($fields, $this->recurrence->toArray($display));
        
        if($display)
        {
            $locale = eZLocale::instance();
            $hourFormat = eZLocale::transformToPHPFormat( $locale->ShortTimeFormat, array( 'g', 'i', 'a', 'H', 'h' ) );
            $referenceTime = mktime( 0, 0, 0 );
            $fields['object']=$this->contentObject;
            $fields['start_time_local']=date($hourFormat,$this->startTime + $referenceTime);
            $fields['end_time_local']=date($hourFormat,$this->startTime + $this->duration + $referenceTime);
            $fields['all_day']=$this->startTime ? false : true;

        }
        
        return $fields;        
    }
    
}

?>