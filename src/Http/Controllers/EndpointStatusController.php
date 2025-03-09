<?php

namespace CryptaEve\Seat\EndpointStatus\Http\Controllers;

use ReflectionClass;

use HaydenPierce\ClassFinder\ClassFinder;
use Seat\Web\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;


class EndpointStatusController extends Controller 
{

    public function getStatusView()
    {

        // each entry will be keyed by endpoint and method and have the following underlying keys
        // - latest_version = esi _latest version
        // - seat_version   = the version of the endpoint that SeAT is calling
        // - seat_status    = what the routeblocker has for this endpoint at the moment
        $endpoints = [];

        $response = Http::get('https://esi.evetech.net/_latest/swagger.json');

        if (!$response->successful()) return redirect()->back()->with('error', 'Couldnt reach ESI swagger _latest!');
        if (!isset($response['paths'])) return redirect()->back()->with('error', 'Missing paths from ESI swagger _latest!');

        foreach($response['paths'] as $pathv => $val) {
            preg_match('/^\/(v\d+)\/([^\/]+)(\/.*)?\/?$/', $pathv, $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                // Extract version
                $version = $matches[1]; // e.g. "v1"
                
                // Keep leading and trailing slashes and combine the first path segment with any remaining path
                $path = '/' . $matches[2] . (isset($matches[3]) ? $matches[3] : '');
                
                

                $endpoints[$path] = [];
                $endpoints[$path]['latest_version'] = $version;
        
            } else {
                continue;
            }
        }

        $seatendpoints = [];

        // Now check all the SeAT Job classes
        $autoloadedClasses = ClassFinder::getClassesInNamespace('Seat\Eveapi\Jobs', ClassFinder::RECURSIVE_MODE);
        foreach($autoloadedClasses as $class) {
            if(is_subclass_of($class, 'Seat\Eveapi\Jobs\EsiBase') && !strpos($class, 'Abstract')){
                $reflection = new ReflectionClass($class);
                
                $filePath = $reflection->getFileName();
                if ($filePath) {                    
                    // Read the file content
                    $fileContent = file_get_contents($filePath);
                    
                    // Regular expression to extract the 'endpoint' and 'version' values
                    preg_match('/protected\s+\$endpoint\s*=\s*\'(.*?)\'/', $fileContent, $endpointMatches);
                    preg_match('/protected\s+\$version\s*=\s*\'(.*?)\'/', $fileContent, $versionMatches);
                    
                    // Extract the matched values
                    $endpointValue = isset($endpointMatches[1]) ? $endpointMatches[1] : 'Not found';
                    $versionValue = isset($versionMatches[1]) ? $versionMatches[1] : 'Not found';
                    // dd($endpointValue, $versionValue);
                    $endpoints[$endpointValue]['seat_version'] = $versionValue;
                    $seatendpoints[] = $endpointValue;
                } else {
                   continue;
                }
            }
        }

        foreach($seatendpoints as $se) {
            $cacheKey = 'esi-route-status:' . $se;
            $status = cache($cacheKey, 'Not Present');
            $endpoints[$se]['seat_status'] = $status;
        }


        return view("endpointstatus::status", compact('endpoints'));
    }

}
