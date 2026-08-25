<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixUtf8Encoding
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $originalData = $response->getData(true);
            if (is_array($originalData)) {
                $fixed = $this->fixEncoding($originalData);
                $response->setData($fixed);
            }
        }

        return $response;
    }

    private function fixEncoding($data)
    {
        if (is_string($data)) {
            return $this->repairDoubleEncodedUtf8($data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->fixEncoding($value);
            }
        }

        return $data;
    }

    private function repairDoubleEncodedUtf8(string $str): string
    {
        if ($str === '' || mb_check_encoding($str, 'ASCII')) {
            return $str;
        }

        if (!$this->hasCommonMojibakePatterns($str)) {
            return $str;
        }

        $decoded = mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        if ($decoded !== false && $decoded !== $str && mb_check_encoding($decoded, 'UTF-8')) {
            return $decoded;
        }

        return $str;
    }

    private function hasCommonMojibakePatterns(string $str): bool
    {
        return (bool) preg_match('/Ã[¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ]/u', $str);
    }
}
