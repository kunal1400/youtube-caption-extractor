<?php
if( !empty($_GET['errmsg']) ) {
	echo '<p style="color:red;width: 100%;">'.$_GET['errmsg'].'</p>';
}
?>
<form class="wpuf-login-form" action="" method="get">
  <p>
    <label for="wpuf-user_login">Youtube URL</label>
    <input type="text" name="you_tube_url" id="wpuf-user_login" class="input">
  </p>
  <p class="submit">
    <input type="submit" value="Submit">
  </p>
</form>

<?php
if( !empty($_GET['you_tube_url']) ) {
	$you_tube_url = $_GET['you_tube_url'];
	if (filter_var($you_tube_url, FILTER_VALIDATE_URL)) {
		$parts = parse_url($you_tube_url);
		parse_str($parts['query'], $query);
		// Getting the params
		if( !empty($query['v']) ) {
		  $videoId = $query['v'];

		  $videoDetails = getVideoDetails($videoId);
		  $oXML = getCaption($videoId);
		  $captionsXml = getCaptionsXml($videoId);

		  if($videoDetails && $oXML && $captionsXml) {
  			$items    = count($oXML['text']);
  			$duration = $videoDetails['items'][0]['contentDetails']['duration'];
  			$minutes  = get_string_between($duration, 'PT', 'M');
  			$seconds  = get_string_between($duration, 'M', 'S');
  			$duration = $minutes*60+$seconds;
  			$npaths   = getXpathFromVideoDuration($duration);

  			// If npaths are present like 2, 4, 6, 8 etc it will generate rows
  			$rows = '';
  			if($npaths) {
  			  foreach ($npaths as $i => $number) {
  				$n = round(($number/10)*$items);

  				$cells = '';
  				// Getting the nth, nth+1 path so it will generate columns
  				for ($i=0; $i <=2 ; $i++) {
  				  $string = '';
  				  $durations = [];

            // Removing the semicolon, comma from string so that proper column generate
            $text = formatCsvColumn($oXML['text'][$n+$i]);

  				  // Getting the timestamps for each nth path from xml response
  					$j = 0;
  					foreach ($captionsXml->text as $data) {
  					  if($j == $n+$i) {
    						// foreach ($data->attributes() as $k => $v) {
    						$dataArray = json_decode(json_encode($data),true);
    						$durations = $dataArray['@attributes'];
  					  }
  					  $j++;
  					}
            if(count($durations) > 0) {
    					// $startTime = convertTime($durations['start']);
    					$startTime = $durations['start'];
    					$timestamp = $startTime;
  				  }
            else {
              $timestamp = '';
            }
            $cells .= $timestamp.';'.$text.';';
  				}
  				$rows .= $you_tube_url.';'.$cells."\n\r";
  			  }
  			}

  			// print_r($rows);
  			// echo $formatedString = str_putcsv($rows);
  			downloadCsv($rows);
  			// echo '<pre>';
  			// print_r($oXML['text']);
  			// echo '</pre>';
		  }

		}
	}
	else {
    echo $you_tube_url." is not a valid URL";
		// global $wp;
		// $redirectUrl = home_url( $wp->request )."/?errmsg=".$you_tube_url." is not a valid URL";
		// wp_redirect($redirectUrl);
		die;
	}
}

function redirectWithMsg() {
	// global $wp;
	// $redirectUrl = home_url( $wp->request )."/?errmsg=".$you_tube_url." is not a valid URL";
	// wp_redirect($redirectUrl);
	// die;
}

function formatCsvColumn( $string ) {
  $s = trim(preg_replace('/\s+/', ' ', $string)).' ';
  $s = str_replace(';','',$s);
  return str_replace(',','',$s);
}

// function getCaptionsXml($videoId, $lang='en') {
//   $url = "https://www.youtube.com/api/timedtext?lang=$lang&v=$videoId";
//   $ch = curl_init();
//   curl_setopt($ch, CURLOPT_URL, $url);
//   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//   $xmlfile = curl_exec($ch);
//   curl_close($ch);
// 	// If response is not empty then xml
// 	if( !empty($xmlfile) ) {
// 		return simplexml_load_string($xmlfile);
// 	} else {
// 		echo "$url doesn't have any captions";
// 		die;
// 	}
// }

function getCaption($videoId, $lang='en') {
  $new = getCaptionsXml($videoId, $lang);
  $con = json_encode($new);
  return json_decode($con, true);
}

