<?php

namespace Yarteyd\PhoneMiddleware;

use Closure;
use Illuminate\Support\Str;
use Log;
use Propaganistas\LaravelPhone\PhoneNumber;

class FormatPhoneMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!empty($request->msisdn)) {
            if ($this->isDefaultCountry($request->msisdn)) {
                $msisdn = str_replace('+', '', (string) PhoneNumber::make($request->msisdn)->ofCountry('GH'));
                $request->merge(['msisdn' => $msisdn, 'msisdn_country' => 'GH']);
            } else {
                $msisdn = $this->appendPlusPrefixToNumber($request->msisdn);
                $country = $this->getCountry($msisdn);
                $msisdn = $this->formatForCountry($msisdn, $country);
                $msisdn = $this->removePlusPrefixFromNumber($request->msisdn);
                $request->merge(['msisdn' => $msisdn, 'msisdn_country' => $country]);
            }
        }
        return $next($request);
    }

    public function appendPlusPrefixToNumber($msisdn)
    {
        return Str::startsWith($msisdn, '+') ? $msisdn : '+' . $msisdn;
    }
    public function removePlusPrefixFromNumber($msisdn)
    {
        return Str::startsWith($msisdn, '+') ? str_replace('+', '', $msisdn) : $msisdn;
    }

    public function isDefaultCountry($msisdn)
    {
        try {
            $isCountry = PhoneNumber::make($msisdn, 'GH')->isOfCountry('GH');
        } catch (\Throwable $e) {
            Log::info('Msisdn is not a Ghanaian number.', [$e]);
            return false;
        }
        return $isCountry;
    }

    public function getCountry($msisdn)
    {
        try {
            $country = PhoneNumber::make($msisdn)->getCountry();
        } catch (\Throwable $e) {
            abort(400, 'Could not determine country of phone number');
        }
        return $country;
    }
    public function formatForCountry($msisdn, $country)
    {
        $msisdn = PhoneNumber::make($msisdn, $country)->ofCountry($country);
        return $msisdn;
    }
}
