<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2016/12/12
 * Time: 12:03
 */

namespace app\common\core;


class Client
{
    /**
     * HTTP Methods
     */
    const HTTP_METHOD_GET = 'GET';

    const HTTP_METHOD_POST = 'POST';

    const HTTP_METHOD_PUT = 'PUT';

    const HTTP_METHOD_DELETE = 'DELETE';

    const HTTP_METHOD_HEAD = 'HEAD';

    const HTTP_METHOD_PATCH = 'PATCH';

    /**
     * HTTP Form content types
     */
    const HTTP_FORM_CONTENT_TYPE_APPLICATION = 0;

    const HTTP_FORM_CONTENT_TYPE_MULTIPART = 1;

    /**
     * Execute a request (with curl)
     *
     * @param string $url
     *            URL
     * @param mixed $parameters
     *            Array of parameters
     * @param string $http_method
     *            HTTP Method
     * @param array $http_headers
     *            HTTP Headers
     * @param int $form_content_type
     *            HTTP form content type to use
     * @return array
     */
    public function executeRequest ($url, $parameters = array(),  $http_method = self::HTTP_METHOD_GET, array $http_headers = null, $form_content_type = self::HTTP_FORM_CONTENT_TYPE_APPLICATION)
    {
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $http_method
        );

        switch ($http_method) {
            case self::HTTP_METHOD_POST:
                $curl_options[CURLOPT_POST] = true;
            /* No break */
            case self::HTTP_METHOD_PUT:
            case self::HTTP_METHOD_PATCH:

                /**
                 * Passing an array to CURLOPT_POSTFIELDS will encode the data
                 * as multipart/form-data,
                 * while passing a URL-encoded string will encode the data as
                 * application/x-www-form-urlencoded.
                 * http://php.net/manual/en/function.curl-setopt.php
                 */
                if (is_array($parameters) && self::HTTP_FORM_CONTENT_TYPE_APPLICATION ===
                    $form_content_type) {
                    $parameters = http_build_query($parameters, null, '&');
                }
                $curl_options[CURLOPT_POSTFIELDS] = $parameters;
                break;
            case self::HTTP_METHOD_HEAD:
                $curl_options[CURLOPT_NOBODY] = true;
            /* No break */
            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:
                if (is_array($parameters)) {
                    $url .= '?' . http_build_query($parameters, null, '&');
                } elseif ($parameters) {
                    $url .= '?' . $parameters;
                }
                break;
            default:
                break;
        }


        $curl_options[CURLOPT_URL] = $url;
        if (is_array($http_headers)) {
            $header = array();
            foreach ($http_headers as $key => $parsed_urlvalue) {
                $header[] = "$key: $parsed_urlvalue";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        // https handling
        if (! empty($this->certificate_file)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificate_file);
        } else {
            // bypass ssl verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        if (! empty($this->curl_options)) {
            curl_setopt_array($ch, $this->curl_options);
        }
        $result = curl_exec($ch);
        return $result;
    }

    public function fetch ($protected_resource_url, $parameters = array(),
                           $http_method = self::HTTP_METHOD_GET, array $http_headers = array(),
                           $form_content_type = self::HTTP_FORM_CONTENT_TYPE_APPLICATION)
    {
        return $this->executeRequest($protected_resource_url, $parameters,
            $http_method, $http_headers, $form_content_type);
    }
}