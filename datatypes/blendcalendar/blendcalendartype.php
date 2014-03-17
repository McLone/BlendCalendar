<?php

/*!
  \class   blendcalendartype blendcalendartype.php
  \ingroup eZDatatype
  \brief   Handles the datatype gcalendar. By using gcalendar you can ...
  \version 1.0
  \date    Monday September 21 2009 01:14:46 pm
  \author  Joe Kepley

  

*/

include_once( "kernel/classes/ezdatatype.php" );

//require_once( 'extension/gcalendar/datatypes/gcalendar/gcalendar.php' );


class BlendCalendarType extends eZDataType
{

    const DATA_TYPE_STRING = "blendcalendar";
    /*!
      Constructor
    */
    function BlendCalendarType()
    {
        $this->eZDataType( self::DATA_TYPE_STRING, "Blend Calendar Event" );
    }
    
    /* **** CLASS SETTINGS **** */
    
    /* No default value for class settings
    function initializeClassAttribute( $classAttribute )
    {
    }
    */
    
    /* FetchClassAttributeHTTPInput handles storage - no extra processing needed
    function storeClassAttribute( $contentClassAttribute, $version )
    {
    }    
    */
    
    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
    }

    /*!
     \reimp
    */
    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
    }

    /*!
     \reimp
    */
    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
    }    
    
        
    
    /* **** OBJECT SETTINGS **** */

    /*!
     Validates input on content object level
     \return eZInputValidator::STATE_ACCEPTED or eZInputValidator::STATE_INVALID if
             the values are accepted or not
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $id = $contentObjectAttribute->attribute( 'id' );
        
        $recurType = $http->postVariable($base . '_recurtype_' . $id);
        $singleDate = $http->postVariable($base . '_singledate_' . $id);
        $weekdays = $http->postVariable($base . '_weekdays_' . $id);

        $monthRecurType = $http->postVariable($base . '_monthrecurtype_' . $id);
        $monthStaticRecurDate = $http->postVariable($base . '_monthstaticrecurdate_' . $id);
        $monthRelativeWeek = $http->postVariable($base . '_monthrelativeweek_' . $id);
        $monthRelativeDay = $http->postVariable($base . '_monthrelativeday_' . $id);

        $yearRecurType = $http->postVariable($base . '_yearrecurtype_' . $id);
        $yearStaticRecurMonth = $http->postVariable($base . '_yearstaticrecurmonth_' . $id);
        $yearStaticRecurDate = $http->postVariable($base . '_yearstaticrecurdate_' . $id);
        $yearRelativeWeek = $http->postVariable($base . '_yearrelativeweek_' . $id);
        $yearRelativeDay = $http->postVariable($base . '_yearrelativeday_' . $id);
        $yearRelativeMonth = $http->postVariable($base . '_yearrelativemonth_' . $id);

        $rangeStart = $http->postVariable($base . '_rangestart_' . $id);
        $rangeEndType = $http->postVariable($base . '_rangeendtype_' . $id);
        $rangeEnd = $http->postVariable($base . '_rangeend_' . $id);
        
        $timeStart = $http->postVariable($base . '_timestart_' . $id);
        $timeEnd = $http->postVariable($base . '_timeend_' . $id);
        $allDay = $http->postVariable($base . '_allday_' . $id);

        $interval = $http->postVariable($base . '_interval_' . $id);


        $locale = eZLocale::instance();
        //This section try to detect if we have an "european date style" (d/m/y) as locale and then replace separator
        //character to . so strtotime later know what to do.
        if ( preg_match( '#%d.%m.%Y#', $locale->ShortDateFormat ) ) //European Style
        {
            $singleDate = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1.$2.$3', $singleDate );
            $rangeStart = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1.$2.$3', $rangeStart );
            $rangeEnd = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1.$2.$3', $rangeEnd );
        }
        else //Assuming "American style" (m/d/y)
        {
            $singleDate = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1/$2/$3', $singleDate );
            $rangeStart = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1/$2/$3', $rangeStart );
            $rangeEnd = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1/$2/$3', $rangeEnd );
        }

        switch($recurType)
        {
            case 'ONCE':
                if(!strtotime($singleDate))
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                                              "Please enter a valid occurrence date." ) );
        
                    return eZInputValidator::STATE_INVALID;        
                }            
            break;
            case 'WEEK':
                if(!$weekdays)
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                                              "Please select at least one day of the week." ) );
        
                    return eZInputValidator::STATE_INVALID;        
                
                }
            break;

        }
                    
        if($recurType != 'ONCE')
        {
            $fromTimestamp = strtotime($rangeStart);
            $toTimestamp = strtotime($rangeEnd);
            
            if($fromTimestamp > $toTimestamp && $rangeEndType != 'NULL')
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                                          "The End Date must be before the Start Date." ) );
    
                return eZInputValidator::STATE_INVALID;        
            }
        }               
        return eZInputValidator::STATE_ACCEPTED;

    }

    /*!
     Retrieves HTTP Variables from edit form, saves data to DB
     \return true if fetching of class attributes are successfull, false if not
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {

        $id = $contentObjectAttribute->attribute( 'id' );
        $contentObjectId = $contentObjectAttribute->attribute( 'contentobject_id' );
        $contentClassAttributeId = $contentObjectAttribute->attribute( 'contentclassattribute_id' );
        $version = $contentObjectAttribute->attribute('version');
        $languageCode = $contentObjectAttribute->attribute('language_code');
        
        $recurType = $http->postVariable($base . '_recurtype_' . $id);
        $singleDate = $http->postVariable($base . '_singledate_' . $id);
        $weekdays = $http->postVariable($base . '_weekdays_' . $id);

        $monthRecurType = $http->postVariable($base . '_monthrecurtype_' . $id);
        $monthStaticRecurDate = $http->postVariable($base . '_monthstaticrecurdate_' . $id);
        $monthRelativeWeek = $http->postVariable($base . '_monthrelativeweek_' . $id);
        $monthRelativeDay = $http->postVariable($base . '_monthrelativeday_' . $id);

        $yearRecurType = $http->postVariable($base . '_yearrecurtype_' . $id);
        $yearStaticRecurMonth = $http->postVariable($base . '_yearstaticrecurmonth_' . $id);
        $yearStaticRecurDate = $http->postVariable($base . '_yearstaticrecurdate_' . $id);
        $yearRelativeWeek = $http->postVariable($base . '_yearrelativeweek_' . $id);
        $yearRelativeDay = $http->postVariable($base . '_yearrelativeday_' . $id);
        $yearRelativeMonth = $http->postVariable($base . '_yearrelativemonth_' . $id);

        $rangeStart = $http->postVariable($base . '_rangestart_' . $id);
        $rangeEndType = $http->postVariable($base . '_rangeendtype_' . $id);
        $rangeEnd = $http->postVariable($base . '_rangeend_' . $id);
        
        $timeStart = $http->postVariable($base . '_timestart_' . $id);
        $timeEnd = $http->postVariable($base . '_timeend_' . $id);
        $allDay = $http->postVariable($base . '_allday_' . $id);

        $interval = $http->postVariable($base . '_interval_' . $id);
        
        $startTime = null;
        $endTime = null;
        $duration = null;
        
        //echo "<pre>"; var_dump($_POST); echo "</pre>";
        
        if(!$allDay)
        {
            $startTime = strtotime($timeStart);
            
            //Convert to seconds since midnight
            $startTime = $startTime % 86400;
            
            if($timeEnd)
            {
                $endTime = strtotime($timeEnd);
                
                $endTime = $endTime % 86400;

                if($endTime < $startTime) //Assume the end time is on the next day if it's less than start (eg 11pm - 2am)
                {
                    $endTime += 86400;
                }
                
                $duration = $endTime - $startTime;
            }
        }
        
        if($recurType == 'MONTH')
        {
            $recurType = $monthRecurType;
        }
        
        if($recurType == 'YEAR')
        {
            $recurType = $yearRecurType;
        }
        
        $recur = null;
        //echo "<pre>"; var_dump($recurType); echo "</pre>";

        $locale = eZLocale::instance();
        //This section try to detect if we have an "european date style" (d/m/y) as locale and then replace separator
        //character to . so strtotime later know what to do.
        if ( preg_match( '#%d.%m.%Y#', $locale->ShortDateFormat ) ) //European Style
        {
            $singleDate = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1.$2.$3', $singleDate );
            $rangeStart = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1.$2.$3', $rangeStart );
            $rangeEnd = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1.$2.$3', $rangeEnd );
        }
        else //Assuming "American style" (m/d/y)
        {
            $singleDate = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1/$2/$3', $singleDate );
            $rangeStart = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1/$2/$3', $rangeStart );
            $rangeEnd = preg_replace( '/([0-9]+).([0-9]+).([0-9]+)/', '$1/$2/$3', $rangeEnd );
        }

        if ( $rangeEndType == 'NULL' )
        {
            $rangeEnd = null;
        }

        switch ($recurType)
        {
            case 'ONCE':
                $singleDate = strtotime($singleDate);
                
                $year = date('Y', $singleDate);
                $month = date('m', $singleDate);
                $day = date('d', $singleDate);
                
                $recur = new CalendarSingleOccurrence($month, $day, $year);
                
            break;
            
            case 'WEEK':
            
                $recur = new CalendarWeeklyRecurrence($weekdays, $rangeStart, $rangeEnd, $interval);
            
            break;
            
            case 'MONTHSTATIC':
            
                $recur = new CalendarMonthlyStaticRecurrence($monthStaticRecurDate, $rangeStart, $rangeEnd, $interval);
            
            break;
            
            case 'MONTHRELATIVE':
                $recur = new CalendarMonthlyRelativeRecurrence($monthRelativeWeek, array($monthRelativeDay), $rangeStart, $rangeEnd, $interval);
            break;

            case 'YEARSTATIC':
                $recur = new CalendarAnnualStaticRecurrence($yearStaticRecurMonth, $yearStaticRecurDate, $rangeStart, $rangeEnd, $interval);
            break;
                    
            case 'YEARRELATIVE':
                $recur = new CalendarAnnualRelativeRecurrence($yearRelativeMonth, $yearRelativeWeek, array($yearRelativeDay), $rangeStart, $rangeEnd, $interval);
            
            break;
            

        }
        
        
        $event = CalendarEvent::load($contentObjectId, $contentClassAttributeId, $version, $languageCode);

        if(!$event)
        {
            $event = new CalendarEvent($startTime, $duration, $recur);
            $event->contentObjectId = $contentObjectId;
            $event->contentClassAttributeId = $contentClassAttributeId;
            $event->version = $version;
            $event->languageCode = $languageCode;
        }
        else
        {
            $event->startTime = $startTime;
            $event->duration = $duration;
            $event->recurrence = $recur;
            $event->version = $version;
            $event->languageCode = $languageCode;
        }
        
        //echo "<pre>"; var_dump($event); echo "</pre>";
        $event->save();
        
    
        return true;
    }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $contentObjectId = $contentObjectAttribute->attribute( "contentobject_id" );
        $contentClassAttributeId = $contentObjectAttribute->attribute( "contentclassattribute_id" );
        $version = $contentObjectAttribute->attribute( "version" );
        $languageCode = $contentObjectAttribute->attribute( "language_code" );

        $eventObj = CalendarEvent::load($contentObjectId, $contentClassAttributeId, $version, $languageCode, false);
        
        if(!$eventObj)
        {
            $now = time();
            $hour = $now - ($now % CalendarRecurrence::HOUR);
            $recur = new CalendarSingleOccurrence(date('n',$now), date('j',$now), date('Y',$now));
            $eventObj = new CalendarEvent($hour, CalendarRecurrence::HOUR, $recur);
        }
        
        $event = $eventObj->toArray();

        return $event;
        
    }

    /*!
     Stores the datatype data to the database which is related to the
     object attribute.
     \return True if the value was stored correctly.
     \note The method is entirely up to the datatype, for instance
           it could reuse the available types in the the attribute or
           store in a separate object.
     \sa fetchObjectAttributeHTTPInput
    */
    function storeObjectAttribute( $objectAttribute )
    {
        $contentObjectId = $objectAttribute->attribute('contentobject_id');
        $contentClassAttributeId = $objectAttribute->attribute('contentclassattribute_id');
        $version = $objectAttribute->attribute('version');
        $languageCode = $objectAttribute->attribute('language_code');

        $eventObj = CalendarEvent::load($contentObjectId, $contentClassAttributeId, $version, $languageCode, false, true);
        if ( !$eventObj )
        {
            $eventObj = CalendarEvent::load($contentObjectId, $contentClassAttributeId, $version, $languageCode, false);
            if ( $eventObj )
            {
                $eventObj->version = $version;
                $eventObj->languageCode = $languageCode;

                $eventObj->save();
            }
        }
    }

    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        $contentObjectId = $contentObjectAttribute->attribute('contentobject_id');
        $contentClassAttributeId = $contentObjectAttribute->attribute('contentclassattribute_id');
        CalendarEvent::destroy($contentObjectId, $contentClassAttributeId, $version);
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $contentObjectAttribute )
    {
        return "";
    }

    /*!
     Returns the value as it will be shown if this attribute is used in the object name pattern.
    */
    function title( $contentObjectAttribute, $name = null )
    {
        return "";
    }

    /*!
     \return true if the datatype can be indexed
    */
    function isIndexable()
    {
        return true;
    }

}

eZDataType::register( BlendCalendarType::DATA_TYPE_STRING, "BlendCalendarType" );
?>
