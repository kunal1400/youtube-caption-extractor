<?php
$youTubeUrl = "https://www.youtube.com/watch?v=ij1Vafu4Wh8";
// $youTubeUrl = "https://www.youtube.com/watch?v=5R54QoUbbow";

$parts = parse_url($youTubeUrl);
parse_str($parts['query'], $query);

// Getting the params
if( !empty($query['v']) ) {
  $videoId = $query['v'];
  $videoDetails = getVideoDetails($videoId);
  $oXML = getCaption($videoId);
  if($videoDetails && $oXML) {
    $items    = count($oXML['text']);
    $duration = $videoDetails['items'][0]['contentDetails']['duration'];
    $minutes  = get_string_between($duration, 'PT', 'M');
    $seconds  = get_string_between($duration, 'M', 'S');
    $duration = $minutes*60+$seconds;
    $npaths   = getXpathFromVideoDuration($duration);

    // If npaths are present
    $rows = '';
    // $rows = [];
    if($npaths) {
      foreach ($npaths as $i => $number) {
        $n = round(($number/10)*$items);
        $cells = '';
        // $cells = [];
        for ($i=0; $i <=2 ; $i++) {
          $string = $oXML['text'][$n+$i];
          $string = trim(preg_replace('/\s+/', ' ', $string)).' ';
          $string = str_replace(';','',$string);
          $string = str_replace(',','',$string);
          // $cells .= rtrim($string, "\n");
          // $cells[] = $string.' ';
          $cells .= $string;
        }
        $rows .= $cells."\r\n";
        // fputcsv($fp, $cells, ";");
        // $rows[] = $cells;
        // $output .= "\r\n";
        // echo $number.' - '.$output;
        // echo "<br/><br/><br/>";
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


function getCaption($videoId, $lang='en') {
  $url = "https://www.youtube.com/api/timedtext?lang=$lang&v=$videoId";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $xmlfile = curl_exec($ch);
  curl_close($ch);
  $new = simplexml_load_string($xmlfile);
  $con = json_encode($new);
  return json_decode($con, true);
}

function getVideoDetails( $videoId ) {
  // Insert google apikey here
  $apiKey = "";
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
