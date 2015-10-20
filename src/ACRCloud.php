<?php
/**
 * acr-cloud-php
 * @package acrCloud
 * @version 0.1.0
 * @link https://github.com/AJDurant/acr-cloud-php
 * @author AJDurant <https://github.com/AJDurant>
 * @license https://github.com/AJDurant/acr-cloud-php/blob/master/LICENSE
 * @copyright Copyright (c) 2015, AJDurant
 */

namespace AJDurant\ACRCloud;

require(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

/**
 * The ACRCloud class
 * @author AJDurant <https://github.com/AJDurant>
 * @since 0.1.0
 */
class ACRCloud {

    /**
     * @var string $access_key The API key for calls to ACRCloud
     * @since 0.1.0
     */
    private $access_key;

    /**
     * @var string $access_secret The API secret for calls to ACRCloud
     * @since 0.1.0
     */
    private $access_secret;

    /**
     * @var string $base_url The API url for ACRCloud
     * @since 0.1.0
     */
    private $base_url = 'http://ap-southeast-1.api.acrcloud.com';

    /**
     * @var string $api_version The API version uri for ACRCloud
     * @since 0.1.0
     */
    private $api_version = '/v1';

    /**
     * @var string $ffmpeg_path The path to the ffmpeg executable on the system
     * @since 0.1.0
     */
    private $ffmpeg_path = 'ffmpeg';

    /**
     * Constructs the ACRCloud library class for use.
     * @param string      $api_key     The API key for calls to ACRCloud
     * @param string      $api_secret  The API secret for calls to ACRCloud
     * @param string|null $base_url    The API url for ACRCloud
     * @param string|null $ffmpeg_path The path to the ffmpeg executable on the system
     * @since 0.1.0
     */
    public function __construct ($access_key, $access_secret, $base_url = null, $ffmpeg_path = null)
    {
        $this->access_key = $access_key;
        $this->access_secret = $access_secret;

        if (!is_null($base_url)) {
            $this->base_url = $base_url;
        }

        if (!is_null($ffmpeg_path)) {
            $this->ffmpeg_path = $ffmpeg_path;
        }
    }

    /**
     * Fingerprints audio files returning track data
     * @param  string  $file_path System file path for the track to identify
     * @param  integer $start     Seconds from start to trim for fingerprinting
     * @param  integer $duration  Length in seconds for trimming for fingerprinting
     * @return array              ACRCloud metadata response
     */
    public function identify($file_path, $start = 5, $duration = 20)
    {
        $wav_data = $this->getWavData($file_path, intval($start), intval($duration));

        $data = $this->apiPost($wav_data);

        return $data;
    }

    /**
     * Trims down input file to PCM data using ffmpeg
     *
     * Using ffmpeg to transcode to wav and dump a fragment to stdout then reading it with popen()
     *  example command: ffmpeg -i "somefile.mp3"  -ac 1 -ar 8000 -f wav -ss 5 -t 10 -  2>/dev/null
     *
     * @param  string $file_path System file path for the track to identify
     * @param  integer $start    Seconds from start to trim for fingerprinting
     * @param  integer $duration Length in seconds for trimming for fingerprinting
     * @return string            PCM data as a string
     */
    protected function getWavData($file_path, $start, $duration)
    {

        $command = escapeshellarg($this->ffmpeg_path) . ' -i ' . escapeshellarg($file_path) . ' -ac 1 -ar 8000 -f wav -ss ' . $start . ' -t ' . $duration. ' - 2>/dev/null';
        $wav = '';

        $phandle = popen($command , 'r');

        while(!feof($phandle))
        {
            $wav .= fread($phandle, 1024);
        }

        pclose($phandle);

        return($wav);
    }

    /**
     * POST audio data to the ACRCloud server for identification
     * @param  string $data Audio data as a string
     * @return array        ACRCloud server response
     */
    protected function apiPost($data)
    {
        $uri = $this->api_version . '/identify';
        $url = $this->base_url . $uri;

        $data_type = 'audio';
        $signature_version = '1';
        $timestamp = time();

        $string_to_sign = 'POST' . "\n"
            . $uri ."\n"
            . $this->access_key . "\n"
            . $data_type . "\n"
            . $signature_version . "\n"
            . $timestamp;

        $signature = hash_hmac('sha1', $string_to_sign, $this->access_secret, true);
        $signature = base64_encode($signature);

        // suported file formats: mp3,wav,wma,amr,ogg, ape,acc,spx,m4a,mp4,FLAC, etc
        // File size: < 1M , You'd better cut large file to small file, within 15 seconds data size is better
        $filesize = strlen($data);

        $postfields = [
            'access_key' => $this->access_key,
            'data_type' => $data_type,
            'sample_bytes' => $filesize,
            'sample' => $data,
            'signature_version' => $signature_version,
            'signature' => $signature,
            'timestamp' => $timestamp
        ];

        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, $postfields);

        $response = curl_exec($request);

        curl_close($request);

        return($response);
    }


}