function getVideoDetails( $videoId ) {
  $apiKey = "AIzaSyBMhJcBZwWB_d-0S53ulC8psZO5dHCvleY";
  if(!empty($apiKey)) {
    $url = "https://www.googleapis.com/youtube/v3/videos?id=$videoId&part=contentDetails&key=$apiKey";
    if( !empty($videoId) ) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $output = curl_exec($ch);
      curl_close($ch);
      return json_decode($output, true);
    }
    else {
      return null;
    }
  }
  else {
    echo "Google api Key is required";
    die;
  }
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function getXpathFromVideoDuration($videoDuration) {
  if(!empty($videoDuration)) {
    if($videoDuration <= 149) {
      // 0:00-02:29
      return array(5);
    }
    if($videoDuration >= 150 && $videoDuration <= 899) {
      // 02:30 - 14:59
      return array(2,5,8);
    }
    if($videoDuration >= 900 && $videoDuration <= 2399) {
      // 15:00 - 39:59
      return array(2,4,6,8);
    }
    if($videoDuration >= 2400 && $videoDuration <= 3599) {
      // 40:00 - 59:59
      return array(1,3,5,7,9);
    }
    if($videoDuration >= 3600 && $videoDuration <= 5369) {
      // 01:00:00 - 01:29:29
      return array(1,3,4,7,8,9);
    }
    if($videoDuration > 5369) {
      // more then 01:30:00
      return array(1,2,3,4,5,6,7,8,9);
    }
  }
  else {
    return null;
  }
}

function downloadCsv($csvString) {
	ob_clean();
	ob_start();
  $fileName = 'extracted_captions_'.time();
  header('Content-Type: application/csv');
  header('Content-Disposition: attachment; filename="'.$fileName.'.csv";');
  echo $csvString;
}

function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
    $fp = fopen('php://temp', 'r+');
    fputcsv($fp, $input, $delimiter, $enclosure);
    rewind($fp);
    $data = fread($fp, 1048576);
    fclose($fp);
    return rtrim($data, "\n");
}

function convertTime($seconds){

    // // start by converting to seconds
    // $seconds = ($dec * 3600);
    // // we're given hours, so let's get those the easy way
    // $hours = floor($dec);
    // // since we've "calculated" hours, let's remove them from the seconds variable
    // $seconds -= $hours * 3600;
    // // calculate minutes left
    // $minutes = floor($seconds / 60);
    // // remove those from seconds as well
    // $seconds -= $minutes * 60;
    // // return the time formatted HH:MM:SS

    return lz(round($seconds/3600)).":".lz(round($seconds/60)).":".lz(round($seconds/100));
}

function lz($num) {
    return (strlen($num) < 2) ? "0{$num}" : $num;
}

function callGetRequest($url) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $return = curl_exec($curl);
  curl_close($curl);
  return $return;
}

function getCaptionsXml($videoId, $lang='en') {

  $url = "https://www.youtube.com/get_video_info?video_id=$videoId";
  $return = callGetRequest($url);

  // $arrayData = json_decode($return, true);
  $urlDecode = urldecode($return);

  if(strpos($urlDecode,"captionTracks")) {
    foreach (explode('&', $urlDecode) as $chunk) {

      // Because the position can be 0
      $isPresent = strpos($chunk, "player_response=");
      if($isPresent === 0) {
        $json = str_replace("player_response=","",$chunk);
        if($json) {
          $jsonArray = json_decode($json, true);
          if($jsonArray['captions']) {
            if($jsonArray['captions']['playerCaptionsTracklistRenderer']) {
              $captionTracks = $jsonArray['captions']['playerCaptionsTracklistRenderer']['captionTracks'];
              $nextInfo = null;
              foreach($captionTracks as $i => $v) {
                if($v['languageCode'] == $lang) {
                  $nextInfo = $lang;
                  break;
                }
              }

              if($nextInfo) {
                $xmldata = callGetRequest($v['baseUrl']);
                return simplexml_load_string($xmldata);
              }
              else {
                echo json_encode(array('status' => false, 'msg' => "Could not find $lang captions for $videoId"));
                die;
              }
            }
            else {
              echo json_encode(array('status' => false, 'msg' => 'playerCaptionsTracklistRenderer key is not present'));
              die;
            }
          }
          else {
            echo json_encode(array('status' => false, 'msg' =>'captions key is not present'));
            die;
          }
        } else {
          echo json_encode(array('status' => false, 'msg' => "player_response= is not a json"));
          die;
        }
      }
    }
  } else {
    echo json_encode(array('status' => false, 'msg' => "Could not find captions for video $videoId"));
    die;
  }
}
