<?php

namespace Adapterman;

trait ParseMultipart
{
    /**
     * Parse multipart form data to $_POST & $_FILES.
     *
     */
    protected static function parseMultipart(string $http_body, string $http_post_boundary): void
    {
        $http_body = \substr($http_body, 0, \strlen($http_body) - (\strlen($http_post_boundary) + 4));
        $boundary_data_array = \explode($http_post_boundary."\r\n", $http_body);
        if ($boundary_data_array[0] === '') {
            unset($boundary_data_array[0]);
        }

        $post_encode_string = '';
        foreach ($boundary_data_array as $boundary_data_buffer) {
            [$boundary_header_buffer, $boundary_value] = \explode("\r\n\r\n", $boundary_data_buffer, 2);
            // Remove \r\n from the end of buffer.
            $boundary_value = \substr($boundary_value, 0, -2);

            // Is post field
            if (! strpos($boundary_header_buffer, '"; filename="')) {
                // Parse $_POST.
                $item =  \explode("\r\n", $boundary_header_buffer)[0];
                $header_value = \explode(': ', $item)[1];
                if (\preg_match('/name="(.*?)"$/', $header_value, $match)) {
                    $post_encode_string .= urlencode($match[1]).'='.urlencode($boundary_value).'&';
                }
                continue;
            };

            // Is file data
            if (\preg_match('/name="(.*?)"/', $boundary_header_buffer, $named)) {
                $name = $named[1];
            } else { // Unknow
                continue;
            }

            foreach (\explode("\r\n", $boundary_header_buffer) as $item) {
                [$header_key, $header_value] = \explode(': ', $item);
                $header_key = \strtolower($header_key);
                switch ($header_key) {
                    case 'content-disposition':
                        if (\preg_match('/"; filename="(.*?)"/', $header_value, $match)) {
                            // Parse $_FILES.
                            $_FILES[$name] = [
                                'file_name' => \basename($match[1]),
                                'full_path' => $match[1],
                                //'file_data' => $boundary_value,
                                'file_size' => \strlen($boundary_value),
                                'tmp_name'  => static::saveTempFile($boundary_value),
                                'error'     => \UPLOAD_ERR_OK, // test 
                            ];
                            break;
                        }
                    case 'content-type':
                        // add file_type
                        $_FILES[$name]['file_type'] = \trim($header_value);
                        break;
                    case 'Content-Lenght':

                }
            }
        }
        // $_POST data
        if ($post_encode_string) {
            \parse_str($post_encode_string, $_POST);
        }
    }

    protected static function saveTempFile($data): string
    {
        $tmp_file = \tempnam(sys_get_temp_dir(), 'php');
        file_put_contents($tmp_file,$file);
        // delete tmp_file after send()
        
        return $tmp_file;
    }

}
