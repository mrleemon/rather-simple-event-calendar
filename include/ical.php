<?php

    function utf8_bytecount($str) {
      return count(str_split($str));
    }
 
    // Takes a UTF-8 string and a byte index into that string, and
    // returns the byte index of the next UTF-8 sequence. When the end
    // of the string is encountered, the function returns NULL
    function utf8_next_index($str, $start) {
        $ret = NULL;
  
        $i = $start;
  
        if (isset($str)) {
          if (ord($str[$i]) < 0xc0) {
            $i++;
          } else {
            $i++;
            while ((ord($str[$i]) & 0xc0) == 0x80) {
              $i++;
            }
          }
          if (isset($str[$i]) && (ord($str[$i]) != 0)) {
            $ret = $i;
          }
        }
        return $ret;
    }
 
    // Given a UTF-8 string and a byte index, return the UTF-8 sequence
    // at this index as a string, and update the byte index to point to
    // the next sequence. When the end of the string is encountered, the
    // last sequence is returned, and the byte index set to NULL
    function utf8_seq($str, &$byte_index) {
        $ret = "."; // dummy to fool PHP
  
        $next = utf8_next_index($str, $byte_index);
  
        if (isset($next)) {
          $j = 0;
          for ($i = $byte_index; $i < $next; $i++) {
            $ret[$j] = $str[$i];
            $j++;
          }
        } else {
          $j = 0;
          for ($i = $byte_index; isset($str[$i]) && (ord($str[$i]) != 0); $i++) {
            $ret[$j] = $str[$i];
            $j++;
          }
        }
        $byte_index = $next;
        return $ret;
    }
 
    function ical_escape($str) {
      // Escape '\'
      $str = str_replace("\\", "\\\\", $str);
      // Escape ';'
      $str = str_replace(";", "\;", $str);
      // Escape ','
      $str = str_replace(",", "\,", $str);
      // EOL can only be \n
      $str = str_replace("\r\n", "\n", $str);
      // Escape '\n'
      $str = str_replace("\n", "\\n", $str);
      return $str;
    }

    // "Folds" lines longer than 75 octets.  Multi-byte safe.
    //
    // "Lines of text SHOULD NOT be longer than 75 octets, excluding the line
    // break.  Long content lines SHOULD be split into a multiple line
    // representations using a line "folding" technique.  That is, a long
    // line can be split between any two characters by inserting a CRLF
    // immediately followed by a single linear white-space character (i.e.,
    // SPACE or HTAB).  Any sequence of CRLF followed immediately by a
    // single linear white-space character is ignored (i.e., removed) when
    // processing the content type."  (RFC 5545)
    function ical_fold($prop, $value) {
        $line_split = "\r\n ";  // The RFC also allows a tab instead of a space
        $space_octets = utf8_bytecount(' ');  // Could be two bytes if we're using UTF-16
        $octets_max = 75;
  
        $result = '';
        $octets = 0;
        $byte_index = 0;
  
        $str = $prop . ':' . $value;
        
        while (isset($byte_index)) {
          // Get the next character
          $prev_byte_index = $byte_index;
          $char = utf8_seq($str, $byte_index);

          $char_octets = $byte_index - $prev_byte_index;
          // If it's a CR then look ahead to the following character, if there is one
          if (($char == "\r") && isset($byte_index)) {
            $this_byte_index = $byte_index;
            $next_char = utf8_seq($str, $byte_index);
            // If that's a LF then take the CR, and advance by one character
            if ($next_char == "\n") {
              $result .= $char;    // take the CR
              $char = $next_char;  // advance by one character
              $octets = 0;         // reset the octet counter to the beginning of the line
              $char_octets = 0;    // and pretend the LF is zero octets long so that after
                             // we've added it in we're still at the beginning of the line
            } else {
              $byte_index = $this_byte_index;
            }
          }
          // otherwise if this character will take us over the octet limit for the line,
          // fold the line and set the octet count to however many octets a space takes
          // (the folding involves adding a CRLF followed by one character, a space or a tab)
          //
          // [Note:  It's not entirely clear from the RFC whether the octet that is introduced
          // when folding counts towards the 75 octets.   Some implementations (eg Google
          // Calendar as of Jan 2011) do not count it.   However it can do no harm to err on
          // the safe side and include the initial whitespace in the count.]
          elseif (($octets + $char_octets) > $octets_max) {
            $result .= $line_split;
            $octets = $space_octets;
          }
          // finally add the character to the result string and up the octet count
          $result .= $char;
          $octets += $char_octets;
        }

        return $result;
    }

    function ical_header($cal_name,$cal_desc,$cal_timezone) {
      $return = '';
      $return .= 'BEGIN:VCALENDAR' . "\r\n";
      $return .= 'VERSION:2.0' . "\r\n";
      $return .= ical_fold('X-WR-CALNAME', ical_escape($cal_name)) . "\r\n";
      $return .= ical_fold('X-WR-CALDESC', ical_escape($cal_desc)) . "\r\n";
      $return .= 'PRODID:Really Simple Event Calendar' . "\r\n";
      $return .= 'CALSCALE:GREGORIAN' . "\r\n";
      $return .= 'X-WR-TIMEZONE:' . $cal_timezone . "\r\n";
      return $return;
    }

    function ical_footer() {
      return 'END:VCALENDAR' . "\r\n";
    }

    function ical_event($id,$created_date,$modified_date,$start_date,$end_date,$summary,$description,$url) {
    
      $now = new DateTime();
      $dtstamp =$now->format('Ymd\THis\Z');
			
      //Generate a globally unique UID 
	    $rand = '';
	    $host = $_SERVER['SERVER_NAME'];
	    $base = 'aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPrRsStTuUvVxXuUvVwWzZ1234567890';
      $start = 0;
      $end = strlen( $base ) - 1;
      $length = 6;

      for( $p = 0; $p < $length; $p++ ):
       		$rand .= $base{mt_rand( $start, $end )};
      endfor;

      $uid  = $now->format('Ymd\THiT').'-'.$rand.'-EO'.$id.'@'.$host;

      $return = 'BEGIN:VEVENT' . "\r\n";
      $return .= 'UID:' . $uid . "\r\n";
      $return .= 'DTSTAMP:' . $dtstamp . "\r\n";
      $return .= 'CREATED:' . $created_date . "\r\n";
      $return .= 'LAST-MODIFIED:' . $modified_date . "\r\n";
      $return .= 'DTSTART:' . $start_date . "\r\n";
      $return .= 'DTEND:' . $end_date . "\r\n";
      $return .= ical_fold('SUMMARY',ical_escape($summary)) . "\r\n";
      $return .= ical_fold('DESCRIPTION',ical_escape($description)) . "\r\n";
      $return .= ical_fold('URL',$url) . "\r\n";
      $return .= 'END:VEVENT' . "\r\n";
      return $return;
    }


?>