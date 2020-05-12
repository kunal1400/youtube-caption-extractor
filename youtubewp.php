<?php
/**
 * Template Name: Custom Page
 **/

if( !empty($_GET['you_tube_url']) ) {
  $you_tube_url = $_GET['you_tube_url'];
  if ($you_tube_url) {
    $parts = parse_url($you_tube_url);
    parse_str($parts['query'], $query);
    // Getting the params
    if( !empty($query['v']) ) {
      $videoId = $query['v'];
      
      $lang = 'en';
      if (!empty($_GET['lang'])) {
      $lang = $_GET['lang'];
      }
      
      $videoDetails = getVideoDetails($videoId);
      $oXML = getCaption($videoId,$lang);
      $captionsXml = getCaptionsXml($videoId,$lang);

      if( !empty($videoDetails['error']) ) {
        echo '<pre>';
        print_r($videoDetails);
        echo '</pre>';
        die;
      }

      if($videoDetails && $oXML && $captionsXml) {		  
        $items    = count($oXML['text']);
        $ytDuration = $videoDetails['items'][0]['contentDetails']['duration'];
        $duration = covtime($ytDuration);
		  
        if($duration) {
          $times = explode(":", $duration);
          $hour = $times[0];
          $minutes = $times[1];
          $seconds = $times[2];
          $duration = $hour*3600+$minutes*60+$seconds;
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
                $text = formatCsvColumn($oXML['text'][$n+$i][0]);

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
                  $startTime = convertTime($durations['start']);
                  // $startTime = $durations['start'];
                  $timestamp = $startTime;
                }
                else {
                  $timestamp = '';
                }
                $cells .= $timestamp.';'.$text.';';
              }
              $rows .= $you_tube_url.';'.$cells;
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
  }
  else {
    // echo $you_tube_url." is not a valid URL";
    global $wp;
    $redirectUrl = home_url( $wp->request )."/?errmsg=".$you_tube_url." is not a valid URL";
    wp_redirect($redirectUrl);
  }
  die;
}

get_header();
?>
<style>
.error {
  text-align: left;
    color: red;
    font-style: italic;
}
.btn {
    padding: 5px 10px;
    margin: 0 5px 0 0;    
    color: #fff;
    border-radius: 14px;
    cursor: pointer;
}
.blueButton {
  background-color: #4054b2;
}
.blackButton {
    background-color: #16181a;
}
.redButton {
    background-color: red;
}
table.ytContainer td {
    padding: 2px;
    margin: 0;
    border: 0;
    text-align: center;
}
</style>
<table class="ytContainer" >
  <tr>
    <td>
      <div class="error"></div>
      <textarea name="name" rows="8" cols="80"></textarea>
    </td>
    <td width="100">
      <select id="captionLang">       
        <option value="en">English</option>
        <option value="de">German</option>
        <option value="nl">Dutch</option>
        <option value="es">Spanish</option>
        <option value="fr">French</option>
        <option value="pt">Portuguese</option>
        <option value="sv">Swedish</option>
      </select>
    </td>
    <td width="120"><span onclick="startDownloadFromTextarea()" class='btn blueButton'>Download</span></td>
  </tr>
</table>
<table style="display:none" class="ytContainer" >
  <tr class='ytElement' id='div_1'>
    <td>
      <div class="error"></div>
      <input type='text' name="you_tube_url" placeholder='Youtube URL' id='txt_1' >
    </td>
    <td width="120"><span class='btn add blueButton'>Add More</span></td>
  <td width="120"><a class='btn blackButton' data-parentId='div_1' onclick="startDownload(this)">Download</a></td>
  </tr>
</table>
<!-- <form class="wpuf-login-form" action="" method="get">
  <p>
    <label for="wpuf-user_login">Youtube URL</label>
    <input type="text" name="you_tube_url" id="wpuf-user_login" class="input">
  </p>
  <p class="submit">
    <input type="submit" value="Submit">
  </p>
</form> -->
<script type="text/javascript">
  var allUrls = []
  function validURL(str) {
      var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
        '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
      return !!pattern.test(str);
    }
    
  function startDownloadFromTextarea() {
    var captionLang = document.getElementById("captionLang").value;
    var commasepratevalue = document.getElementsByTagName("textarea")[0].value;
    if(commasepratevalue && captionLang) {
      var commaAllsepratearray = commasepratevalue.split(",")
      var commasepratearray = jQuery.unique(commaAllsepratearray)

      // If array is present
      if( Array.isArray(commasepratearray) ) {
      commasepratearray.forEach(function(youtubeurl, index) {
        // console.log(index, youtubeurl, "index, youtubeurl")
        if(youtubeurl) {
        // Removing the error message on click of download
        jQuery('div.error').val();
        var trimUrl = youtubeurl.trim()
        var isValidUrl = validURL(trimUrl);
        if(isValidUrl) {
          // send this url to api so that user can download the file
          allUrls.push('?you_tube_url='+encodeURIComponent(trimUrl)+'&lang='+captionLang)
          // window.open('?you_tube_url='+trimUrl,'_blank');
        }
        else {
          jQuery('div.error').text(youtubeurl+" is invalid Url");
          // break;
        }
        }

        // Check it urls are working
        if( index == commasepratearray.length - 1) {
        var allUniqueUrls = jQuery.unique(allUrls);
        var allUrlsCount = allUniqueUrls.length;

        // Setting the timer
        var timer = setInterval( function() {
          if(allUrlsCount>0) {
            var index = allUrlsCount-1;
            var url = allUrls[index]
            console.log(url, index, "url")
            window.open(url, "_blank")
            allUrlsCount--;
          }
          else {
            document.getElementsByTagName("textarea")[0].value = ""
            clearInterval(timer)
          }
        }, 300);
        }

      })
      }
    }
    else {
      alert("Please enter an value")
    } 
    }
    
    function startDownload(parentId) {
      if(parentId) {
        var pId    = jQuery(parentId).attr("data-parentId")
        var parent = document.getElementById(pId)
        var youtubeurl = jQuery(parent).find('input[name="you_tube_url"]').val();
        // var inputU = jQuery(parent).find('input[name="you_tube_url"]');

        if(youtubeurl) {
          // Removing the error message on click of download
          jQuery(parent).find('div.error').val();

          var isValidUrl = validURL(youtubeurl);
          if(isValidUrl) {
            // send this url to api so that user can download the file
            window.open('?you_tube_url='+youtubeurl,'_blank');
            jQuery(parent).find('input[name="you_tube_url"]').attr("disabled","true");
          } 
          else {
            jQuery(parent).find('div.error').text("Invalid Url");
          }
        } 
        else {
          jQuery(parent).find('div.error').text("url is empty");
        }
      }
      else {
        alert("parentId is missing")
      }      
    }


    jQuery(document).ready(function(){

     // Add new element
     jQuery(".add").click(function(){

      // Finding total number of element added
      var total_ytElement = jQuery(".ytElement").length;
     
      // last <div> with element class id
      var lastid = jQuery(".ytElement:last").attr("id");
      var split_id = lastid.split("_");
      var nextindex = Number(split_id[1]) + 1;

      var max = 50;
      // Check total number element
      if(total_ytElement < max ){
       // Adding new div ytContainer after last occurance of element class
       jQuery(".ytElement:last").after("<tr class='ytElement' id='div_"+ nextindex +"'></tr>");
     
       // Adding element to <div>
       jQuery("#div_" + nextindex).append("<td><div class='error'></div><input type='text' name='you_tube_url' placeholder='Youtube URL' id='txt_"+ nextindex +"'></td><td><span id='remove_" + nextindex + "' class='btn redButton remove'>X</span></td><td><a class='btn add blackButton' data-parentId='div_"+ nextindex +"' onclick='startDownload(this)'>Download</a></td>");   
      }
     
     });

     // Remove element
     jQuery('.ytContainer').on('click','.remove',function(){
     
      var id = this.id;
      var split_id = id.split("_");
      var deleteindex = split_id[1];

      // Remove <div> with id
      jQuery("#div_" + deleteindex).remove();

     }); 
    });
</script>
<?php
get_footer();

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
//  // If response is not empty then xml
//  if( !empty($xmlfile) ) {
//    return simplexml_load_string($xmlfile);
//  } else {
//    echo "$url doesn't have any captions";
//    die;
//  }
// }

function getCaption($videoId, $lang='en') {
  $new = getCaptionsXml($videoId, $lang);
  $con = json_encode($new);
  return json_decode($con, true);
}

function getVideoDetails( $videoId ) {
  $apiKey = "AIzaSyDPuRySTDvdT0wgEzIFWtQdz6fAYW4WUZs";
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
  $t = round($seconds);
  return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
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

function covtime($youtube_time){
    $start = new DateTime('@0'); // Unix epoch
    $start->add(new DateInterval($youtube_time));
    return $start->format('H:i:s');
}
