<?php

error_reporting(0);

// create search string 
$search = trim(sprintf('%s %s %s', 
  trim(@$_GET['gender']),
  trim(@$_GET['color']),
  trim(@$_GET['clothing_item'])
));

// search and return items as json
if($search){
  $items = amazon_search($search);
}else{
  $items = array();
}

$json = json_encode($items);
file_put_contents(__dir__.'/adriana_results.json', $json);

//allow cross domain requets
setCORS();

header('content-type:application/json');
exit($json);

##########################################

/**
 *  - https://developer.mozilla.org/en/HTTP_access_control
 *  - http://www.w3.org/TR/cors/
 */
function setCORS(){

  if(isset($_SERVER['HTTP_ORIGIN'])){
    header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
  }

  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])){
      header('Access-Control-Allow-Methods: GET, POST, OPTIONS');         
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){
      header('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    }
    exit;
  }

}

// Search amazon
function amazon_search($search){

  $url = 'https://www.amazon.com/s?field-keywords=' . urlencode($search);
  $items = array();
  //changed to 50 on 04/01/2017 @ 4:01 PM
  $max_items = 50;

  $response = amazon_page($url);
  
  // save page
  file_put_contents(__dir__.'/amazon_page.html', $response);
  
  // parse response
  $regex = '<li[^>]+id="result_(\d+)".+';
  $regex .= '<img[^>]+src="(https://images[^"]+)"[^>]*>.+';
  $regex .= '<span[^>]+aria-label="([^"]+)"[^>]+class="[^"]*sx-zero-spacing[^"]*"';
  preg_match_all('|'.$regex.'|Usi', $response, $matches);

  foreach($matches[0] as $i => $match){
    $src = trim($matches[2][$i]);
    $price = trim($matches[3][$i]);
    $items[] = array(
      'price' => $price,
      'img_src' => $src,
      'img' => '', // will fetch in amazon_images()
    );
  }
  
  $items = array_slice($items, 0, $max_items);
  
  if(sizeof($items)){
    $items = amazon_images($items);
  }else{
    // some default item
    $items[] = array(
      'price' => 'No items found',
      'img_src' => '',
      'img' => 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', // 1x1px transparent gif 
    );
  }
  
  return $items;
  
}

// Fetch amazon images
function amazon_images($items){

  $ch = array();
  $mh = curl_multi_init();
  foreach($items as $key => $item){
    $ch[$key] = curl_init($item['img_src']);
    curl_setopt($ch[$key], CURLOPT_HEADER, false);
    curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch[$key], CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($ch[$key], CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36');
    curl_setopt($ch[$key], CURLOPT_HTTPHEADER, array(
      'Accept-Charset: utf-8', 
      'Accept: text/html',
      'Accept-Language: en',
      'Connection: keep-alive',
      'Content-Type: text/html',
      'Cache-Control: no-cache',
      'Pragma: no-cache',
    ));
    curl_multi_add_handle($mh, $ch[$key]);
  }

  do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
  } while ($running > 0);

  foreach($items as $key => &$item){
    $img = curl_multi_getcontent($ch[$key]);
    
    if(0){
      // save image locally
      $item['img_name'] = 'img-'.md5($item['img_src']).'.jpg';
      $filepath = str_replace(basename(__FILE__), $item['img_name'], __FILE__);
      file_put_contents($filepath, $img);
    }
    
    $item['img'] = base64_encode($img); 
    curl_multi_remove_handle($mh, $ch[$key]);
  }

  curl_multi_close($mh);
    
  return $items;
  
}

// Fetch amazon page
function amazon_page($url){

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept-Charset: utf-8', 
    'Accept: text/html',
    'Accept-Language: en',
    'Connection: keep-alive',
    'Content-Type: text/html',
    'Cache-Control: no-cache',
    'Pragma: no-cache',
  ));
  
  $response = curl_exec($ch);
  curl_close($ch);
  
  return $response;
  
}


