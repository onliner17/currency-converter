<?php
  $GLOBALS['my_trace_message'] = "";
  $GLOBALS['my_conversion_text'] = "";
  
  $names = array (
    'AUD' => 'Australian dollar',
    'BGN' => 'Bulgarian lev',
    'BRL' => 'Brazilian real',
    'CAD' => 'Canadian dollar',
    'CHF' => 'Swiss franc',
    'CNY' => 'Chinese yuan renminbi',
    'CZK' => 'Czech koruna',
    'DKK' => 'Danish krone',
    'GBP' => 'Pound sterling',
    'HKD' => 'Hong Kong dollar',
    'HRK' => 'Croatian kuna',
    'HUF' => 'Hungarian forint',
    'IDR' => 'Indonesian rupiah',
    'ILS' => 'Israeli shekel',
    'INR' => 'Indian rupee',
    'ISK' => 'Icelandic krona',
    'JPY' => 'Japanese yen',
    'KRW' => 'South Korean won',
    'LTL' => 'Lithuanian litas',
    'LVL' => 'Latvian lats',
    'MXN' => 'Mexican peso',
    'MYR' => 'Malaysian ringgit',
    'NOK' => 'Norwegian krone',
    'NZD' => 'New Zealand dollar',
    'PHP' => 'Philippine peso',
    'PLN' => 'Polish zloty',
    'RON' => 'New Romanian leu',
    'RUB' => 'Russian rouble',
    'SEK' => 'Swedish krona',
    'SGD' => 'Singapore dollar',
    'THB' => 'Thai baht',
    'TRY' => 'New Turkish lira',
    'USD' => 'US dollar',
    'ZAR' => 'South African rand',
  );

  function download_page($path){
  	$ch = curl_init();
  	curl_setopt($ch, CURLOPT_URL,$path);
  	curl_setopt($ch, CURLOPT_FAILONERROR,1);
  	curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  	$retValue = curl_exec($ch);			 
  	curl_close($ch);
  	return $retValue;
  }
  
  function convert($amount, $from, $to, &$ecd_update_time, &$rate_value)
  {  
    $now = time();
    //$cache = "$_SERVER[DOCUMENT_ROOT]/www/tools/currency-converter/cache-erates.xml";
    $cache = __DIR__ . "/cache-erates.xml";
    //echo "cache : " . $cache . "<br />";
    
    # Exchange rates - Grab current rates from European Central Bank
    
    # Check whether we have a recent copy of the data locally
    $last_cache_time = 0;
    //Get cache file time 
    if (file_exists($cache))
    {
      $last_cache_time = filemtime($cache);
    }
  
    if (($interval = $now - $last_cache_time) > 3600 * 3) {
      $xml_source = download_page("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
      //write to the cache file
      file_put_contents($cache, $xml_source);
      //load XML from string
      $XML=simplexml_load_string($xml_source);
      $GLOBALS['my_trace_message'] = $GLOBALS['my_trace_message'] . "\n" . "Cache REFRESHED after $interval seconds";  
    } else 
    {
      //$stuff = file($cache);
      $XML=simplexml_load_file($cache);
      $GLOBALS['my_trace_message'] = $GLOBALS['my_trace_message'] . "\n" . "Cached information reused aged $interval seconds";
    }
      
    $c_array['EUR'] = 1.0;            
    foreach($XML->Cube->Cube->Cube as $rate){
        //Output the value of 1EUR for a currency code
        //echo '1 &euro;= '. $rate["rate"]. ' ' .$rate["currency"].'<br/>';
        
        $ccc = (string)$rate["currency"];
        //echo gettype($ccc). "<br />" . PHP_EOL;
        
        $c_array[$ccc] = $rate["rate"];
    } 
  
    //Get Feed update time
    $ecd_update_time = 0;
    if (isset($XML->Cube->Cube[0]["time"]))
    {
      $ecd_update_time = $XML->Cube->Cube[0]["time"];
    } 
    //echo "Time = " . $ecd_update_time . "<br />" . PHP_EOL;
  
    if (isset($c_array[$to]) && $c_array[$from])
    {
      $rate_value = (float)$c_array[$to]/(float)$c_array[$from];
     
      $result = $rate_value * $amount;

      $rate_value = round($rate_value, 4);      
      $result = round($result, 4); 
      return $result;  
    }
    else
    {
      //Currency to not found.
      //echo "Error";
      return -1;
    } 
  }
/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
  
  $amount = "1";
  $from ="EUR";
  $to ="USD";
  if (isset($_POST['amount']) && isset($_POST['from']) && isset($_POST['to'])) 
  {
  
    //Retrieve the amount, from and to after stripping tags for SECURITY 
    $amount = strip_tags(trim($_POST['amount']));
    $from = strip_tags( trim($_POST['from']));
    $to = strip_tags( trim($_POST['to']));
    
    //More security Checks
    //TBD - Need to add client side restrictions like size
    if ($amount < 0)
    {
      $amount = 1;
    }
    if ($amount > 10000000000)
    {
      $amount = 1;
    }
    if (strlen($from) > 10)
    {
      $from = "EUR";
    }
    if (strlen($to) > 10)
    {
      $to = "USD";
    }
    
    //echo "amount = $amount, from= $from, to= $to <br />" . PHP_EOL;
    
    $rate_value = -1;
    $ecd_update_time = "Unknown";
    $result = convert($amount, $from, $to, $ecd_update_time, $rate_value);
         
    $currency_name = "";
    if (isset($names[$to]))
    {
      $currency_name = "(" . $names[$to] . ")";
    } 
           
    //echo "$amount $from = $result $to $currency_name <br />" . PHP_EOL;
    
    $GLOBALS['my_conversion_text'] = "<p class='lead text-left'> $amount <span class='text-primary'><strong>$from</strong></span> = $result <span class='text-primary'><strong>$to</strong></span> <small>$currency_name</small><abbr title='*** ECB Update Time = $ecd_update_time. 1 $from = $rate_value $to.'>***</abbr></p>" . PHP_EOL;  
    //exit;        
  }   
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <!--
    <meta name="author" content="">
    <link rel="icon" href="/favicon.ico">
    -->
  
    <title>Currency Exchange Rate Converter</title>
    
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
        
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->    
  </head>
  <body>
    <div class="container">
      <!--<h3>Currency Converter</h3>-->
      
      <div class="row">
        
          <form method="post" class="form form-inline" role="form">          
            
            <div class="form-group">   
              <input name="amount" type="number" step="any" class="form-control" value="<?php echo $amount?>" autofocus="" />
            </div>
  
            <div class="form-group">
              <select name="from" class="form-control" value="USD">
                <option value="<?php echo $from?>"><?php echo $from?></option>
                <option value="EUR">EUR - Euro</option>
              	<option value="GBP">GBP - British Pound</option>
              	<option value="USD">USD - U.S. Dollar</option>
              	<option value="CAD">CAD - Canadian Dollar</option>
              	<option value="AUD">AUD - Australian Dollar</option>
              	<option value="JPY">JPY - Japanese Yen</option>
                <option value="ILS">ILS - Israeli Shekel</option>
              </select>               
            </div>

            <div class="form-group">
              <!--<label for="exampleTo">TO: </label>-->
              <select name="to" class="form-control">
                <option value="<?php echo $to?>"><?php echo $to?></option>
                <option value="USD">USD - U.S. Dollar</option>
              	<option value="ILS">ILS - Israeli Shekel</option>
              	<option value="EUR">EUR - Euro</option>
              	<option value="GBP">GBP - British Pound</option>
              	<option value="CAD">CAD - Canadian Dollar</option>
              	<option value="AUD">AUD - Australian Dollar</option>
              	<option value="JPY">JPY - Japanese Yen</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary">Convert</button>
                
          </form>

      </div>

      <div class="row" style="margin-top:15px;">
          <?php
              echo $GLOBALS['my_conversion_text'];
          ?>
         
          <?php
            //Display copyright if host is not eurusd.co
            $local_host = "";
            if (isset($_SERVER['HTTP_HOST']))
            {
              $local_host = $_SERVER['HTTP_HOST'];
            } 
            
            $pos = strpos($local_host, "eurusd.co");
            
            if ($pos === false) 
            {
              echo "Copyright <a href='https://www.eurusd.co/' target='_blank'>EurUsd.co</a> " . date("Y");
              
              //echo "  " . $local_host;
            }
          ?>
          <!--Trace: <?= $GLOBALS['my_trace_message'] ?><br />-->
                    
      </div>
  </div>
  
     <!-- Bootstrap core JavaScript
      ================================================== -->
      <!-- Placed at the end of the document so the pages load faster -->
      <!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>-->
      <!-- Latest compiled and minified JavaScript -->
      <!--<script src="js/bootstrap.min.js"></script>-->
      
      <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
      <!--<script src="/js/ie10-viewport-bug-workaround.js"></script>-->
  </body>
  </html>