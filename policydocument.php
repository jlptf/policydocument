<?php 

    /*
     * reference : https://gist.github.com/MLoureiro/14503293c3cb2191e2f6 
     * @params p12FilePath Service account p12 file path
     * @params string Plain text for encoding
     */
    function googleSignString($p12FilePath, $string){
        $certs = [];
        if (!openssl_pkcs12_read(file_get_contents($p12FilePath), $certs, 'notasecret'))
        {
            echo "Unable to parse the p12 file. OpenSSL error: " . openssl_error_string(); exit();
        }
        $RSAPrivateKey = openssl_pkey_get_private($certs["pkey"]);
        $signed = '';
        if(!openssl_sign( $string, $signed, $RSAPrivateKey, 'sha256' ))
        {
            error_log( 'openssl_sign failed!' );
            $signed = 'failed';
        }
        else
        {
            $signed = base64_encode($signed);
        }
        return $signed;
    }

    $bucket = 'mybucket';//change to your bucket name
    $key    = 'myobject';//change to your object name
    $p12file = 'your.p12';//change to your p12 file path
    $actionPath = "https://".$bucket.".storage.googleapis.com";
    $account = "your service account";

    $expire = time() + (60 * 5); // 5 min
    $iso8601 = date('c', $expire);
    echo "unixtimestamp-iso8601:".$iso8601;

    $policy = '{"expiration": "' . $iso8601 . '",' . 
              '"conditions": ['.
                '{"acl":"public-read"},'. //specify object acl
                '{"bucket": "'. $bucket.'"},'.  //specify bucket
                '{"key":"'.$key.'"},'. //spcify object name
                '{"success_action_redirect":"http://localhost/success.php"}'. // specify redirect url after upload successful
              ']}';
    
    $policy_utf = utf8_encode($policy);
    $policy_base64 = base64_encode($policy_utf);

    $signature = googleSignString($p12file, $policy_base64);
?>

<form action="<?php echo $actionPath?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="key" value="<?php echo $key?>">
            <input type="hidden" name="bucket" value="<?php echo $bucket?>">
            <input type="hidden" name="acl" value="public-read">
            <input type="hidden" name="success_action_redirect" value="http://localhost/success.php">
            <input type="hidden" name="GoogleAccessId" value="<?php echo $account?>">
            <input type="hidden" name="policy" value="<?php echo $policy_base64; ?>">
            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
            <input type="file" name="file">
            <input type="submit" value="Upload!">
</form>
